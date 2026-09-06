<?php

declare(strict_types=1);

namespace OCA\Health\Tests\Unit\Controller;

use OC\AppFramework\Routing\RouteParser;
use OCA\Health\AppInfo\Application;
use OCA\Health\Controller\PageController;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

class PageControllerTest extends TestCase {
	/** @return iterable<string, array{string, string}> */
	public static function publicPwaRoutes(): iterable {
		yield 'shell' => ['pwa', '/pwa/'];
		yield 'manifest' => ['manifest', '/pwa/manifest.webmanifest'];
		yield 'service worker' => ['serviceWorker', '/pwa/service-worker.js'];
	}

	#[DataProvider('publicPwaRoutes')]
	public function testOnlyExplicitStaticPwaRoutesAllowAnonymousGet(string $methodName, string $url): void {
		$method = new \ReflectionMethod(PageController::class, $methodName);
		self::assertCount(1, $method->getAttributes(PublicPage::class));
		self::assertCount(1, $method->getAttributes(NoCSRFRequired::class));
		$route = $method->getAttributes(FrontpageRoute::class)[0]->newInstance()->toArray();
		self::assertSame('GET', $route['verb']);
		self::assertSame($url, $route['url']);
	}

	public function testPwaShellManifestAndWorkerContainOnlyStaticConfiguration(): void {
		$controller = $this->controller();
		$shell = $controller->pwa();
		self::assertSame(TemplateResponse::RENDER_AS_BLANK, $shell->getRenderAs());
		self::assertDoesNotMatchRegularExpression('/user|password|token|credential|health value/i', json_encode($shell->getParams(), JSON_THROW_ON_ERROR));
		self::assertSame('/apps/health/img/app.svg', $shell->getParams()['darkIconUrl']);
		$manifest = json_decode($controller->manifest()->render(), true, 8, JSON_THROW_ON_ERROR);
		self::assertSame('/apps/health/pwa/', $manifest['scope']);
		$worker = $controller->serviceWorker();
		self::assertSame('/apps/health/pwa/', $worker->getHeaders()['Service-Worker-Allowed']);
		self::assertStringContainsString('"scopePath":"/apps/health/pwa/"', $worker->render());
		self::assertStringNotContainsString('appPassword', $worker->render());
	}
	public function testSavedStatisticsViewDeepLinkResolvesToTheApplicationPage(): void {
		$match = $this->matcher()->match('/apps/health/statistics/views/74');

		self::assertSame('74', $match['id']);
		self::assertSame([Application::APP_ID, 'PageController', 'index'], $match['caller']);
	}

	public function testMalformedSavedStatisticsViewIdStillReachesTheVueApplication(): void {
		$match = $this->matcher()->match('/apps/health/statistics/views/not-a-number');

		self::assertSame('not-a-number', $match['id']);
		self::assertSame([Application::APP_ID, 'PageController', 'index'], $match['caller']);
	}

	public function testSavedStatisticsViewRouteDoesNotCaptureOcsApiPaths(): void {
		$this->expectException(ResourceNotFoundException::class);

		$this->matcher()->match('/ocs/v2.php/apps/health/api/v2/statistics/views/74');
	}

	private function matcher(): UrlMatcher {
		$reflection = new \ReflectionMethod(PageController::class, 'index');
		$routes = array_map(
			static fn (\ReflectionAttribute $attribute): array => [
				'name' => 'page#index',
				...$attribute->newInstance()->toArray(),
			],
			$reflection->getAttributes(FrontpageRoute::class),
		);
		$collection = (new RouteParser())->parseDefaultRoutes(['routes' => $routes], Application::APP_ID);

		return new UrlMatcher($collection, new RequestContext('/', 'GET'));
	}

	private function controller(): PageController {
		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->method('linkTo')->willReturnCallback(static fn (string $app, string $file): string => '/apps/' . $app . '/' . $file);
		$urlGenerator->method('linkToRoute')->willReturnCallback(static fn (string $route): string => match ($route) {
			'health.page.pwa' => '/apps/health/pwa/',
			'health.page.manifest' => '/apps/health/pwa/manifest.webmanifest',
			default => throw new \LogicException('Unexpected route.'),
		});
		$urlGenerator->method('getAbsoluteURL')->willReturnCallback(static fn (string $url): string => 'https://cloud.example.test' . $url);
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getAppPath')->with(Application::APP_ID)->willReturn(dirname(__DIR__, 3));
		return new PageController($this->createMock(IRequest::class), $urlGenerator, $appManager);
	}
}
