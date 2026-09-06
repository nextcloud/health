import type { AccountConfiguration, PendingOperation } from './types.ts'

import { listOperations, putAccount, putOperation, removeOperation } from './storage.ts'
import { ApiError, currentDailyValue, refreshMetadata, sendOperation } from './transport.ts'

export type SyncState = { status: 'idle' | 'syncing' | 'synced' | 'offline' | 'expired' | 'failed', pending: number }

export interface QueueProcessor {
	prepare: (operation: PendingOperation) => Promise<PendingOperation>
	markSending: (operation: PendingOperation) => Promise<void>
	send: (operation: PendingOperation) => Promise<void>
	remove: (operationId: string) => Promise<void>
}

export async function processQueue(operations: PendingOperation[], processor: QueueProcessor): Promise<void> {
	for (let operation of operations) {
		operation = await processor.prepare(operation)
		await processor.markSending(operation)
		await processor.send(operation)
		await processor.remove(operation.operationId)
	}
}

export async function prepareIncrement(account: AccountConfiguration, operation: PendingOperation): Promise<PendingOperation> {
	if (operation.kind !== 'daily_increment' || operation.preparedNumericValue !== null) {
		return operation
	}
	const current = await currentDailyValue(account, operation.metricKey, operation.localDate)
	const prepared = { ...operation, preparedNumericValue: current + operation.delta }
	await putOperation(prepared)
	return prepared
}

export class SyncCoordinator {
	private active: Promise<void> | null = null
	private readonly account: () => AccountConfiguration | null
	private readonly changed: (state: SyncState) => void

	public constructor(account: () => AccountConfiguration | null, changed: (state: SyncState) => void) {
		this.account = account
		this.changed = changed
	}

	public sync(): Promise<void> {
		this.active ??= this.run().finally(() => {
			this.active = null
		})
		return this.active
	}

	private async run(): Promise<void> {
		const account = this.account()
		if (account === null) {
			return
		}
		let operations = await listOperations()
		this.changed({ status: 'syncing', pending: operations.length })
		try {
			const metadata = await refreshMetadata(account)
			Object.assign(account, metadata, { authState: 'connected' as const })
			await putAccount(account)
			await processQueue(operations, {
				prepare: (operation) => prepareIncrement(account, operation),
				markSending: (operation) => putOperation({ ...operation, state: 'sending' }),
				send: (operation) => sendOperation(account, operation.kind === 'daily_increment'
					? { ...operation, kind: 'daily_value', numericValue: operation.preparedNumericValue ?? 0 }
					: operation),
				remove: removeOperation,
			})
			operations = await listOperations()
			this.changed({ status: 'synced', pending: operations.length })
		} catch (error) {
			if (error instanceof ApiError && error.kind === 'authentication') {
				account.authState = 'expired'
				await putAccount(account)
			}
			for (const operation of await listOperations()) {
				await putOperation({ ...operation, state: 'failed-retryable' })
			}
			this.changed({ status: error instanceof ApiError && error.kind === 'authentication' ? 'expired' : error instanceof ApiError && error.kind === 'unreachable' ? 'offline' : 'failed', pending: (await listOperations()).length })
		}
	}
}
