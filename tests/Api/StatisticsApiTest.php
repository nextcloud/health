<?php

declare(strict_types=1);

namespace OCA\Health\Tests\Api;

use DateTimeImmutable;
use DateTimeZone;
use GuzzleHttp\Client;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUserManager;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class StatisticsApiTest extends TestCase {
	private const PASSWORD = 'health-api-test-password';

	private static Client $http;
	private static IDBConnection $db;
	private static IConfig $config;
	private static IUserManager $userManager;
	private static string $userA;
	private static string $userB;

	public static function setUpBeforeClass(): void {
		$suffix = bin2hex(random_bytes(5));
		self::$userA = 'health-statistics-a-' . $suffix;
		self::$userB = 'health-statistics-b-' . $suffix;
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
		self::$config->setUserValue(self::$userA, 'core', 'timezone', 'UTC');
		self::$config->setUserValue(self::$userB, 'core', 'timezone', 'UTC');
	}

	public function testUnauthenticatedAndInvalidStatisticsRequestsAreRejected(): void {
		self::assertSame(401, self::$http->request('GET', 'statistics', ['query' => ['period' => 'last_7_days']])->getStatusCode());
		self::assertSame(400, $this->requestAs(self::$userA, 'GET', 'statistics', ['query' => ['period' => 'invalid']])->getStatusCode());
		self::assertSame(400, $this->requestAs(self::$userA, 'GET', 'statistics', ['query' => ['period' => 'last_7_days', 'metrics' => 'unknown']])->getStatusCode());
		self::assertSame(400, $this->requestAs(self::$userA, 'GET', 'statistics', ['query' => ['period' => 'last_7_days', 'metrics' => ['stress']]])->getStatusCode());
	}

	public function testStatisticsDefaultsAndEmptyMetricSelectionHaveExplicitResponseShapes(): void {
		$defaultResponse = $this->requestAs(self::$userA, 'GET', 'statistics');
		self::assertSame(200, $defaultResponse->getStatusCode());
		$defaults = $this->ocsData($defaultResponse);
		self::assertSame('last_30_days', $defaults['period']);
		self::assertArrayHasKey('from', $defaults);
		self::assertArrayHasKey('to', $defaults);
		self::assertArrayHasKey('metrics', $defaults);
		self::assertIsArray($defaults['metrics']);

		$emptyResponse = $this->requestAs(self::$userA, 'GET', 'statistics', ['query' => ['metrics' => '']]);
		self::assertSame(200, $emptyResponse->getStatusCode());
		self::assertSame([], $this->ocsData($emptyResponse)['metrics']);
	}

	public function testStatisticsAggregateOwnerScopedJournalDataByLocalDay(): void {
		$range = $this->statisticsAs(self::$userA, 'last_7_days', 'stress,hydration,break');
		$date = $range['from'];
		$this->createEntry(self::$userA, 'stress', 2, null, $date);
		$this->createEntry(self::$userA, 'stress', 4, null, $date);
		$this->createEntry(self::$userB, 'stress', 5, null, $date);
		$this->createEntry(self::$userA, 'hydration', null, 'small_glass', $date);
		$this->createEntry(self::$userA, 'hydration', null, 'espresso', $date);
		$this->createEntry(self::$userA, 'hydration', null, 'tea', $date);
		$this->createEntry(self::$userA, 'hydration', null, 'other', $date);
		$this->createEntry(self::$userA, 'break', null, 'short', $date);
		$this->createEntry(self::$userA, 'break', null, 'mindfulness', $date);

		$statistics = $this->statisticsAs(self::$userA, 'last_7_days', 'stress,hydration,break');
		self::assertSame('last_7_days', $statistics['period']);
		self::assertCount(7, $this->metric($statistics, 'stress')['series']);

		$stress = $this->metric($statistics, 'stress');
		self::assertEquals(3.0, $this->point($stress, $date)['value']);
		self::assertEquals(3.0, $stress['summary']['average']);
		self::assertEquals(3.0, $stress['summary']['minimum']);
		self::assertEquals(3.0, $stress['summary']['maximum']);
		self::assertSame(2, $stress['summary']['count']);
		self::assertSame(1, $stress['summary']['activeDays']);

		$hydration = $this->metric($statistics, 'hydration');
		$hydrationPoint = $this->point($hydration, $date);
		self::assertEquals(4.0, $hydrationPoint['value']);
		self::assertEquals(['water' => 1.0, 'coffee' => 1.0, 'tea' => 1.0, 'other' => 1.0], $hydrationPoint['subseries']);
		self::assertSame(4, $hydration['summary']['count']);
		self::assertEquals(0.0, $hydration['summary']['minimum']);
		self::assertEquals(4.0 / 7, $hydration['summary']['average']);
		self::assertSame(1, $hydration['summary']['activeDays']);

		$break = $this->metric($statistics, 'break');
		$breakPoint = $this->point($break, $date);
		self::assertEquals(2.0, $breakPoint['value']);
		self::assertEquals(1.0, $breakPoint['subseries']['short']);
		self::assertEquals(1.0, $breakPoint['subseries']['mindfulness']);

		$otherUserStatistics = $this->statisticsAs(self::$userB, 'last_7_days', 'stress');
		self::assertSame(1, $this->metric($otherUserStatistics, 'stress')['summary']['count']);
		self::assertEquals(5.0, $this->point($this->metric($otherUserStatistics, 'stress'), $date)['value']);
	}

	public function testStatisticsAggregateMeasurementsDailyValuesAndLogicalBloodPressure(): void {
		$range = $this->statisticsAs(self::$userA, 'last_7_days', 'pulse,blood_pressure,job_satisfaction');
		$date = $range['from'];
		$nextDate = (new DateTimeImmutable($date, new DateTimeZone('UTC')))->modify('+1 day')->format('Y-m-d');
		$this->createMeasurement(self::$userA, 'pulse', 60, null, 'bpm', $date);
		$this->createMeasurement(self::$userA, 'pulse', 80, null, 'bpm', $date);
		$this->createMeasurement(self::$userB, 'pulse', 100, null, 'bpm', $date);
		$this->createMeasurement(self::$userA, 'blood_pressure', null, ['systolic' => 120, 'diastolic' => 80], 'mmhg', $date);
		$this->createMeasurement(self::$userA, 'blood_pressure', null, ['systolic' => 130, 'diastolic' => 90], 'mmhg', $date);
		$this->createDailyValue(self::$userA, 'job_satisfaction', 2, null, $date);
		$this->createDailyValue(self::$userA, 'job_satisfaction', 4, null, $nextDate);

		$statistics = $this->statisticsAs(self::$userA, 'last_7_days', 'pulse,blood_pressure,job_satisfaction');
		$pulse = $this->metric($statistics, 'pulse');
		self::assertEquals(70.0, $this->point($pulse, $date)['value']);
		self::assertSame(2, $pulse['summary']['count']);
		self::assertEquals(70.0, $pulse['summary']['average']);

		$bloodPressure = $this->metric($statistics, 'blood_pressure');
		$bloodPressurePoint = $this->point($bloodPressure, $date);
		self::assertNull($bloodPressurePoint['value']);
		self::assertEquals(125.0, $bloodPressurePoint['subseries']['systolic']);
		self::assertEquals(85.0, $bloodPressurePoint['subseries']['diastolic']);
		self::assertSame(2, $bloodPressure['summary']['count']);
		self::assertEquals(125.0, $bloodPressure['summary']['subseries']['systolic']['average']);
		self::assertEquals(85.0, $bloodPressure['summary']['subseries']['diastolic']['average']);

		$jobSatisfaction = $this->metric($statistics, 'job_satisfaction');
		self::assertEquals(2.0, $this->point($jobSatisfaction, $date)['value']);
		self::assertEquals(4.0, $this->point($jobSatisfaction, $nextDate)['value']);
		self::assertEquals(3.0, $jobSatisfaction['summary']['average']);
		self::assertSame(2, $jobSatisfaction['summary']['count']);
	}

	public function testStatisticsReturnDailyGoalRevisionsWithoutRewritingHistory(): void {
		$range = $this->statisticsAs(self::$userA, 'last_7_days', 'hydration');
		$from = $range['from'];
		$today = (new DateTimeImmutable($range['to'], new DateTimeZone('UTC')))->modify('-1 day')->format('Y-m-d');
		$yesterday = (new DateTimeImmutable($today, new DateTimeZone('UTC')))->modify('-1 day')->format('Y-m-d');

		$goal = $this->ocsData($this->requestAs(self::$userA, 'POST', 'goals', ['json' => [
			'targetKey' => 'hydration.water',
			'period' => 'day',
			'comparator' => 'gte',
			'targetValue' => 2,
			'remindersEnabled' => false,
		]]));
		$qb = self::$db->getQueryBuilder();
		$qb->update('health_goal_revisions')
			->set('effective_from', $qb->createNamedParameter($from, IQueryBuilder::PARAM_STR))
			->where($qb->expr()->eq('goal_id', $qb->createNamedParameter($goal['id'], IQueryBuilder::PARAM_INT)))
			->executeStatement();
		$updated = $this->requestAs(self::$userA, 'PUT', 'goals/' . $goal['id'], ['json' => ['targetValue' => 3]]);
		self::assertSame(200, $updated->getStatusCode());

		$hydration = $this->metric($this->statisticsAs(self::$userA, 'last_7_days', 'hydration'), 'hydration');
		self::assertCount(2, $hydration['goals']);
		self::assertSame('water', $hydration['goals'][0]['seriesKey']);
		self::assertEquals(2.0, $hydration['goals'][0]['targetValue']);
		self::assertSame($from, $hydration['goals'][0]['effectiveFrom']);
		self::assertSame($yesterday, $hydration['goals'][0]['effectiveTo']);
		self::assertEquals(3.0, $hydration['goals'][1]['targetValue']);
		self::assertSame($today, $hydration['goals'][1]['effectiveFrom']);
		self::assertNull($hydration['goals'][1]['effectiveTo']);
	}

	public function testStatisticsReturnOpenEndedLongTermGoalRevisions(): void {
		$range = $this->statisticsAs(self::$userA, 'last_7_days', 'weight,steps');
		$from = $range['from'];
		$today = (new DateTimeImmutable($range['to'], new DateTimeZone('UTC')))->modify('-1 day')->format('Y-m-d');
		$yesterday = (new DateTimeImmutable($today, new DateTimeZone('UTC')))->modify('-1 day')->format('Y-m-d');
		$beforeRange = (new DateTimeImmutable($from, new DateTimeZone('UTC')))->modify('-1 day')->format('Y-m-d');

		$weightGoal = $this->ocsData($this->requestAs(self::$userA, 'POST', 'goals', ['json' => [
			'targetKey' => 'weight',
			'period' => 'long_term',
			'comparator' => 'lte',
			'targetValue' => 80,
			'remindersEnabled' => false,
		]]));
		$this->ocsData($this->requestAs(self::$userA, 'POST', 'goals', ['json' => [
			'targetKey' => 'steps',
			'period' => 'day',
			'comparator' => 'gte',
			'targetValue' => 5000,
			'remindersEnabled' => false,
		]]));
		$qb = self::$db->getQueryBuilder();
		$qb->update('health_goal_revisions')
			->set('effective_from', $qb->createNamedParameter($beforeRange, IQueryBuilder::PARAM_STR))
			->where($qb->expr()->eq('goal_id', $qb->createNamedParameter($weightGoal['id'], IQueryBuilder::PARAM_INT)))
			->executeStatement();
		self::assertSame(200, $this->requestAs(self::$userA, 'PUT', 'goals/' . $weightGoal['id'], ['json' => ['targetValue' => 75]])->getStatusCode());

		$statistics = $this->statisticsAs(self::$userA, 'last_7_days', 'weight,steps');
		$weight = $this->metric($statistics, 'weight');
		self::assertCount(2, $weight['goals']);
		self::assertSame('weight', $weight['goals'][0]['metricKey']);
		self::assertSame('latest_value', $weight['goals'][0]['kind']);
		self::assertEquals(80.0, $weight['goals'][0]['targetValue']);
		self::assertSame($beforeRange, $weight['goals'][0]['effectiveFrom']);
		self::assertSame($yesterday, $weight['goals'][0]['effectiveTo']);
		self::assertEquals(75.0, $weight['goals'][1]['targetValue']);
		self::assertSame($today, $weight['goals'][1]['effectiveFrom']);
		self::assertNull($weight['goals'][1]['effectiveTo']);
		$steps = $this->metric($statistics, 'steps');
		self::assertCount(1, $steps['goals']);
		self::assertSame('steps', $steps['goals'][0]['metricKey']);
	}

	public function testStatisticsUseTheAuthenticatedUsersTimezoneForBuckets(): void {
		self::$config->setUserValue(self::$userA, 'core', 'timezone', 'Europe/Berlin');
		$range = $this->statisticsAs(self::$userA, 'last_7_days', 'stress');
		$localDate = $range['from'];
		$recordedAt = (new DateTimeImmutable($localDate . ' 00:30:00', new DateTimeZone('Europe/Berlin')))
			->setTimezone(new DateTimeZone('UTC'))
			->format('Y-m-d\TH:i:s\Z');
		$this->createEntry(self::$userA, 'stress', 4, null, $localDate, $recordedAt);

		$stress = $this->metric($this->statisticsAs(self::$userA, 'last_7_days', 'stress'), 'stress');
		self::assertEquals(4.0, $this->point($stress, $localDate)['value']);
	}

	private function createEntry(string $userId, string $metricKey, ?int $numericValue, ?string $optionValue, string $date, ?string $recordedAt = null): void {
		$response = $this->requestAs($userId, 'POST', 'entries', ['json' => [
			'metricKey' => $metricKey,
			'numericValue' => $numericValue,
			'optionValue' => $optionValue,
			'context' => 'manual',
			'recordedAt' => $recordedAt ?? $date . 'T12:00:00Z',
			'note' => null,
		]]);
		self::assertSame(201, $response->getStatusCode());
	}

	/** @param array{systolic: int|float, diastolic: int|float}|null $values */
	private function createMeasurement(string $userId, string $metricKey, int|float|null $numericValue, ?array $values, string $unit, string $date): void {
		$response = $this->requestAs($userId, 'POST', 'measurements', ['json' => [
			'metricKey' => $metricKey,
			'numericValue' => $numericValue,
			'values' => $values,
			'unit' => $unit,
			'recordedAt' => $date . 'T12:00:00Z',
			'note' => null,
			'context' => 'manual',
			'source' => 'api',
		]]);
		self::assertSame(201, $response->getStatusCode());
	}

	private function createDailyValue(string $userId, string $metricKey, int|float $numericValue, ?string $unit, string $date): void {
		$response = $this->requestAs($userId, 'PUT', 'daily-values/' . $metricKey . '/' . $date, ['json' => [
			'numericValue' => $numericValue,
			'unit' => $unit,
		]]);
		self::assertSame(200, $response->getStatusCode());
	}

	/** @return array<string, mixed> */
	private function statisticsAs(string $userId, string $period, string $metrics): array {
		$response = $this->requestAs($userId, 'GET', 'statistics', ['query' => ['period' => $period, 'metrics' => $metrics]]);
		self::assertSame(200, $response->getStatusCode());
		return $this->ocsData($response);
	}

	/** @param array<string, mixed> $statistics @return array<string, mixed> */
	private function metric(array $statistics, string $metricKey): array {
		foreach ($statistics['metrics'] as $metric) {
			if ($metric['metricKey'] === $metricKey) {
				return $metric;
			}
		}

		self::fail('Expected metric was not returned: ' . $metricKey);
	}

	/** @param array<string, mixed> $metric @return array<string, mixed> */
	private function point(array $metric, string $date): array {
		foreach ($metric['series'] as $point) {
			if ($point['date'] === $date) {
				return $point;
			}
		}

		self::fail('Expected date was not returned: ' . $date);
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
		$goalIds = self::goalIds();
		foreach (['health_goal_reminder_state', 'health_goal_revisions'] as $table) {
			if ($goalIds === []) {
				continue;
			}
			$qb = self::$db->getQueryBuilder();
			$qb->delete($table)
				->where($qb->expr()->in('goal_id', $qb->createNamedParameter($goalIds, IQueryBuilder::PARAM_INT_ARRAY)))
				->executeStatement();
		}

		foreach (['health_goals', 'health_entries', 'health_daily_values', 'health_measurements', 'health_user_metrics', 'health_user_settings'] as $table) {
			$qb = self::$db->getQueryBuilder();
			$qb->delete($table)
				->where($qb->expr()->orX(
					$qb->expr()->eq('user_id', $qb->createNamedParameter(self::$userA, IQueryBuilder::PARAM_STR)),
					$qb->expr()->eq('user_id', $qb->createNamedParameter(self::$userB, IQueryBuilder::PARAM_STR)),
				))
				->executeStatement();
		}
	}

	/** @return list<int> */
	private static function goalIds(): array {
		$qb = self::$db->getQueryBuilder();
		$qb->select('id')->from('health_goals')
			->where($qb->expr()->orX(
				$qb->expr()->eq('user_id', $qb->createNamedParameter(self::$userA, IQueryBuilder::PARAM_STR)),
				$qb->expr()->eq('user_id', $qb->createNamedParameter(self::$userB, IQueryBuilder::PARAM_STR)),
			));
		return array_map('intval', $qb->executeQuery()->fetchFirstColumn());
	}
}
