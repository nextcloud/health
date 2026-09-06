interface CacheRequest { method: string, mode: string, url: string }

export function isShellNavigation(request: CacheRequest, scopePath: string, origin: string): boolean {
	const url = new URL(request.url)
	return request.method === 'GET' && request.mode === 'navigate' && url.origin === origin && url.pathname.startsWith(scopePath)
}

export function isStaticAsset(request: Pick<CacheRequest, 'method' | 'url'>, assets: readonly string[]): boolean {
	return request.method === 'GET' && assets.includes(new URL(request.url).href)
}
