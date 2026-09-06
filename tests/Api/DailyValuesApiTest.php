<?php

declare(strict_types=1);

namespace OCA\Health\Tests\Api;

use GuzzleHttp\Client;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUserManager;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class DailyValuesApiTest extends TestCase {
	private const PASSWORD = 'health-api-test-password';
	private const DATE = '2026-08-24';
	private static Client $http;
	private static IDBConnection $db;
	private static IUserManager $userManager;
	private static string $userA;
	private static string $userB;

	public static function setUpBeforeClass(): void {
		$suffix = bin2hex(random_bytes(5));
		self::$userA = 'health-daily-a-' . $suffix;
		self::$userB = 'health-daily-b-' . $suffix;
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
		self::deleteTestValues();
		foreach ([self::$userA, self::$userB] as $userId) {
			$user = self::$userManager->get($userId);
			if ($user !== null) {
				$user->delete();
			}
		}
	}

	protected function setUp(): void {
		self::deleteTestValues();
	}

	public function testJobSatisfactionIsAnOwnerScopedUnitlessIntegerScale(): void {
		$response = $this->upsert(self::$userA, 1, null);
		self::assertSame(200, $response->getStatusCode());
		$value = $this->ocsData($response);
		self::assertSame('job_satisfaction', $value['metricKey']);
		self::assertEquals(1.0, $value['numericValue']);
		self::assertSame(self::DATE, $value['localDate']);
		self::assertArrayNotHasKey('userId', $value);

		$updated = $this->upsert(self::$userA, 5, null);
		self::assertSame(200, $updated->getStatusCode());
		self::assertSame($value['id'], $this->ocsData($updated)['id']);
		self::assertEquals(5.0, $this->ocsData($updated)['numericValue']);
		$nextDay = '2026-08-25';
		$nextDayValue = $this->upsert(self::$userA, 3, null, $nextDay);
		self::assertSame(200, $nextDayValue->getStatusCode());
		self::assertNotSame($value['id'], $this->ocsData($nextDayValue)['id']);

		$own = $this->ocsData($this->requestAs(self::$userA, 'GET', 'daily-values', ['query' => ['date' => self::DATE]]));
		self::assertCount(1, $own['values']);
		$ownNextDay = $this->ocsData($this->requestAs(self::$userA, 'GET', 'daily-values', ['query' => ['date' => $nextDay]]));
		self::assertCount(1, $ownNextDay['values']);
		$other = $this->ocsData($this->requestAs(self::$userB, 'GET', 'daily-values', ['query' => ['date' => self::DATE]]));
		self::assertSame([], $other['values']);
	}

	public function testJobSatisfactionRejectsOutOfRangeFractionalAndUnitValues(): void {
		foreach ([[0, null], [6, null], [2.5, null], [3, 'count'], ['3', null]] as [$numericValue, $unit]) {
			self::assertSame(400, $this->upsert(self::$userA, $numericValue, $unit)->getStatusCode());
		}
	}

	public function testUnauthenticatedDailyValueRequestsAreRejected(): void {
		self::assertSame(401, self::$http->request('GET', 'daily-values', ['query' => ['date' => self::DATE]])->getStatusCode());
		self::assertSame(401, self::$http->request('PUT', 'daily-values/job_satisfaction/' . self::DATE, ['json' => ['numericValue' => 3, 'unit' => null]])->getStatusCode());
	}

	public function testKilocaloriesUseTheFixedCanonicalUnitAndAllowDecimals(): void {
		$response = $this->requestAs(self::$userA, 'PUT', 'daily-values/kilocalories/' . self::DATE, ['json' => ['numericValue' => 2140.5, 'unit' => 'kcal']]);
		self::assertSame(200, $response->getStatusCode());
		$value = $this->ocsData($response);
		self::assertSame('kilocalories', $value['metricKey']);
		self::assertEquals(2140.5, $value['numericValue']);
		self::assertSame(400, $this->requestAs(self::$userA, 'PUT', 'daily-values/kilocalories/' . self::DATE, ['json' => ['numericValue' => 10, 'unit' => 'kj']])->getStatusCode());
		self::assertSame(400, $this->requestAs(self::$userA, 'PUT', 'daily-values/kilocalories/' . self::DATE, ['json' => ['numericValue' => -1, 'unit' => 'kcal']])->getStatusCode());
	}

	public function testFruitIsAnOwnerScopedNonNegativeWholeNumberCount(): void {
		$response = $this->requestAs(self::$userA, 'PUT', 'daily-values/fruit/' . self::DATE, ['json' => ['numericValue' => 3, 'unit' => 'pieces']]);
		self::assertSame(200, $response->getStatusCode());
		self::assertSame('fruit', $this->ocsData($response)['metricKey']);
		self::assertEquals(3.0, $this->ocsData($response)['numericValue']);
		self::assertSame([], $this->ocsData($this->requestAs(self::$userB, 'GET', 'daily-values', ['query' => ['date' => self::DATE]]))['values']);
		foreach ([-1, 1.5] as $invalid) {
			self::assertSame(400, $this->requestAs(self::$userA, 'PUT', 'daily-values/fruit/' . self::DATE, ['json' => ['numericValue' => $invalid, 'unit' => 'pieces']])->getStatusCode());
		}
	}

	private function upsert(string $userId, mixed $numericValue, mixed $unit, string $date = self::DATE): ResponseInterface {
		return $this->requestAs($userId, 'PUT', 'daily-values/job_satisfaction/' . $date, ['json' => ['numericValue' => $numericValue, 'unit' => $unit]]);
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

	private static function deleteTestValues(): void {
		$qb = self::$db->getQueryBuilder();
		$qb->delete('health_daily_values')->where($qb->expr()->orX(
			$qb->expr()->eq('user_id', $qb->createNamedParameter(self::$userA, IQueryBuilder::PARAM_STR)),
			$qb->expr()->eq('user_id', $qb->createNamedParameter(self::$userB, IQueryBuilder::PARAM_STR)),
		));
		$qb->executeStatement();
	}
}
