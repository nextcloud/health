<?php

declare(strict_types=1);

namespace OCA\Health\Tests\Api;

use GuzzleHttp\Client;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUserManager;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ConfigurationApiTest extends TestCase {
	private const PASSWORD = 'health-api-test-password';

	private static Client $http;
	private static IDBConnection $db;
	private static IUserManager $userManager;
	private static string $userA;
	private static string $userB;

	public static function setUpBeforeClass(): void {
		$suffix = bin2hex(random_bytes(5));
		self::$userA = 'health-config-a-' . $suffix;
		self::$userB = 'health-config-b-' . $suffix;
		self::$db = \OC::$server->get(IDBConnection::class);
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
		self::deleteTestConfiguration();
		foreach ([self::$userA, self::$userB] as $userId) {
			$user = self::$userManager->get($userId);
			if ($user !== null) {
				$user->delete();
			}
		}
	}

	protected function setUp(): void {
		self::deleteTestConfiguration();
	}

	public function testUnauthenticatedAccessIsRejected(): void {
		self::assertSame(401, self::$http->request('GET', 'configuration')->getStatusCode());
		self::assertSame(401, self::$http->request('PUT', 'configuration', ['json' => ['profile' => []]])->getStatusCode());
	}

	public function testProfileIsSavedInCanonicalFormAndScopedToTheAuthenticatedUser(): void {
		$response = $this->requestAs(self::$userA, 'PUT', 'configuration', [
			'json' => [
				'profile' => [
					'height' => 70,
					'heightUnit' => 'in',
					'dateOfBirth' => '2000-02-29',
					'growthReferenceSex' => 'female',
				],
			],
		]);

		self::assertSame(200, $response->getStatusCode());
		$profile = $this->ocsData($response)['profile'];
		self::assertEqualsWithDelta(177.8, $profile['heightCm'], 0.000001);
		self::assertSame('in', $profile['heightDisplayUnit']);
		self::assertSame('2000-02-29', $profile['dateOfBirth']);
		self::assertSame('female', $profile['growthReferenceSex']);

		$otherProfile = $this->ocsData($this->requestAs(self::$userB, 'GET', 'configuration'))['profile'];
		self::assertNull($otherProfile['heightCm']);
		self::assertNull($otherProfile['dateOfBirth']);
		self::assertNull($otherProfile['growthReferenceSex']);
	}

	public function testPartialProfileUpdateRetainsHeightAndCanClearOptionalFields(): void {
		$this->requestAs(self::$userA, 'PUT', 'configuration', [
			'json' => ['profile' => [
				'height' => 180,
				'heightUnit' => 'cm',
				'dateOfBirth' => '2001-01-02',
				'growthReferenceSex' => 'male',
			]],
		]);

		$response = $this->requestAs(self::$userA, 'PUT', 'configuration', [
			'json' => ['profile' => [
				'dateOfBirth' => null,
				'growthReferenceSex' => null,
			]],
		]);

		self::assertSame(200, $response->getStatusCode());
		$profile = $this->ocsData($response)['profile'];
		self::assertEquals(180.0, $profile['heightCm']);
		self::assertSame('cm', $profile['heightDisplayUnit']);
		self::assertNull($profile['dateOfBirth']);
		self::assertNull($profile['growthReferenceSex']);
	}

	public function testInvalidProfileValuesAreRejected(): void {
		foreach ([
			['dateOfBirth' => '2001-02-29'],
			['dateOfBirth' => '02/01/2001'],
			['growthReferenceSex' => 'other'],
			['heightUnit' => 'm'],
		] as $profile) {
			$response = $this->requestAs(self::$userA, 'PUT', 'configuration', ['json' => ['profile' => $profile]]);
			self::assertSame(400, $response->getStatusCode());
		}
	}

	public function testDailyNoteSearchIsPrivateByDefaultAndPersistedPerUser(): void {
		self::assertFalse($this->ocsData($this->requestAs(self::$userA, 'GET', 'configuration'))['searchDailyNotes']);
		self::assertFalse($this->ocsData($this->requestAs(self::$userB, 'GET', 'configuration'))['searchDailyNotes']);

		$response = $this->requestAs(self::$userA, 'PUT', 'configuration', ['json' => ['searchDailyNotes' => true]]);
		self::assertSame(200, $response->getStatusCode());
		self::assertTrue($this->ocsData($response)['searchDailyNotes']);
		self::assertTrue($this->ocsData($this->requestAs(self::$userA, 'GET', 'configuration'))['searchDailyNotes']);
		self::assertFalse($this->ocsData($this->requestAs(self::$userB, 'GET', 'configuration'))['searchDailyNotes']);
	}

	public function testInvalidDailyNoteSearchPreferenceIsRejected(): void {
		$response = $this->requestAs(self::$userA, 'PUT', 'configuration', ['json' => ['searchDailyNotes' => 'yes']]);
		self::assertSame(400, $response->getStatusCode());
	}

	public function testNewDailyMetricsCanBeEnabledIndependentlyAndRemainOwnerScoped(): void {
		$response = $this->requestAs(self::$userA, 'PUT', 'configuration', ['json' => ['metrics' => [
			'kilocalories' => ['enabled' => true],
			'fruit' => ['enabled' => true],
		]]]);
		self::assertSame(200, $response->getStatusCode());
		$metrics = $this->ocsData($response)['metrics'];
		self::assertTrue($metrics['kilocalories']['enabled']);
		self::assertSame('kcal', $metrics['kilocalories']['displayUnit']);
		self::assertTrue($metrics['fruit']['enabled']);
		self::assertSame('pieces', $metrics['fruit']['displayUnit']);
		$other = $this->ocsData($this->requestAs(self::$userB, 'GET', 'configuration'))['metrics'];
		self::assertFalse($other['kilocalories']['enabled']);
		self::assertFalse($other['fruit']['enabled']);
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

	private static function deleteTestConfiguration(): void {
		foreach (['health_user_metrics', 'health_user_settings'] as $table) {
			$qb = self::$db->getQueryBuilder();
			$qb->delete($table)->where($qb->expr()->orX(
				$qb->expr()->eq('user_id', $qb->createNamedParameter(self::$userA, IQueryBuilder::PARAM_STR)),
				$qb->expr()->eq('user_id', $qb->createNamedParameter(self::$userB, IQueryBuilder::PARAM_STR)),
			));
			$qb->executeStatement();
		}
	}
}
