export interface PwaDiagnosticsInput {
	buildVersion: string
	serverOrigin: string | null
	online: boolean
	authentication: 'connected' | 'expired' | 'disconnected'
	synchronization: 'idle' | 'syncing' | 'synced' | 'offline' | 'expired' | 'failed'
	lastSyncAt: string | null
	pendingOperations: number
	serviceWorker: 'active' | 'unavailable'
}

/**
 * Deliberately accepts only the small technical allow-list. Health records,
 * account credentials, HTTP headers, and arbitrary errors cannot enter this
 * diagnostic representation.
 *
 * @param input Technical state supplied by the PWA shell
 */
export function createDiagnostics(input: PwaDiagnosticsInput): Readonly<Record<string, string | number | boolean | null>> {
	return {
		app: 'Health PWA',
		version: input.buildVersion,
		serverOrigin: input.serverOrigin,
		online: input.online,
		authentication: input.authentication,
		synchronization: input.synchronization,
		lastSyncAt: input.lastSyncAt,
		pendingOperations: input.pendingOperations,
		serviceWorker: input.serviceWorker,
	}
}

export function diagnosticsText(diagnostics: Readonly<Record<string, string | number | boolean | null>>): string {
	return JSON.stringify(diagnostics, null, 2)
}
