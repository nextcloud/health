import type { AccountConfiguration, PendingOperation } from './types.ts'

const DATABASE_NAME = 'health-pwa'
export const DATABASE_VERSION = 1
let databasePromise: Promise<IDBDatabase> | null = null

function result<T>(request: IDBRequest<T>): Promise<T> {
	return new Promise((resolve, reject) => {
		request.addEventListener('success', () => resolve(request.result), { once: true })
		request.addEventListener('error', () => reject(request.error ?? new Error('IndexedDB request failed.')), { once: true })
	})
}

function done(transaction: IDBTransaction): Promise<void> {
	return new Promise((resolve, reject) => {
		transaction.addEventListener('complete', () => resolve(), { once: true })
		transaction.addEventListener('abort', () => reject(transaction.error ?? new Error('IndexedDB transaction aborted.')), { once: true })
	})
}

export function openDatabase(): Promise<IDBDatabase> {
	databasePromise ??= new Promise((resolve, reject) => {
		const request = indexedDB.open(DATABASE_NAME, DATABASE_VERSION)
		request.addEventListener('upgradeneeded', () => {
			request.result.createObjectStore('account', { keyPath: 'key' })
			const outbox = request.result.createObjectStore('outbox', { keyPath: 'operationId' })
			outbox.createIndex('createdAt', 'createdAt')
		})
		request.addEventListener('success', () => resolve(request.result), { once: true })
		request.addEventListener('error', () => reject(request.error ?? new Error('Could not open local Health data.')), { once: true })
	})
	return databasePromise
}

export async function getAccount(): Promise<AccountConfiguration | null> {
	const database = await openDatabase()
	return (await result<AccountConfiguration | undefined>(database.transaction('account').objectStore('account').get('primary'))) ?? null
}

export async function putAccount(account: AccountConfiguration): Promise<void> {
	const database = await openDatabase()
	const transaction = database.transaction('account', 'readwrite')
	transaction.objectStore('account').put(account)
	await done(transaction)
}

export async function listOperations(): Promise<PendingOperation[]> {
	const database = await openDatabase()
	const operations = await result<PendingOperation[]>(database.transaction('outbox').objectStore('outbox').getAll())
	return operations.sort((left, right) => left.createdAt.localeCompare(right.createdAt))
}

export async function putOperation(operation: PendingOperation): Promise<void> {
	const database = await openDatabase()
	const transaction = database.transaction('outbox', 'readwrite')
	transaction.objectStore('outbox').put(operation)
	await done(transaction)
}

export async function removeOperation(operationId: string): Promise<void> {
	const database = await openDatabase()
	const transaction = database.transaction('outbox', 'readwrite')
	transaction.objectStore('outbox').delete(operationId)
	await done(transaction)
}

export async function clearLocalData(): Promise<void> {
	const database = await openDatabase()
	const transaction = database.transaction(['account', 'outbox'], 'readwrite')
	transaction.objectStore('account').clear()
	transaction.objectStore('outbox').clear()
	await done(transaction)
}
