<?php

declare(strict_types=1);

namespace OCA\Health\Tests\Api;

use DateTimeImmutable;
use DateTimeZone;
use GuzzleHttp\Client;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUserManager;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class GoalsApiTest extends TestCase {
	private const PASSWORD = 'health-api-test-password';
	private static Client $http;
	private static IDBConnection $db;
	private static IUserManager $userManager;
	private static string $userA;
	private static string $userB;

	public static function setUpBeforeClass(): void {
		$suffix = bin2hex(random_bytes(5));
		self::$userA = 'health-goals-a-' . $suffix;
		self::$userB = 'health-goals-b-' . $suffix;
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

	public function testUnauthenticatedGoalEndpointsAreRejected(): void {
		self::assertSame(401, self::$http->request('GET', 'goals')->getStatusCode());
		self::assertSame(401, self::$http->request('POST', 'goals', ['json' => $this->goalRequest()])->getStatusCode());
		self::assertSame(401, self::$http->request('PUT', 'goals/1', ['json' => ['active' => false]])->getStatusCode());
		self::assertSame(401, self::$http->request('DELETE', 'goals/1')->getStatusCode());
		self::assertSame(401, self::$http->request('GET', 'goals/progress', ['query' => ['period' => 'day', 'date' => $this->today()]])->getStatusCode());
	}

	public function testCreateListAndProgressArePrivateAndDerived(): void {
		$created = $this->requestAs(self::$userA, 'POST', 'goals', ['json' => $this->goalRequest()]);
		self::assertSame(201, $created->getStatusCode());
		$goal = $this->ocsData($created);
		self::assertIsInt($goal['id']);
		self::assertSame('hydration.water', $goal['targetKey']);
		self::assertSame('day', $goal['period']);
		self::assertTrue($goal['active']);
		self::assertNull($goal['retiredAt']);
		self::assertEquals(2.0, $goal['currentRevision']['targetValue']);
		self::assertArrayNotHasKey('userId', $goal);

		$own = $this->ocsData($this->requestAs(self::$userA, 'GET', 'goals'));
		self::assertCount(1, $own['goals']);
		self::assertContains('hydration.water', array_column($own['targets'], 'targetKey'));
		$other = $this->ocsData($this->requestAs(self::$userB, 'GET', 'goals'));
		self::assertSame([], $other['goals']);
		self::assertSame($own['targets'], $other['targets']);

		$this->createWaterEntry(self::$userA);
		$first = $this->progressAs(self::$userA, 'day');
		self::assertCount(1, $first);
		self::assertSame($goal['id'], $first[0]['goalId']);
		self::assertEquals(1.0, $first[0]['currentValue']);
		self::assertEquals(0.5, $first[0]['progressRatio']);
		self::assertSame('in_progress', $first[0]['status']);
		self::assertArrayNotHasKey('lastActivityAt', $first[0]);

		$this->createWaterEntry(self::$userA);
		$reached = $this->progressAs(self::$userA, 'day');
		self::assertEquals(2.0, $reached[0]['currentValue']);
		self::assertEquals(1.0, $reached[0]['progressRatio']);
		self::assertSame('reached', $reached[0]['status']);
		self::assertSame([], $this->progressAs(self::$userB, 'day'));
	}

	public function testGoalValidationRevisionPauseRetirementAndOwnership(): void {
		foreach ([
			['targetKey' => 'unknown'],
			['period' => 'long_term'],
			['comparator' => 'invalid'],
			['targetValue' => 0],
			['targetValue' => '2'],
			['remindersEnabled' => 'yes'],
		] as $override) {
			$response = $this->requestAs(self::$userA, 'POST', 'goals', ['json' => array_replace($this->goalRequest(), $override)]);
			self::assertSame(400, $response->getStatusCode());
		}

		$created = $this->ocsData($this->requestAs(self::$userA, 'POST', 'goals', ['json' => $this->goalRequest()]));
		self::assertSame(400, $this->requestAs(self::$userA, 'POST', 'goals', ['json' => $this->goalRequest()])->getStatusCode());
		$coffee = $this->ocsData($this->requestAs(self::$userA, 'POST', 'goals', ['json' => array_replace($this->goalRequest(), ['targetKey' => 'hydration.coffee'])]));
		self::assertSame(400, $this->requestAs(self::$userA, 'PUT', 'goals/' . $created['id'], ['json' => ['targetKey' => 'hydration.coffee']])->getStatusCode());
		$goalsAfterRejectedIdentityChange = $this->ocsData($this->requestAs(self::$userA, 'GET', 'goals'))['goals'];
		$goalsById = [];
		foreach ($goalsAfterRejectedIdentityChange as $listedGoal) {
			$goalsById[$listedGoal['id']] = $listedGoal;
		}
		self::assertTrue($goalsById[$created['id']]['active']);
		self::assertTrue($goalsById[$coffee['id']]['active']);
		self::assertSame(404, $this->requestAs(self::$userB, 'PUT', 'goals/' . $created['id'], ['json' => ['active' => false]])->getStatusCode());
		self::assertSame(404, $this->requestAs(self::$userB, 'PUT', 'goals/' . $created['id'], ['json' => ['active' => true]])->getStatusCode());
		self::assertSame(404, $this->requestAs(self::$userB, 'DELETE', 'goals/' . $created['id'])->getStatusCode());

		$updated = $this->ocsData($this->requestAs(self::$userA, 'PUT', 'goals/' . $created['id'], ['json' => ['targetValue' => 3, 'remindersEnabled' => true]]));
		self::assertSame($created['id'], $updated['id']);
		self::assertEquals(3.0, $updated['currentRevision']['targetValue']);
		self::assertTrue($updated['remindersEnabled']);
		self::assertSame($this->today(), $updated['currentRevision']['effectiveFrom']);

		$paused = $this->ocsData($this->requestAs(self::$userA, 'PUT', 'goals/' . $created['id'], ['json' => ['active' => false]]));
		self::assertFalse($paused['active']);
		self::assertNull($paused['retiredAt']);
		self::assertSame('paused', $this->progressAs(self::$userA, 'day')[0]['status']);
		$resumed = $this->ocsData($this->requestAs(self::$userA, 'PUT', 'goals/' . $created['id'], ['json' => ['active' => true]]));
		self::assertSame($created['id'], $resumed['id']);
		self::assertTrue($resumed['active']);
		self::assertNull($resumed['retiredAt']);
		self::assertCount(2, $this->ocsData($this->requestAs(self::$userA, 'GET', 'goals'))['goals']);
		$this->requestAs(self::$userA, 'PUT', 'goals/' . $created['id'], ['json' => ['active' => false]]);
		$retired = $this->ocsData($this->requestAs(self::$userA, 'DELETE', 'goals/' . $created['id']));
		self::assertFalse($retired['active']);
		self::assertFalse($retired['remindersEnabled']);
		self::assertIsString($retired['retiredAt']);
		self::assertSame([$coffee['id']], array_column($this->ocsData($this->requestAs(self::$userA, 'GET', 'goals'))['goals'], 'id'));
		self::assertSame(400, $this->requestAs(self::$userA, 'PUT', 'goals/' . $created['id'], ['json' => ['active' => true]])->getStatusCode());
		$activeRetired = $this->ocsData($this->requestAs(self::$userA, 'DELETE', 'goals/' . $coffee['id']));
		self::assertFalse($activeRetired['active']);
		self::assertIsString($activeRetired['retiredAt']);
		self::assertSame([], $this->ocsData($this->requestAs(self::$userA, 'GET', 'goals'))['goals']);
		self::assertSame(404, $this->requestAs(self::$userA, 'GET', 'goals/reminder-state')->getStatusCode());
	}

	public function testProgressRejectsInvalidAndFuturePeriods(): void {
		self::assertSame(400, $this->requestAs(self::$userA, 'GET', 'goals/progress', ['query' => ['period' => 'day']])->getStatusCode());
		self::assertSame(400, $this->requestAs(self::$userA, 'GET', 'goals/progress', ['query' => ['period' => 'day', 'date' => 'not-a-date']])->getStatusCode());
		$future = (new DateTimeImmutable($this->today(), new DateTimeZone('UTC')))->modify('+1 month')->format('Y-m-d');
		self::assertSame(400, $this->requestAs(self::$userA, 'GET', 'goals/progress', ['query' => ['period' => 'month', 'date' => $future]])->getStatusCode());
		self::assertSame(400, $this->requestAs(self::$userA, 'GET', 'goals/progress', ['query' => ['period' => 'long_term', 'date' => $this->today()]])->getStatusCode());
	}

	public function testProgressDerivesEveryInitialRegistryTarget(): void {
		foreach ([
			['targetKey' => 'hydration.water', 'period' => 'day', 'comparator' => 'gte', 'targetValue' => 2],
			['targetKey' => 'hydration.coffee', 'period' => 'day', 'comparator' => 'lte', 'targetValue' => 3],
			['targetKey' => 'break.all', 'period' => 'day', 'comparator' => 'gte', 'targetValue' => 2],
			['targetKey' => 'break.mindfulness', 'period' => 'day', 'comparator' => 'gte', 'targetValue' => 1],
			['targetKey' => 'steps', 'period' => 'day', 'comparator' => 'gte', 'targetValue' => 10000],
			['targetKey' => 'job_satisfaction', 'period' => 'day', 'comparator' => 'gte', 'targetValue' => 4],
			['targetKey' => 'pulse', 'period' => 'day', 'comparator' => 'lte', 'targetValue' => 50],
			['targetKey' => 'blood_pressure', 'period' => 'day', 'comparator' => 'gte', 'targetValue' => 1],
		] as $request) {
			$this->createGoal($request);
		}

		$this->createEventEntry(self::$userA, 'hydration', 'small_glass');
		$this->createEventEntry(self::$userA, 'hydration', 'large_glass');
		$this->createEventEntry(self::$userA, 'hydration', 'coffee');
		$this->createEventEntry(self::$userA, 'hydration', 'cappuccino');
		$this->createEventEntry(self::$userA, 'break', 'short');
		$this->createEventEntry(self::$userA, 'break', 'mindfulness');
		$this->upsertDailyValue(self::$userA, 'steps', 8420, 'steps');
		$this->upsertDailyValue(self::$userA, 'job_satisfaction', 4, null);
		$this->createMeasurement(self::$userA, 'pulse', 54, null, 'bpm');
		$this->createMeasurement(self::$userA, 'pulse', 49, null, 'bpm');
		$this->createMeasurement(self::$userA, 'blood_pressure', null, ['systolic' => 120, 'diastolic' => 80], 'mmhg');

		$progress = $this->progressByTarget($this->progressAs(self::$userA, 'day'));
		self::assertSame('reached', $progress['hydration.water']['status']);
		self::assertEquals(2.0, $progress['hydration.water']['currentValue']);
		self::assertSame('within_limit', $progress['hydration.coffee']['status']);
		self::assertEquals(2.0, $progress['hydration.coffee']['currentValue']);
		self::assertSame('reached', $progress['break.all']['status']);
		self::assertEquals(2.0, $progress['break.all']['currentValue']);
		self::assertSame('reached', $progress['break.mindfulness']['status']);
		self::assertEquals(1.0, $progress['break.mindfulness']['currentValue']);
		self::assertSame('in_progress', $progress['steps']['status']);
		self::assertEquals(8420.0, $progress['steps']['currentValue']);
		self::assertSame('reached', $progress['job_satisfaction']['status']);
		self::assertSame('reached', $progress['pulse']['status']);
		self::assertEquals(49.0, $progress['pulse']['observedValue']);
		self::assertSame('reached', $progress['blood_pressure']['status']);
		self::assertEquals(1.0, $progress['blood_pressure']['currentValue']);
	}

	public function testLongTermWeightSupportsBothUserSelectedDirections(): void {
		$goal = $this->createGoal(['targetKey' => 'weight', 'period' => 'long_term', 'comparator' => 'lte', 'targetValue' => 78]);
		$this->upsertDailyValue(self::$userA, 'weight', 82, 'kg');
		$belowGoal = $this->progressByTarget($this->progressAs(self::$userA, 'long_term'))['weight'];
		self::assertSame('in_progress', $belowGoal['status']);
		self::assertEquals(82.0, $belowGoal['currentValue']);

		$this->upsertDailyValue(self::$userA, 'weight', 86, 'kg');
		$updated = $this->ocsData($this->requestAs(self::$userA, 'PUT', 'goals/' . $goal['id'], ['json' => ['comparator' => 'gte', 'targetValue' => 85]]));
		self::assertSame('gte', $updated['currentRevision']['comparator']);
		$aboveGoal = $this->progressByTarget($this->progressAs(self::$userA, 'long_term'))['weight'];
		self::assertSame('reached', $aboveGoal['status']);
		self::assertEquals(86.0, $aboveGoal['currentValue']);
	}

	public function testMultiplePeriodsForTheSameTargetPersistAndChangeIndependently(): void {
		$daily = $this->createGoal(['targetKey' => 'steps', 'period' => 'day', 'targetValue' => 5000]);
		$weekly = $this->createGoal(['targetKey' => 'steps', 'period' => 'week', 'targetValue' => 30000]);
		self::assertNotSame($daily['id'], $weekly['id']);
		self::assertSame(400, $this->requestAs(self::$userA, 'POST', 'goals', ['json' => $this->goalRequest(['targetKey' => 'steps', 'period' => 'day', 'targetValue' => 6000])])->getStatusCode());
		$updated = $this->ocsData($this->requestAs(self::$userA, 'PUT', 'goals/' . $daily['id'], ['json' => ['targetValue' => 6000, 'remindersEnabled' => true]]));
		self::assertEquals(6000.0, $updated['currentRevision']['targetValue']);
		self::assertTrue($updated['remindersEnabled']);
		self::assertEquals(30000.0, $this->goalById($weekly['id'])['currentRevision']['targetValue']);
		$this->requestAs(self::$userA, 'DELETE', 'goals/' . $daily['id']);
		self::assertSame($weekly['id'], $this->goalById($weekly['id'])['id']);
		self::assertSame(404, $this->requestAs(self::$userB, 'PUT', 'goals/' . $weekly['id'], ['json' => ['targetValue' => 1]])->getStatusCode());

		$dailyWeight = $this->createGoal(['targetKey' => 'weight', 'period' => 'day', 'comparator' => 'lte', 'targetValue' => 85]);
		$longTermWeight = $this->createGoal(['targetKey' => 'weight', 'period' => 'long_term', 'comparator' => 'lte', 'targetValue' => 80]);
		self::assertNotSame($dailyWeight['id'], $longTermWeight['id']);
		self::assertSame(400, $this->requestAs(self::$userA, 'POST', 'goals', ['json' => $this->goalRequest(['targetKey' => 'weight', 'period' => 'long_term', 'comparator' => 'lte', 'targetValue' => 78])])->getStatusCode());
		$this->upsertDailyValue(self::$userA, 'weight', 84, 'kg');
		self::assertSame('reached', $this->progressByTarget($this->progressAs(self::$userA, 'day'))['weight']['status']);
		self::assertSame('in_progress', $this->progressByTarget($this->progressAs(self::$userA, 'long_term'))['weight']['status']);
		$changedLongTerm = $this->ocsData($this->requestAs(self::$userA, 'PUT', 'goals/' . $longTermWeight['id'], ['json' => ['targetValue' => 79, 'remindersEnabled' => true]]));
		self::assertEquals(79.0, $changedLongTerm['currentRevision']['targetValue']);
		self::assertTrue($changedLongTerm['remindersEnabled']);
		self::assertEquals(85.0, $this->goalById($dailyWeight['id'])['currentRevision']['targetValue']);
		self::assertFalse($this->goalById($dailyWeight['id'])['remindersEnabled']);
		$this->requestAs(self::$userA, 'DELETE', 'goals/' . $dailyWeight['id']);
		self::assertSame($longTermWeight['id'], $this->goalById($longTermWeight['id'])['id']);
	}

	public function testLongTermWeightJourneyProgressUsesItsBaselineAndDirection(): void {
		$today = $this->today();
		$start = (new DateTimeImmutable($today, new DateTimeZone('UTC')))->modify('-2 days')->format('Y-m-d');
		$this->upsertDailyValue(self::$userA, 'weight', 90, 'kg', $start);
		$below = $this->createGoal(['targetKey' => 'weight', 'period' => 'long_term', 'comparator' => 'lte', 'targetValue' => 80]);
		$this->setRevisionStart($below['id'], $start);
		$this->upsertDailyValue(self::$userA, 'weight', 85, 'kg', $today);
		$progress = $this->progressByTarget($this->progressAs(self::$userA, 'long_term'))['weight'];
		self::assertEquals(90.0, $progress['baselineValue']);
		self::assertEquals(0.5, $progress['progressRatio']);
		self::assertSame('in_progress', $progress['status']);

		$this->upsertDailyValue(self::$userA, 'weight', 79, 'kg', $today);
		$completed = $this->progressByTarget($this->progressAs(self::$userA, 'long_term'))['weight'];
		self::assertEquals(1.0, $completed['progressRatio']);
		self::assertSame('reached', $completed['status']);
		$this->requestAs(self::$userA, 'DELETE', 'goals/' . $below['id']);
		self::assertArrayNotHasKey('weight', $this->progressByTarget($this->progressAs(self::$userA, 'long_term')));

		$this->upsertDailyValue(self::$userB, 'weight', 90, 'kg', $start);
		$aboveResponse = $this->requestAs(self::$userB, 'POST', 'goals', ['json' => $this->goalRequest(['targetKey' => 'weight', 'period' => 'long_term', 'comparator' => 'gte', 'targetValue' => 100])]);
		self::assertSame(201, $aboveResponse->getStatusCode());
		$above = $this->ocsData($aboveResponse);
		$this->setRevisionStart($above['id'], $start);
		$this->upsertDailyValue(self::$userB, 'weight', 95, 'kg', $today);
		$upward = $this->progressByTarget($this->progressAs(self::$userB, 'long_term'))['weight'];
		self::assertEquals(0.5, $upward['progressRatio']);
		self::assertSame('in_progress', $upward['status']);

		$this->upsertDailyValue(self::$userB, 'weight', 101, 'kg', $today);
		$upwardCompleted = $this->progressByTarget($this->progressAs(self::$userB, 'long_term'))['weight'];
		self::assertEquals(1.0, $upwardCompleted['progressRatio']);
		self::assertSame('reached', $upwardCompleted['status']);
	}

	public function testRevisionPreservesThePriorDailyTarget(): void {
		$goal = $this->createGoal();
		$yesterday = (new DateTimeImmutable($this->today(), new DateTimeZone('UTC')))->modify('-1 day')->format('Y-m-d');
		$qb = self::$db->getQueryBuilder();
		$qb->update('health_goal_revisions')
			->set('effective_from', $qb->createNamedParameter($yesterday, IQueryBuilder::PARAM_STR))
			->where($qb->expr()->eq('goal_id', $qb->createNamedParameter($goal['id'], IQueryBuilder::PARAM_INT)))
			->executeStatement();
		$this->createEventEntry(self::$userA, 'hydration', 'small_glass', $yesterday);

		$updated = $this->ocsData($this->requestAs(self::$userA, 'PUT', 'goals/' . $goal['id'], ['json' => ['targetValue' => 3]]));
		self::assertSame($this->today(), $updated['currentRevision']['effectiveFrom']);
		$historical = $this->progressByTarget($this->progressAs(self::$userA, 'day', $yesterday))['hydration.water'];
		self::assertEquals(2.0, $historical['targetValue']);
		self::assertSame('not_reached', $historical['status']);

		$this->createWaterEntry(self::$userA);
		$this->createWaterEntry(self::$userA);
		$this->createWaterEntry(self::$userA);
		$current = $this->progressByTarget($this->progressAs(self::$userA, 'day'))['hydration.water'];
		self::assertEquals(3.0, $current['targetValue']);
		self::assertSame('reached', $current['status']);
	}

	/** @param array<string, mixed> $overrides @return array<string, mixed> */
	private function goalRequest(array $overrides = []): array {
		return array_replace(['targetKey' => 'hydration.water', 'period' => 'day', 'comparator' => 'gte', 'targetValue' => 2, 'remindersEnabled' => false], $overrides);
	}

	/** @param array<string, mixed> $request @return array<string, mixed> */
	private function createGoal(array $request = []): array {
		$response = $this->requestAs(self::$userA, 'POST', 'goals', ['json' => $this->goalRequest($request)]);
		self::assertSame(201, $response->getStatusCode());
		return $this->ocsData($response);
	}

	private function createWaterEntry(string $userId): void {
		$this->createEventEntry($userId, 'hydration', 'small_glass');
	}

	private function createEventEntry(string $userId, string $metricKey, string $optionValue, ?string $date = null): void {
		$response = $this->requestAs($userId, 'POST', 'entries', ['json' => [
			'metricKey' => $metricKey, 'numericValue' => null, 'optionValue' => $optionValue, 'context' => 'manual',
			'recordedAt' => ($date ?? $this->today()) . 'T12:00:00Z', 'note' => null,
		]]);
		self::assertSame(201, $response->getStatusCode());
	}

	private function upsertDailyValue(string $userId, string $metricKey, int|float $numericValue, ?string $unit, ?string $date = null): void {
		$response = $this->requestAs($userId, 'PUT', 'daily-values/' . $metricKey . '/' . ($date ?? $this->today()), ['json' => ['numericValue' => $numericValue, 'unit' => $unit]]);
		self::assertSame(200, $response->getStatusCode());
	}

	/** @return array<string, mixed> */
	private function goalById(int $id): array {
		foreach ($this->ocsData($this->requestAs(self::$userA, 'GET', 'goals'))['goals'] as $goal) {
			if ($goal['id'] === $id) {
				return $goal;
			}
		}
		self::fail('Goal not found.');
	}

	private function setRevisionStart(int $goalId, string $date): void {
		$qb = self::$db->getQueryBuilder();
		$qb->update('health_goal_revisions')
			->set('effective_from', $qb->createNamedParameter($date, IQueryBuilder::PARAM_STR))
			->where($qb->expr()->eq('goal_id', $qb->createNamedParameter($goalId, IQueryBuilder::PARAM_INT)))
			->executeStatement();
	}

	/** @param array{systolic: int|float, diastolic: int|float}|null $values */
	private function createMeasurement(string $userId, string $metricKey, int|float|null $numericValue, ?array $values, string $unit): void {
		$response = $this->requestAs($userId, 'POST', 'measurements', ['json' => [
			'metricKey' => $metricKey, 'numericValue' => $numericValue, 'values' => $values, 'unit' => $unit,
			'recordedAt' => $this->today() . 'T12:00:00Z', 'note' => null, 'context' => 'manual', 'source' => 'api',
		]]);
		self::assertSame(201, $response->getStatusCode());
	}

	/** @return list<array<string, mixed>> */
	private function progressAs(string $userId, string $period, ?string $date = null): array {
		$query = ['period' => $period];
		if ($period !== 'long_term') {
			$query['date'] = $date ?? $this->today();
		}
		$data = $this->ocsData($this->requestAs($userId, 'GET', 'goals/progress', ['query' => $query]));
		/** @var list<array<string, mixed>> $goals */
		$goals = $data['goals'];
		return $goals;
	}

	/** @param list<array<string, mixed>> $progress @return array<string, array<string, mixed>> */
	private function progressByTarget(array $progress): array {
		$result = [];
		foreach ($progress as $item) {
			$result[$item['targetKey']] = $item;
		}
		return $result;
	}

	private function today(): string {
		return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d');
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
		foreach (['health_goal_reminder_state', 'health_goal_revisions', 'health_goals', 'health_entries', 'health_daily_values', 'health_measurements', 'health_user_metrics', 'health_user_settings'] as $table) {
			$goalIds = in_array($table, ['health_goal_reminder_state', 'health_goal_revisions'], true) ? self::goalIds() : [];
			if (in_array($table, ['health_goal_reminder_state', 'health_goal_revisions'], true) && $goalIds === []) {
				continue;
			}
			$qb = self::$db->getQueryBuilder();
			$qb->delete($table);
			if (in_array($table, ['health_goal_reminder_state', 'health_goal_revisions'], true)) {
				$qb->where($qb->expr()->in('goal_id', $qb->createNamedParameter($goalIds, IQueryBuilder::PARAM_INT_ARRAY)));
			} elseif ($table === 'health_goals') {
				$qb->where($qb->expr()->orX(
					$qb->expr()->eq('user_id', $qb->createNamedParameter(self::$userA, IQueryBuilder::PARAM_STR)),
					$qb->expr()->eq('user_id', $qb->createNamedParameter(self::$userB, IQueryBuilder::PARAM_STR)),
				));
			} else {
				$qb->where($qb->expr()->orX(
					$qb->expr()->eq('user_id', $qb->createNamedParameter(self::$userA, IQueryBuilder::PARAM_STR)),
					$qb->expr()->eq('user_id', $qb->createNamedParameter(self::$userB, IQueryBuilder::PARAM_STR)),
				));
			}
			$qb->executeStatement();
		}
	}

	/** @return list<int> */
	private static function goalIds(): array {
		$qb = self::$db->getQueryBuilder();
		$qb->select('id')->from('health_goals')->where($qb->expr()->orX(
			$qb->expr()->eq('user_id', $qb->createNamedParameter(self::$userA, IQueryBuilder::PARAM_STR)),
			$qb->expr()->eq('user_id', $qb->createNamedParameter(self::$userB, IQueryBuilder::PARAM_STR)),
		));
		return array_map('intval', $qb->executeQuery()->fetchFirstColumn());
	}
}
