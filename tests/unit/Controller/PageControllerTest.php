<?php

declare(strict_types=1);

namespace OCA\Health\Tests\Unit\Controller;

use OC\AppFramework\Routing\RouteParser;
use OCA\Health\AppInfo\Application;
use OCA\Health\Controller\PageController;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

class PageControllerTest extends TestCase {
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
}
