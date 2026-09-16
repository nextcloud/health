<?php

declare(strict_types=1);

namespace OCA\Health\Tests\Api;

use DateTimeImmutable;
use DateTimeZone;
use GuzzleHttp\Client;
use OCA\Health\Migration\Version3006Date20260915140000;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDateTimeZone;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class MeasurementsApiTest extends TestCase {
	private const PASSWORD = 'health-api-test-password';
	private const RECORDED_AT = '2026-09-15T12:00:00Z';
	private static Client $http;
	private static IDBConnection $db;
	private static IConfig $config;
	private static IUserManager $userManager;
	private static string $userA;
	private static string $userB;

	public static function setUpBeforeClass(): void {
		$suffix = bin2hex(random_bytes(5));
		self::$userA = 'health-measurements-a-' . $suffix;
		self::$userB = 'health-measurements-b-' . $suffix;
		self::$db = \OC::$server->get(IDBConnection::class);
		self::$config = \OC::$server->get(IConfig::class);
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
		self::deleteTestMeasurements();
		foreach ([self::$userA, self::$userB] as $userId) {
			$user = self::$userManager->get($userId);
			if ($user !== null) {
				$user->delete();
			}
		}
	}

	protected function setUp(): void {
		self::deleteTestMeasurements();
		self::$config->setUserValue(self::$userA, 'core', 'timezone', 'UTC');
		self::$config->setUserValue(self::$userB, 'core', 'timezone', 'UTC');
	}

	public function testKilocaloriesAllowMultipleOwnerScopedMeasurementsAndUseOnlyKcal(): void {
		$first = $this->createAs(self::$userA, 450);
		$second = $this->createAs(self::$userA, 700, '2026-09-15T13:00:00Z');
		$third = $this->createAs(self::$userA, 180, '2026-09-15T14:00:00Z');
		self::assertSame(201, $first->getStatusCode());
		self::assertSame(201, $second->getStatusCode());
		self::assertSame(201, $third->getStatusCode());
		$created = $this->ocsData($first);
		self::assertSame('kilocalories', $created['metricKey']);
		self::assertEquals(450.0, $created['numericValue']);
		self::assertSame(self::RECORDED_AT, $created['recordedAt']);
		self::assertArrayNotHasKey('userId', $created);

		$own = $this->ocsData($this->requestAs(self::$userA, 'GET', 'measurements', ['query' => ['from' => '2026-09-15T00:00:00Z', 'to' => '2026-09-16T00:00:00Z']]));
		self::assertCount(3, $own['measurements']);
		self::assertSame([], $this->ocsData($this->requestAs(self::$userB, 'GET', 'measurements', ['query' => ['from' => '2026-09-15T00:00:00Z', 'to' => '2026-09-16T00:00:00Z']]))['measurements']);

		self::assertSame(400, $this->createAs(self::$userA, -1)->getStatusCode());
		self::assertSame(400, $this->createAs(self::$userA, 200, self::RECORDED_AT, 'kj')->getStatusCode());
	}

	public function testKilocalorieMeasurementsCannotBeReadUpdatedOrDeletedByAnotherUser(): void {
		$created = $this->ocsData($this->createAs(self::$userA, 450));
		$id = $created['id'];
		$replacement = ['numericValue' => 500, 'values' => null, 'unit' => 'kcal', 'recordedAt' => self::RECORDED_AT, 'note' => null, 'context' => 'manual'];
		self::assertSame(404, $this->requestAs(self::$userB, 'PUT', 'measurements/' . $id, ['json' => $replacement])->getStatusCode());
		self::assertSame(404, $this->requestAs(self::$userB, 'DELETE', 'measurements/' . $id)->getStatusCode());
		self::assertSame(200, $this->requestAs(self::$userA, 'PUT', 'measurements/' . $id, ['json' => $replacement])->getStatusCode());
		self::assertSame(200, $this->requestAs(self::$userA, 'DELETE', 'measurements/' . $id)->getStatusCode());
	}

	public function testUnauthenticatedMeasurementRequestsAreRejected(): void {
		self::assertSame(401, self::$http->request('GET', 'measurements')->getStatusCode());
		self::assertSame(401, self::$http->request('POST', 'measurements', ['json' => $this->request(450)])->getStatusCode());
	}

	public function testLegacyDailyKilocaloriesMigrateOnceToTheSameOwnersLocalDate(): void {
		$this->insertLegacyDailyKilocalories(self::$userA, 2140.5, '2026-09-15');
		$migration = new Version3006Date20260915140000(self::$db, \OC::$server->get(IDateTimeZone::class));
		$migration->postSchemaChange($this->createMock(IOutput::class), static function (): never {
			throw new \LogicException('The migration must not request a schema change.');
		}, []);

		$measurements = $this->ocsData($this->requestAs(self::$userA, 'GET', 'measurements', ['query' => ['from' => '2026-09-15T00:00:00Z', 'to' => '2026-09-16T00:00:00Z']]))['measurements'];
		self::assertCount(1, $measurements);
		self::assertSame('kilocalories', $measurements[0]['metricKey']);
		self::assertEquals(2140.5, $measurements[0]['numericValue']);
		self::assertSame('2026-09-15T12:00:00Z', $measurements[0]['recordedAt']);
		self::assertSame([], $this->ocsData($this->requestAs(self::$userA, 'GET', 'daily-values', ['query' => ['date' => '2026-09-15']]))['values']);

		$migration->postSchemaChange($this->createMock(IOutput::class), static function (): never {
			throw new \LogicException('The migration must not request a schema change.');
		}, []);
		self::assertCount(1, $this->ocsData($this->requestAs(self::$userA, 'GET', 'measurements', ['query' => ['from' => '2026-09-15T00:00:00Z', 'to' => '2026-09-16T00:00:00Z']]))['measurements']);
	}

	private function createAs(string $userId, int|float $numericValue, string $recordedAt = self::RECORDED_AT, string $unit = 'kcal'): ResponseInterface {
		return $this->requestAs($userId, 'POST', 'measurements', ['json' => $this->request($numericValue, $recordedAt, $unit)]);
	}

	/** @return array<string, mixed> */
	private function request(int|float $numericValue, string $recordedAt = self::RECORDED_AT, string $unit = 'kcal'): array {
		return ['metricKey' => 'kilocalories', 'numericValue' => $numericValue, 'values' => null, 'unit' => $unit, 'recordedAt' => $recordedAt, 'note' => null, 'context' => 'manual', 'source' => 'api'];
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

	private static function deleteTestMeasurements(): void {
		foreach (['health_daily_values', 'health_measurements'] as $table) {
			$qb = self::$db->getQueryBuilder();
			$qb->delete($table)->where($qb->expr()->orX(
				$qb->expr()->eq('user_id', $qb->createNamedParameter(self::$userA, IQueryBuilder::PARAM_STR)),
				$qb->expr()->eq('user_id', $qb->createNamedParameter(self::$userB, IQueryBuilder::PARAM_STR)),
			));
			$qb->executeStatement();
		}
	}

	private function insertLegacyDailyKilocalories(string $userId, float $numericValue, string $localDate): void {
		$timestamp = new DateTimeImmutable($localDate . 'T12:00:00Z', new DateTimeZone('UTC'));
		$qb = self::$db->getQueryBuilder();
		$qb->insert('health_daily_values')->values([
			'user_id' => $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR),
			'metric_key' => $qb->createNamedParameter('kilocalories', IQueryBuilder::PARAM_STR),
			'numeric_value' => $qb->createNamedParameter((string)$numericValue, IQueryBuilder::PARAM_STR),
			'local_date' => $qb->createNamedParameter($localDate, IQueryBuilder::PARAM_STR),
			'created_at' => $qb->createNamedParameter($timestamp, IQueryBuilder::PARAM_DATETIME_IMMUTABLE),
			'updated_at' => $qb->createNamedParameter($timestamp, IQueryBuilder::PARAM_DATETIME_IMMUTABLE),
		]);
		$qb->executeStatement();
	}
}
