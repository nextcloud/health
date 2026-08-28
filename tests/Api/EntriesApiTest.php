<?php

declare(strict_types=1);

namespace OCA\Health\Tests\Api;

use DateTimeImmutable;
use GuzzleHttp\Client;
use OCA\Health\Service\EntryService;
use OCA\Health\Service\MetricService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUserManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class EntriesApiTest extends TestCase {
	private const PASSWORD = 'health-api-test-password';

	private static Client $http;
	private static IDBConnection $db;
	private static EntryService $entryService;
	private static IUserManager $userManager;
	private static string $userA;
	private static string $userB;

	public static function setUpBeforeClass(): void {
		$suffix = bin2hex(random_bytes(5));
		self::$userA = 'health-api-a-' . $suffix;
		self::$userB = 'health-api-b-' . $suffix;

		self::$db = \OC::$server->get(IDBConnection::class);
		self::$entryService = \OC::$server->get(EntryService::class);
		self::$userManager = \OC::$server->get(IUserManager::class);
		self::$userManager->createUser(self::$userA, self::PASSWORD);
		self::$userManager->createUser(self::$userB, self::PASSWORD);

		self::$http = new Client([
			'base_uri' => 'http://localhost/ocs/v2.php/apps/health/api/v2/',
			'http_errors' => false,
			'headers' => [
				'Accept' => 'application/json',
				'OCS-APIRequest' => 'true',
			],
		]);
	}

	public static function tearDownAfterClass(): void {
		self::deleteTestEntries();

		foreach ([self::$userA, self::$userB] as $userId) {
			$user = self::$userManager->get($userId);
			if ($user !== null) {
				$user->delete();
			}
		}
	}

	protected function setUp(): void {
		self::deleteTestEntries();
	}

	#[DataProvider('unauthenticatedRequestProvider')]
	public function testUnauthenticatedAccessIsRejected(string $method, string $path, array $options): void {
		$response = self::$http->request($method, $path, $options);

		self::assertSame(401, $response->getStatusCode());
	}

	/**
	 * @return list<array{string}>
	 */
	public static function unauthenticatedRequestProvider(): array {
		$entry = [
			'metricKey' => 'stress',
			'numericValue' => 3,
			'optionValue' => null,
			'context' => 'manual',
			'recordedAt' => '2026-08-17T12:00:00Z',
			'note' => null,
		];

		return [
			['GET', 'entries', []],
			['POST', 'entries', ['json' => $entry]],
			['PUT', 'entries/1', ['json' => $entry]],
			['DELETE', 'entries/1', []],
		];
	}

	public function testAuthenticatedStressCreationSucceeds(): void {
		$response = $this->requestAs(self::$userA, 'POST', 'entries', [
			'json' => $this->validEntry([
				'numericValue' => 4,
				'recordedAt' => '2026-08-17T14:30:00+02:00',
				'note' => 'Synthetic API test note',
			]),
		]);

		self::assertSame(201, $response->getStatusCode());
		$entry = $this->ocsData($response);
		self::assertIsInt($entry['id']);
		self::assertSame('stress', $entry['metricKey']);
		self::assertSame(4, $entry['numericValue']);
		self::assertNull($entry['optionValue']);
		self::assertSame('manual', $entry['context']);
		self::assertSame('api', $entry['source']);
		self::assertSame('2026-08-17T12:30:00Z', $entry['recordedAt']);
		self::assertMatchesRegularExpression('/Z$/', $entry['createdAt']);
		self::assertSame($entry['createdAt'], $entry['updatedAt']);
		self::assertSame('Synthetic API test note', $entry['note']);
		self::assertArrayNotHasKey('userId', $entry);
	}

	#[DataProvider('validBoundaryProvider')]
	public function testStressBoundaryValuesSucceed(int $value): void {
		$response = $this->createAs(self::$userA, ['numericValue' => $value]);

		self::assertSame(201, $response->getStatusCode());
		self::assertSame($value, $this->ocsData($response)['numericValue']);
	}

	/**
	 * @return list<array{int}>
	 */
	public static function validBoundaryProvider(): array {
		return [[1], [5]];
	}

	#[DataProvider('invalidStressValueProvider')]
	public function testInvalidStressValuesFail(mixed $value): void {
		$response = $this->createAs(self::$userA, ['numericValue' => $value]);

		self::assertSame(400, $response->getStatusCode());
	}

	/**
	 * @return list<array{mixed}>
	 */
	public static function invalidStressValueProvider(): array {
		return [[0], [6], [1.5], ['4']];
	}

	#[DataProvider('validAdditionalScaleValueProvider')]
	public function testAdditionalScaleBoundaryValuesSucceed(string $metricKey, int $value): void {
		$response = $this->createAs(self::$userA, [
			'metricKey' => $metricKey,
			'numericValue' => $value,
		]);

		self::assertSame(201, $response->getStatusCode());
		$entry = $this->ocsData($response);
		self::assertSame($metricKey, $entry['metricKey']);
		self::assertSame($value, $entry['numericValue']);
		self::assertNull($entry['optionValue']);
	}

	/**
	 * @return list<array{string, int}>
	 */
	public static function validAdditionalScaleValueProvider(): array {
		return [
			['energy', 1],
			['energy', 5],
			['mood', 1],
			['mood', 5],
		];
	}

	#[DataProvider('invalidAdditionalScaleValueProvider')]
	public function testAdditionalScaleInvalidValuesFail(string $metricKey, mixed $value): void {
		$response = $this->createAs(self::$userA, [
			'metricKey' => $metricKey,
			'numericValue' => $value,
		]);

		self::assertSame(400, $response->getStatusCode());
	}

	/**
	 * @return list<array{string, mixed}>
	 */
	public static function invalidAdditionalScaleValueProvider(): array {
		return [
			['energy', 0],
			['energy', 6],
			['mood', 0],
			['mood', 6],
			['mood', 3.5],
			['mood', '3'],
		];
	}

	#[DataProvider('validBeverageOptionProvider')]
	public function testBeverageHydrationOptionsCanBeCreatedAndListed(string $optionValue): void {
		$created = $this->createAs(self::$userA, [
			'metricKey' => 'hydration',
			'numericValue' => null,
			'optionValue' => $optionValue,
		]);

		self::assertSame(201, $created->getStatusCode());
		self::assertSame($optionValue, $this->ocsData($created)['optionValue']);
		self::assertSame($optionValue, $this->listAs(self::$userA)['entries'][0]['optionValue']);
	}

	/**
	 * @return list<array{string}>
	 */
	public static function validBeverageOptionProvider(): array {
		return [
			['coffee'],
			['cappuccino'],
			['espresso'],
			['double_espresso'],
			['latte_macchiato'],
			['cafe_au_lait'],
			['tea'],
		];
	}

	public function testUnsupportedHydrationOptionIsRejected(): void {
		$response = $this->createAs(self::$userA, [
			'metricKey' => 'hydration',
			'numericValue' => null,
			'optionValue' => 'iced_coffee',
		]);

		self::assertSame(400, $response->getStatusCode());
	}

	public function testScaleMetricRejectsOptionValue(): void {
		$response = $this->createAs(self::$userA, ['optionValue' => 'large_glass']);

		self::assertSame(400, $response->getStatusCode());
	}

	#[DataProvider('validEventOptionProvider')]
	public function testEventOptionsSucceed(string $metricKey, string $optionValue): void {
		$response = $this->createAs(self::$userA, [
			'metricKey' => $metricKey,
			'numericValue' => null,
			'optionValue' => $optionValue,
		]);

		self::assertSame(201, $response->getStatusCode());
		$entry = $this->ocsData($response);
		self::assertSame($metricKey, $entry['metricKey']);
		self::assertNull($entry['numericValue']);
		self::assertSame($optionValue, $entry['optionValue']);
	}

	/**
	 * @return list<array{string, string}>
	 */
	public static function validEventOptionProvider(): array {
		return [
			['hydration', 'small_glass'],
			['hydration', 'large_glass'],
			['hydration', 'coffee'],
			['hydration', 'tea'],
			['hydration', 'other'],
			['break', 'short'],
			['break', 'regular'],
			['break', 'short_walk'],
			['break', 'long_walk'],
			['break', 'mindfulness'],
			['break', 'fresh_air'],
		];
	}

	public function testEventMetricMayOmitNumericValue(): void {
		$request = $this->validEntry([
			'metricKey' => 'hydration',
			'optionValue' => 'small_glass',
		]);
		unset($request['numericValue']);

		$response = $this->requestAs(self::$userA, 'POST', 'entries', ['json' => $request]);

		self::assertSame(201, $response->getStatusCode());
		self::assertNull($this->ocsData($response)['numericValue']);
	}

	#[DataProvider('invalidEventOptionProvider')]
	public function testEventMetricRejectsUnknownOption(string $metricKey, string $optionValue): void {
		$response = $this->createAs(self::$userA, [
			'metricKey' => $metricKey,
			'numericValue' => null,
			'optionValue' => $optionValue,
		]);

		self::assertSame(400, $response->getStatusCode());
	}

	/**
	 * @return list<array{string, string}>
	 */
	public static function invalidEventOptionProvider(): array {
		return [
			['hydration', 'bucket'],
			['break', 'lunch'],
		];
	}

	#[DataProvider('eventMetricProvider')]
	public function testEventMetricRejectsNumericValue(string $metricKey, string $validOption): void {
		$response = $this->createAs(self::$userA, [
			'metricKey' => $metricKey,
			'numericValue' => 1,
			'optionValue' => $validOption,
		]);

		self::assertSame(400, $response->getStatusCode());
	}

	/**
	 * @return list<array{string, string}>
	 */
	public static function eventMetricProvider(): array {
		return [
			['hydration', 'small_glass'],
			['break', 'short'],
		];
	}

	public function testUnknownMetricFails(): void {
		$response = $this->createAs(self::$userA, ['metricKey' => 'unknown']);

		self::assertSame(400, $response->getStatusCode());
	}

	public function testInvalidContextFails(): void {
		$response = $this->createAs(self::$userA, ['context' => 'invalid']);

		self::assertSame(400, $response->getStatusCode());
	}

	#[DataProvider('invalidTimestampProvider')]
	public function testInvalidTimestampFails(string $timestamp): void {
		$response = $this->createAs(self::$userA, ['recordedAt' => $timestamp]);

		self::assertSame(400, $response->getStatusCode());
	}

	/**
	 * @return list<array{string}>
	 */
	public static function invalidTimestampProvider(): array {
		return [
			['2026-08-17 12:00:00'],
			['2026-02-30T12:00:00Z'],
			['2026-08-17T12:00:00+15:00'],
		];
	}

	public function testNoteLongerThanMaximumFails(): void {
		$response = $this->createAs(self::$userA, ['note' => str_repeat('é', 1001)]);

		self::assertSame(400, $response->getStatusCode());
	}

	public function testRequestCannotAlterOwnershipAndUsersAreIsolated(): void {
		$response = $this->createAs(self::$userA, [
			'userId' => self::$userB,
			'source' => 'mobile',
		]);
		self::assertSame(201, $response->getStatusCode());
		$createdId = $this->ocsData($response)['id'];

		$userBPage = $this->listAs(self::$userB);
		self::assertSame([], $userBPage['entries']);

		$userAPage = $this->listAs(self::$userA);
		self::assertCount(1, $userAPage['entries']);
		self::assertSame($createdId, $userAPage['entries'][0]['id']);
		self::assertArrayNotHasKey('userId', $userAPage['entries'][0]);
		self::assertSame('mobile', $userAPage['entries'][0]['source']);
	}

	public function testWebSourceSucceedsAndIsReturnedByList(): void {
		$created = $this->createAs(self::$userA, ['source' => 'web']);

		self::assertSame(201, $created->getStatusCode());
		self::assertSame('web', $this->ocsData($created)['source']);
		self::assertSame('web', $this->listAs(self::$userA)['entries'][0]['source']);
	}

	public function testOmittedSourceDefaultsToApi(): void {
		$created = $this->createAs(self::$userA);

		self::assertSame('api', $this->ocsData($created)['source']);
	}

	public function testUnsupportedSourceFails(): void {
		$response = $this->createAs(self::$userA, ['source' => 'browser']);

		self::assertSame(400, $response->getStatusCode());
	}

	public function testOwnerCanUpdateStressValueWithoutChangingMetricOrSource(): void {
		$created = $this->ocsData($this->createAs(self::$userA, [
			'numericValue' => 4,
			'source' => 'web',
		]));
		usleep(1100000);

		$response = $this->updateAs(self::$userA, $created['id'], [
			'numericValue' => 3,
			'metricKey' => 'mood',
			'note' => 'Updated note',
		]);

		self::assertSame(200, $response->getStatusCode());
		$updated = $this->ocsData($response);
		self::assertSame($created['id'], $updated['id']);
		self::assertSame('stress', $updated['metricKey']);
		self::assertSame(3, $updated['numericValue']);
		self::assertSame('web', $updated['source']);
		self::assertSame('Updated note', $updated['note']);
		self::assertNotSame($created['updatedAt'], $updated['updatedAt']);
	}

	#[DataProvider('invalidStressValueProvider')]
	public function testUpdatedStressValueIsValidated(mixed $value): void {
		$created = $this->ocsData($this->createAs(self::$userA));

		$response = $this->updateAs(self::$userA, $created['id'], ['numericValue' => $value]);

		self::assertSame(400, $response->getStatusCode());
	}

	public function testOwnerCanUpdateHydrationOption(): void {
		$created = $this->ocsData($this->createAs(self::$userA, [
			'metricKey' => 'hydration',
			'numericValue' => null,
			'optionValue' => 'small_glass',
		]));

		$response = $this->updateAs(self::$userA, $created['id'], [
			'numericValue' => null,
			'optionValue' => 'large_glass',
		]);

		self::assertSame(200, $response->getStatusCode());
		$updated = $this->ocsData($response);
		self::assertSame('hydration', $updated['metricKey']);
		self::assertNull($updated['numericValue']);
		self::assertSame('large_glass', $updated['optionValue']);
	}

	public function testInvalidHydrationUpdateFails(): void {
		$created = $this->ocsData($this->createAs(self::$userA, [
			'metricKey' => 'hydration',
			'numericValue' => null,
			'optionValue' => 'small_glass',
		]));

		$response = $this->updateAs(self::$userA, $created['id'], [
			'numericValue' => null,
			'optionValue' => 'bucket',
		]);

		self::assertSame(400, $response->getStatusCode());
	}

	public function testUserCannotUpdateAnotherUsersEntry(): void {
		$created = $this->ocsData($this->createAs(self::$userA));

		$response = $this->updateAs(self::$userB, $created['id'], ['numericValue' => 5]);

		self::assertSame(404, $response->getStatusCode());
		self::assertSame(3, $this->listAs(self::$userA)['entries'][0]['numericValue']);
	}

	public function testOwnerCanDeleteEntryAndItIsNoLongerListed(): void {
		$created = $this->ocsData($this->createAs(self::$userA));

		$response = $this->requestAs(self::$userA, 'DELETE', 'entries/' . $created['id']);

		self::assertSame(200, $response->getStatusCode());
		self::assertNull($this->ocsDataValue($response));
		self::assertSame([], $this->listAs(self::$userA)['entries']);
	}

	public function testForeignDeleteMatchesMissingDeleteAndDoesNotRemoveEntry(): void {
		$created = $this->ocsData($this->createAs(self::$userA));

		$foreign = $this->requestAs(self::$userB, 'DELETE', 'entries/' . $created['id']);
		$missing = $this->requestAs(self::$userB, 'DELETE', 'entries/999999999');

		self::assertSame(404, $foreign->getStatusCode());
		self::assertSame($missing->getStatusCode(), $foreign->getStatusCode());
		self::assertSame(1, count($this->listAs(self::$userA)['entries']));
	}

	public function testTimeRangeIsFromInclusiveAndToExclusive(): void {
		$fromEntry = $this->createAs(self::$userA, ['recordedAt' => '2026-08-17T00:00:00Z']);
		$insideEntry = $this->createAs(self::$userA, ['recordedAt' => '2026-08-17T12:00:00Z']);
		$toEntry = $this->createAs(self::$userA, ['recordedAt' => '2026-08-18T00:00:00Z']);

		$page = $this->listAs(self::$userA, [
			'from' => '2026-08-17T00:00:00Z',
			'to' => '2026-08-18T00:00:00Z',
		]);
		$ids = array_column($page['entries'], 'id');

		self::assertContains($this->ocsData($fromEntry)['id'], $ids);
		self::assertContains($this->ocsData($insideEntry)['id'], $ids);
		self::assertNotContains($this->ocsData($toEntry)['id'], $ids);
	}

	public function testPaginationDefaultsTo50AndHasMaximum200(): void {
		$this->createEntriesDirectly(self::$userA, 201, '2026-08-17T12:00:00Z');

		$defaultPage = $this->listAs(self::$userA);
		self::assertCount(50, $defaultPage['entries']);
		self::assertIsString($defaultPage['nextCursor']);

		$maximumPage = $this->listAs(self::$userA, ['limit' => 200]);
		self::assertCount(200, $maximumPage['entries']);
		self::assertIsString($maximumPage['nextCursor']);

		$response = $this->requestAs(self::$userA, 'GET', 'entries', [
			'query' => ['limit' => 201],
		]);
		self::assertSame(400, $response->getStatusCode());
	}

	public function testMalformedCursorFailsSafely(): void {
		$response = $this->requestAs(self::$userA, 'GET', 'entries', [
			'query' => ['cursor' => 'not-a-valid-cursor'],
		]);

		self::assertSame(400, $response->getStatusCode());
	}

	public function testIdenticalRecordedAtDoesNotDuplicateOrSkipAcrossPages(): void {
		$expectedIds = $this->createEntriesDirectly(self::$userA, 5, '2026-08-17T12:00:00Z');
		rsort($expectedIds);

		$actualIds = [];
		$cursor = null;
		do {
			$query = ['limit' => 2];
			if ($cursor !== null) {
				$query['cursor'] = $cursor;
			}
			$page = $this->listAs(self::$userA, $query);
			array_push($actualIds, ...array_column($page['entries'], 'id'));
			$cursor = $page['nextCursor'];
		} while ($cursor !== null);

		self::assertSame($expectedIds, $actualIds);
		self::assertCount(5, array_unique($actualIds));
	}

	public function testCapabilitiesAdvertiseTheMetricRegistryDefinitions(): void {
		$response = (new Client([
			'base_uri' => 'http://localhost/',
			'http_errors' => false,
		]))->request('GET', 'ocs/v2.php/cloud/capabilities', [
			'auth' => [self::$userA, self::PASSWORD],
			'headers' => [
				'Accept' => 'application/json',
				'OCS-APIRequest' => 'true',
			],
		]);

		self::assertSame(200, $response->getStatusCode());
		$data = $this->ocsData($response);
		$health = $data['capabilities']['health'];
		self::assertSame(['2'], $health['apiVersions']);
		self::assertContains('entries', $health['features']);
		self::assertSame(MetricService::getModuleKeys(), $health['modules']);

		$metrics = array_column($health['metrics'], null, 'metricKey');
		self::assertSame(MetricService::getModuleKeys(), array_keys($metrics));

		foreach (['stress', 'energy', 'mood'] as $metricKey) {
			self::assertSame('journal', $metrics[$metricKey]['category']);
			self::assertSame('scale', $metrics[$metricKey]['valueType']);
			self::assertSame(1, $metrics[$metricKey]['minimum']);
			self::assertSame(5, $metrics[$metricKey]['maximum']);
			self::assertNull($metrics[$metricKey]['allowedOptions']);
			self::assertSame('average', $metrics[$metricKey]['aggregation']);
		}

		self::assertSame('event', $metrics['hydration']['valueType']);
		self::assertNull($metrics['hydration']['minimum']);
		self::assertNull($metrics['hydration']['maximum']);
		self::assertSame([
			'small_glass',
			'large_glass',
			'coffee',
			'cappuccino',
			'espresso',
			'double_espresso',
			'latte_macchiato',
			'cafe_au_lait',
			'tea',
			'other',
		], $metrics['hydration']['allowedOptions']);
		self::assertSame('count', $metrics['hydration']['aggregation']);

		self::assertSame('event', $metrics['break']['valueType']);
		self::assertNull($metrics['break']['minimum']);
		self::assertNull($metrics['break']['maximum']);
		self::assertSame([
			'short',
			'regular',
			'short_walk',
			'long_walk',
			'mindfulness',
			'fresh_air',
		], $metrics['break']['allowedOptions']);
		self::assertSame('count', $metrics['break']['aggregation']);
	}

	/**
	 * @param array<string, mixed> $overrides
	 * @return array<string, mixed>
	 */
	private function validEntry(array $overrides = []): array {
		return array_replace([
			'metricKey' => 'stress',
			'numericValue' => 3,
			'optionValue' => null,
			'context' => 'manual',
			'recordedAt' => '2026-08-17T12:00:00Z',
			'note' => null,
		], $overrides);
	}

	/**
	 * @param array<string, mixed> $overrides
	 */
	private function createAs(string $userId, array $overrides = []): ResponseInterface {
		return $this->requestAs($userId, 'POST', 'entries', [
			'json' => $this->validEntry($overrides),
		]);
	}

	/**
	 * @param array<string, mixed> $overrides
	 */
	private function updateAs(string $userId, int $id, array $overrides = []): ResponseInterface {
		$request = array_replace([
			'numericValue' => 3,
			'optionValue' => null,
			'context' => 'manual',
			'recordedAt' => '2026-08-17T12:00:00Z',
			'note' => null,
		], $overrides);

		return $this->requestAs($userId, 'PUT', 'entries/' . $id, ['json' => $request]);
	}

	/**
	 * @param array<string, mixed> $query
	 * @return array{entries: list<array<string, mixed>>, nextCursor: string|null}
	 */
	private function listAs(string $userId, array $query = []): array {
		$response = $this->requestAs($userId, 'GET', 'entries', ['query' => $query]);
		self::assertSame(200, $response->getStatusCode());
		/** @var array{entries: list<array<string, mixed>>, nextCursor: string|null} $data */
		$data = $this->ocsData($response);
		return $data;
	}

	/**
	 * @param array<string, mixed> $options
	 */
	private function requestAs(string $userId, string $method, string $path, array $options = []): ResponseInterface {
		$options['auth'] = [$userId, self::PASSWORD];
		return self::$http->request($method, $path, $options);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function ocsData(ResponseInterface $response): array {
		$data = $this->ocsDataValue($response);
		self::assertIsArray($data);
		return $data;
	}

	private function ocsDataValue(ResponseInterface $response): mixed {
		/** @var array{ocs: array{data: array<string, mixed>}} $decoded */
		$decoded = json_decode((string)$response->getBody(), true, 16, JSON_THROW_ON_ERROR);
		return $decoded['ocs']['data'];
	}

	/**
	 * @return list<int>
	 */
	private function createEntriesDirectly(string $userId, int $count, string $recordedAt): array {
		$ids = [];
		$base = new DateTimeImmutable($recordedAt);
		for ($index = 0; $index < $count; $index++) {
			$entry = self::$entryService->create(
				$userId,
				'stress',
				($index % 5) + 1,
				null,
				'manual',
				$base->format(DATE_RFC3339),
				null,
			);
			$ids[] = $entry['id'];
		}

		return $ids;
	}

	private static function deleteTestEntries(): void {
		$qb = self::$db->getQueryBuilder();
		$qb->delete('health_entries')
			->where($qb->expr()->orX(
				$qb->expr()->eq('user_id', $qb->createNamedParameter(self::$userA, IQueryBuilder::PARAM_STR)),
				$qb->expr()->eq('user_id', $qb->createNamedParameter(self::$userB, IQueryBuilder::PARAM_STR)),
			));
		$qb->executeStatement();
	}
}
