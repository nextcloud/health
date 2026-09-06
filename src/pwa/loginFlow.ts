import type { AccountConfiguration, LoginStart } from './types.ts'

import { apiBaseUrl } from './transport.ts'

function sameOrigin(value: string): URL {
	const url = new URL(value, location.origin)
	if (url.origin !== location.origin) {
		throw new TypeError('Health PWA supports only its installation origin.')
	}
	return url
}

export async function startLogin(path: string): Promise<LoginStart> {
	const response = await fetch(path, { method: 'POST', credentials: 'omit', headers: { Accept: 'application/json' } })
	if (!response.ok) {
		throw new Error('Could not start login.')
	}
	const value = await response.json() as LoginStart
	sameOrigin(value.login)
	sameOrigin(value.poll.endpoint)
	return value
}

export async function pollLogin(start: LoginStart, previous?: AccountConfiguration, signal?: AbortSignal): Promise<AccountConfiguration> {
	const deadline = Date.now() + 10 * 60_000
	while (Date.now() < deadline) {
		const response = await fetch(sameOrigin(start.poll.endpoint), { method: 'POST', credentials: 'omit', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ token: start.poll.token }), signal })
		if (response.status === 200) {
			const value = await response.json() as { server: string, loginName: string, appPassword: string }
			const server = new URL(value.server)
			if (server.origin !== location.origin || value.loginName === '' || value.appPassword === '') {
				throw new Error('Invalid login response.')
			}
			const serverUrl = server.href.replace(/\/$/u, '')
			return { key: 'primary', serverUrl, apiBaseUrl: apiBaseUrl(serverUrl), loginName: value.loginName, appPassword: value.appPassword, locale: previous?.locale ?? navigator.language, timezone: previous?.timezone ?? Intl.DateTimeFormat().resolvedOptions().timeZone, metrics: previous?.metrics ?? [], configuration: previous?.configuration ?? {}, authState: 'connected' }
		}
		if (response.status !== 404) {
			throw new Error('Login was rejected.')
		}
		await new Promise((resolve) => window.setTimeout(resolve, 1000))
	}
	throw new Error('Login timed out.')
}
