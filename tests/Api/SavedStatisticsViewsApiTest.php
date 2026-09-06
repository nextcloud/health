<?php

declare(strict_types=1);

namespace OCA\Health\Tests\Api;

use GuzzleHttp\Client;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUserManager;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class SavedStatisticsViewsApiTest extends TestCase {
	private const PASSWORD = 'health-api-test-password';

	private static Client $http;
	private static IDBConnection $db;
	private static IUserManager $userManager;
	private static string $userA;
	private static string $userB;

	public static function setUpBeforeClass(): void {
		$suffix = bin2hex(random_bytes(5));
		self::$userA = 'health-saved-statistics-a-' . $suffix;
		self::$userB = 'health-saved-statistics-b-' . $suffix;
		self::$db = \OC::$server->get(IDBConnection::class);
		self::$userManager = \OC::$server->get(IUserManager::class);
		self::$userManager->createUser(self::$userA, self::PASSWORD);
		self::$userManager->createUser(self::$userB, self::PASSWORD);
		self::$http = new Client([
			'base_uri' => 'http://localhost/ocs/v2.php/apps/health/api/v2/',
			'http_errors' => false,
			'headers' => ['Accept' => 'application/json', 'OCS-APIRequest' => 'true'],
		]);
	}

	public static function tearDownAfterClass(): void {
		self::deleteTestData();
		foreach ([self::$userA, self::$userB] as $userId) {
			$user = self::$userManager->get($userId);
			if ($user !== null) {
				$user->delete();
			}
		}
	}

	protected function setUp(): void {
		self::deleteTestData();
	}

	public function testUnauthenticatedAndInvalidRequestsAreRejected(): void {
		self::assertSame(401, self::$http->request('GET', 'statistics/views')->getStatusCode());
		self::assertSame(400, $this->requestAs(self::$userA, 'POST', 'statistics/views', ['json' => [
			'title' => '',
			'icon' => '📊',
			'metricKeys' => ['stress'],
			'period' => 'last_30_days',
		]])->getStatusCode());
		self::assertSame(400, $this->requestAs(self::$userA, 'POST', 'statistics/views', ['json' => [
			'title' => 'Invalid metrics',
			'icon' => '📊',
			'metricKeys' => ['unknown_metric'],
			'period' => 'last_30_days',
		]])->getStatusCode());
		self::assertSame(400, $this->requestAs(self::$userA, 'POST', 'statistics/views', ['json' => [
			'title' => 'Invalid period',
			'icon' => '📊',
			'metricKeys' => ['stress'],
			'period' => 'forever',
		]])->getStatusCode());
	}

	public function testCreateListFetchUpdateAndDeleteAreOwnerScoped(): void {
		$viewA = $this->createView(self::$userA, 'My trend', '📈', ['stress', 'energy'], 'last_30_days');
		$viewB = $this->createView(self::$userB, 'Private B', '🧭', ['mood'], 'this_week');

		self::assertSame('My trend', $viewA['title']);
		self::assertSame('📈', $viewA['icon']);
		self::assertSame(['stress', 'energy'], $viewA['metricKeys']);
		self::assertSame('last_30_days', $viewA['period']);
		self::assertArrayHasKey('createdAt', $viewA);
		self::assertArrayHasKey('updatedAt', $viewA);

		$listed = $this->ocsData($this->requestAs(self::$userA, 'GET', 'statistics/views'));
		self::assertSame([$viewA['id']], array_column($listed['views'], 'id'));
		self::assertSame($viewA, $this->ocsData($this->requestAs(self::$userA, 'GET', 'statistics/views/' . $viewA['id'])));

		self::assertSame(404, $this->requestAs(self::$userB, 'GET', 'statistics/views/' . $viewA['id'])->getStatusCode());
		self::assertSame(404, $this->requestAs(self::$userB, 'PUT', 'statistics/views/' . $viewA['id'], ['json' => [
			'title' => 'Cross-user update',
			'icon' => '📊',
			'metricKeys' => ['mood'],
			'period' => 'this_week',
		]])->getStatusCode());
		self::assertSame(404, $this->requestAs(self::$userB, 'DELETE', 'statistics/views/' . $viewA['id'])->getStatusCode());

		$updated = $this->ocsData($this->requestAs(self::$userA, 'PUT', 'statistics/views/' . $viewA['id'], ['json' => [
			'title' => 'Updated trend',
			'icon' => '📊',
			'metricKeys' => ['hydration'],
			'period' => 'this_month',
		]]));
		self::assertSame($viewA['id'], $updated['id']);
		self::assertSame('Updated trend', $updated['title']);
		self::assertSame(['hydration'], $updated['metricKeys']);
		self::assertSame('this_month', $updated['period']);

		self::assertSame(200, $this->requestAs(self::$userA, 'DELETE', 'statistics/views/' . $viewA['id'])->getStatusCode());
		self::assertSame([], $this->ocsData($this->requestAs(self::$userA, 'GET', 'statistics/views'))['views']);
		self::assertSame($viewB, $this->ocsData($this->requestAs(self::$userB, 'GET', 'statistics/views/' . $viewB['id'])));
	}

	public function testCreatingCopiedConfigurationCreatesAnIndependentView(): void {
		$source = $this->createView(self::$userA, 'Source', '📈', ['stress', 'energy'], 'last_30_days');
		$clone = $this->createView(self::$userA, 'Copy', '🧭', $source['metricKeys'], $source['period']);

		self::assertNotSame($source['id'], $clone['id']);
		self::assertSame($source['metricKeys'], $clone['metricKeys']);
		self::assertSame($source['period'], $clone['period']);
		self::assertSame('Source', $this->ocsData($this->requestAs(self::$userA, 'GET', 'statistics/views/' . $source['id']))['title']);
	}

	public function testKilocaloriesAndFruitCanBeStoredInSavedStatisticsViews(): void {
		$view = $this->createView(self::$userA, 'Nutrition', '🍎', ['kilocalories', 'fruit'], 'last_7_days');
		self::assertSame(['kilocalories', 'fruit'], $view['metricKeys']);
		self::assertSame($view, $this->ocsData($this->requestAs(self::$userA, 'GET', 'statistics/views/' . $view['id'])));
	}

	/** @param list<string> $metricKeys @return array<string, mixed> */
	private function createView(string $userId, string $title, string $icon, array $metricKeys, string $period): array {
		$response = $this->requestAs($userId, 'POST', 'statistics/views', ['json' => [
			'title' => $title,
			'icon' => $icon,
			'metricKeys' => $metricKeys,
			'period' => $period,
		]]);
		self::assertSame(201, $response->getStatusCode());
		return $this->ocsData($response);
	}

	/** @param array<string, mixed> $options */
	private function requestAs(string $userId, string $method, string $path, array $options = []): ResponseInterface {
		$options['auth'] = [$userId, self::PASSWORD];
		return self::$http->request($method, $path, $options);
	}

	/** @return array<string, mixed> */
	private function ocsData(ResponseInterface $response): array {
		/** @var array{ocs: array{data: array<string, mixed>}} $decoded */
		$decoded = json_decode((string)$response->getBody(), true, 16, JSON_THROW_ON_ERROR);
		return $decoded['ocs']['data'];
	}

	private static function deleteTestData(): void {
		$qb = self::$db->getQueryBuilder();
		$qb->delete('health_statistics_views')
			->where($qb->expr()->orX(
				$qb->expr()->eq('user_id', $qb->createNamedParameter(self::$userA, IQueryBuilder::PARAM_STR)),
				$qb->expr()->eq('user_id', $qb->createNamedParameter(self::$userB, IQueryBuilder::PARAM_STR)),
			))
			->executeStatement();
	}
}
