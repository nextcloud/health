import { isShellNavigation, isStaticAsset } from './cachePolicy.ts'

interface Configuration { cacheName: string, scopePath: string, shellUrl: string, assets: string[] }
interface WorkerExtendableEvent extends Event { waitUntil: (promise: Promise<unknown>) => void }
interface WorkerFetchEvent extends WorkerExtendableEvent { request: Request, respondWith: (response: Promise<Response>) => void }
const worker = globalThis as typeof globalThis & { __HEALTH_PWA_CONFIG__: Configuration, clients: { claim: () => Promise<void> }, skipWaiting: () => Promise<void> }
const configuration = worker.__HEALTH_PWA_CONFIG__

globalThis.addEventListener('install', (rawEvent) => {
	const event = rawEvent as WorkerExtendableEvent
	event.waitUntil(caches.open(configuration.cacheName).then((cache) => cache.addAll(configuration.assets)))
})

globalThis.addEventListener('activate', (rawEvent) => {
	const event = rawEvent as WorkerExtendableEvent
	event.waitUntil(Promise.all([
		caches.keys().then((names) => Promise.all(names.filter((name) => name.startsWith('health-pwa-') && name !== configuration.cacheName).map((name) => caches.delete(name)))),
		worker.clients.claim(),
	]))
})

globalThis.addEventListener('fetch', (rawEvent) => {
	const event = rawEvent as WorkerFetchEvent
	if (event.request.method !== 'GET') {
		return
	}
	if (isShellNavigation(event.request, configuration.scopePath, location.origin)) {
		event.respondWith(fetch(event.request).then((response) => response.ok && !response.redirected ? response : (caches.match(configuration.shellUrl).then((cached) => cached ?? response))).catch(() => caches.match(configuration.shellUrl).then((cached) => cached ?? Response.error())))
		return
	}
	if (isStaticAsset(event.request, configuration.assets)) {
		event.respondWith(caches.match(event.request).then((cached) => cached ?? fetch(event.request)))
	}
})

globalThis.addEventListener('message', (rawEvent) => {
	if ((rawEvent as MessageEvent).data === 'SKIP_WAITING') {
		void worker.skipWaiting()
	}
})
