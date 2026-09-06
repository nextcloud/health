import type { AccountConfiguration, MetricConfiguration, MetricDefinition, PendingOperation } from './types.ts'

export class ApiError extends Error {
	public readonly kind: 'authentication' | 'unreachable' | 'server'

	public constructor(kind: 'authentication' | 'unreachable' | 'server', message: string) {
		super(message)
		this.kind = kind
	}
}

function authorization(account: AccountConfiguration): string {
	const bytes = new TextEncoder().encode(`${account.loginName}:${account.appPassword}`)
	let binary = ''
	for (const byte of bytes) {
		binary += String.fromCharCode(byte)
	}
	return `Basic ${btoa(binary)}`
}

export function apiBaseUrl(serverUrl: string): string {
	const server = new URL(serverUrl)
	server.pathname = server.pathname.replace(/\/index\.php\/?$/u, '/')
	return new URL('ocs/v2.php/apps/health/api/v2/', `${server.href.replace(/\/+$/u, '')}/`).href
}

async function request(account: AccountConfiguration, path: string, init: RequestInit = {}): Promise<Response> {
	try {
		const response = await fetch(new URL(path.replace(/^\//u, ''), account.apiBaseUrl), {
			...init,
			credentials: 'omit',
			headers: { Accept: 'application/json', Authorization: authorization(account), 'OCS-APIRequest': 'true', ...(init.body === undefined ? {} : { 'Content-Type': 'application/json' }), ...init.headers },
		})
		if (response.status === 401 || response.status === 403) {
			throw new ApiError('authentication', 'Connection expired.')
		}
		if (!response.ok) {
			throw new ApiError('server', `Health returned HTTP ${response.status}.`)
		}
		return response
	} catch (error) {
		if (error instanceof ApiError) {
			throw error
		}
		throw new ApiError('unreachable', 'Health is unreachable.')
	}
}

async function data<T>(response: Response): Promise<T> {
	const body = await response.json() as { ocs?: { data?: T } }
	if (body.ocs?.data === undefined) {
		throw new ApiError('server', 'Health returned an invalid response.')
	}
	return body.ocs.data
}

export async function refreshMetadata(account: AccountConfiguration): Promise<Pick<AccountConfiguration, 'metrics' | 'configuration'>> {
	const capabilitiesUrl = new URL('ocs/v2.php/cloud/capabilities', `${account.serverUrl.replace(/\/+$/u, '')}/`)
	const capabilitiesResponse = await fetch(capabilitiesUrl, { credentials: 'omit', headers: { Accept: 'application/json', Authorization: authorization(account), 'OCS-APIRequest': 'true' } })
	if (capabilitiesResponse.status === 401 || capabilitiesResponse.status === 403) {
		throw new ApiError('authentication', 'Connection expired.')
	}
	if (!capabilitiesResponse.ok) {
		throw new ApiError('server', 'Could not load Health capabilities.')
	}
	const capabilities = await data<{ capabilities: { health?: { metrics?: MetricDefinition[] } } }>(capabilitiesResponse)
	const configuration = await data<{ metrics: Record<string, MetricConfiguration> }>(await request(account, 'configuration'))
	return { metrics: capabilities.capabilities.health?.metrics ?? [], configuration: configuration.metrics }
}

export async function sendOperation(account: AccountConfiguration, operation: PendingOperation): Promise<void> {
	if (operation.kind === 'journal') {
		await request(account, 'entries', { method: 'POST', body: JSON.stringify({ metricKey: operation.metricKey, numericValue: operation.numericValue, optionValue: operation.optionValue, context: 'manual', source: 'mobile', recordedAt: operation.recordedAt, note: null, operationId: operation.operationId }) })
		return
	}
	if (operation.kind === 'measurement') {
		await request(account, 'measurements', { method: 'POST', body: JSON.stringify({ metricKey: operation.metricKey, numericValue: operation.numericValue, values: operation.values, unit: operation.unit, context: 'manual', source: 'mobile', recordedAt: operation.recordedAt, note: null, operationId: operation.operationId }) })
		return
	}
	if (operation.kind === 'daily_value') {
		await request(account, `daily-values/${encodeURIComponent(operation.metricKey)}/${operation.localDate}`, { method: 'PUT', body: JSON.stringify({ numericValue: operation.numericValue, unit: operation.unit }) })
		return
	}
	throw new TypeError('Daily increments must be prepared before sending.')
}

export async function currentDailyValue(account: AccountConfiguration, metricKey: string, localDate: string): Promise<number> {
	const page = await data<{ values: Array<{ metricKey: string, numericValue: number }> }>(await request(account, `daily-values?date=${encodeURIComponent(localDate)}`))
	return page.values.find((value) => value.metricKey === metricKey)?.numericValue ?? 0
}

export async function revoke(account: AccountConfiguration, path: string): Promise<void> {
	await fetch(path, { method: 'DELETE', credentials: 'omit', headers: { Accept: 'application/json', Authorization: authorization(account), 'OCS-APIRequest': 'true' } })
}
