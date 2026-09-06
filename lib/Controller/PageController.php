<?php

declare(strict_types=1);

namespace OCA\Health\Controller;

use OCA\Health\AppInfo\Application;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IURLGenerator;

/**
 * @psalm-suppress UnusedClass
 */
class PageController extends Controller {
	public function __construct(
		IRequest $request,
		private IURLGenerator $urlGenerator,
		private IAppManager $appManager,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[NoCSRFRequired]
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/')]
	#[FrontpageRoute(verb: 'GET', url: '/journal/{date}', postfix: 'journal')]
	#[FrontpageRoute(verb: 'GET', url: '/goals', postfix: 'goals')]
	#[FrontpageRoute(verb: 'GET', url: '/statistics', postfix: 'statistics')]
	#[FrontpageRoute(verb: 'GET', url: '/statistics/views/{id}', postfix: 'statistics-view')]
	#[FrontpageRoute(verb: 'GET', url: '/donate', postfix: 'donate')]
	#[FrontpageRoute(verb: 'GET', url: '/settings', postfix: 'settings')]
	public function index(): TemplateResponse {
		return new TemplateResponse(
			Application::APP_ID,
			'index',
		);
	}

	#[PublicPage]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/pwa/')]
	public function pwa(): TemplateResponse {
		$appPath = $this->appManager->getAppPath(Application::APP_ID);
		$response = new TemplateResponse(Application::APP_ID, 'pwa', [
			// The existing variants use a dark path for light surfaces and a white path for dark surfaces.
			'iconUrl' => $this->urlGenerator->linkTo(Application::APP_ID, 'img/app-dark.svg'),
			'darkIconUrl' => $this->urlGenerator->linkTo(Application::APP_ID, 'img/app.svg'),
			'scriptUrl' => $this->urlGenerator->linkTo(Application::APP_ID, 'js/health-pwa.mjs'),
			'styleUrl' => $this->urlGenerator->linkTo(Application::APP_ID, 'css/health-pwa.css'),
			'buildVersion' => $this->assetHash($appPath . '/js/health-pwa.mjs'),
		], TemplateResponse::RENDER_AS_BLANK);
		$csp = new EmptyContentSecurityPolicy();
		$csp->addAllowedScriptDomain("'self'");
		$csp->addAllowedStyleDomain("'self'");
		$csp->addAllowedImageDomain("'self'");
		$csp->addAllowedConnectDomain("'self'");
		$csp->addAllowedWorkerSrcDomain("'self'");
		$csp->addAllowedFrameAncestorDomain("'none'");
		$csp->addAllowedFormActionDomain("'none'");
		$response->setContentSecurityPolicy($csp);
		$response->addHeader('Cache-Control', 'no-cache');
		$response->addHeader('Referrer-Policy', 'no-referrer');
		$response->addHeader('X-Content-Type-Options', 'nosniff');
		return $response;
	}

	#[PublicPage]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/pwa/manifest.webmanifest')]
	public function manifest(): DataDisplayResponse {
		$pwaUrl = $this->urlGenerator->linkToRoute('health.page.pwa');
		$iconUrl = $this->urlGenerator->linkTo(Application::APP_ID, 'img/app.svg');
		$manifest = [
			'name' => 'Health',
			'short_name' => 'Health',
			'display' => 'standalone',
			'start_url' => $pwaUrl,
			'scope' => $pwaUrl,
			'background_color' => '#ffffff',
			'theme_color' => '#ffffff',
			'icons' => [['src' => $iconUrl, 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'any maskable']],
		];
		$response = new DataDisplayResponse(json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), headers: [
			'Content-Type' => 'application/manifest+json; charset=utf-8',
			'Cache-Control' => 'public, max-age=300',
			'X-Content-Type-Options' => 'nosniff',
		]);
		$response->setContentSecurityPolicy(new EmptyContentSecurityPolicy());
		return $response;
	}

	#[PublicPage]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/pwa/service-worker.js')]
	public function serviceWorker(): DataDisplayResponse {
		$pwaUrl = $this->urlGenerator->linkToRoute('health.page.pwa');
		$appPath = $this->appManager->getAppPath(Application::APP_ID);
		$workerPath = $appPath . '/js/health-pwa-service-worker.mjs';
		$worker = file_get_contents($workerPath);
		if ($worker === false) {
			throw new \RuntimeException('The Health PWA service worker has not been built.');
		}
		$assets = [
			$this->urlGenerator->getAbsoluteURL($pwaUrl),
			$this->urlGenerator->getAbsoluteURL($this->urlGenerator->linkToRoute('health.page.manifest')),
			$this->urlGenerator->getAbsoluteURL($this->urlGenerator->linkTo(Application::APP_ID, 'js/health-pwa.mjs')),
			$this->urlGenerator->getAbsoluteURL($this->urlGenerator->linkTo(Application::APP_ID, 'css/health-pwa.css')),
			$this->urlGenerator->getAbsoluteURL($this->urlGenerator->linkTo(Application::APP_ID, 'img/app.svg')),
		];
		$fingerprint = implode('', array_map(fn (string $file): string => $this->assetHash($file), [
			$workerPath,
			$appPath . '/js/health-pwa.mjs',
			$appPath . '/css/health-pwa.css',
			$appPath . '/img/app.svg',
			$appPath . '/img/app-dark.svg',
			$appPath . '/templates/pwa.php',
			__FILE__,
		]));
		$scopePath = (string)parse_url($pwaUrl, PHP_URL_PATH);
		$configuration = ['cacheName' => 'health-pwa-' . hash('sha256', $fingerprint), 'buildVersion' => $this->assetHash($appPath . '/js/health-pwa.mjs'), 'scopePath' => $scopePath, 'shellUrl' => $assets[0], 'assets' => $assets];
		$response = new DataDisplayResponse('globalThis.__HEALTH_PWA_CONFIG__=' . json_encode($configuration, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . ";\n" . $worker, headers: [
			'Content-Type' => 'application/javascript; charset=utf-8',
			'Cache-Control' => 'no-cache',
			'Service-Worker-Allowed' => $scopePath,
			'X-Content-Type-Options' => 'nosniff',
		]);
		$response->setContentSecurityPolicy(new EmptyContentSecurityPolicy());
		return $response;
	}

	private function assetHash(string $path): string {
		$hash = hash_file('sha256', $path);
		if ($hash === false) {
			throw new \RuntimeException('A Health PWA shell asset is unavailable.');
		}
		return $hash;
	}
}
