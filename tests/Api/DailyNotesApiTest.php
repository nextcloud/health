<?php

declare(strict_types=1);

namespace OCA\Health\Tests\Api;

use GuzzleHttp\Client;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUserManager;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class DailyNotesApiTest extends TestCase {
	private const PASSWORD = 'health-daily-note-test-password';

	private static Client $http;
	private static IDBConnection $db;
	private static IUserManager $userManager;
	private static string $userA;
	private static string $userB;

	public static function setUpBeforeClass(): void {
		$suffix = bin2hex(random_bytes(5));
		self::$userA = 'health-note-a-' . $suffix;
		self::$userB = 'health-note-b-' . $suffix;
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
		self::deleteTestNotes();
		foreach ([self::$userA, self::$userB] as $userId) {
			$user = self::$userManager->get($userId);
			if ($user !== null) {
				$user->delete();
			}
		}
	}

	protected function setUp(): void {
		self::deleteTestNotes();
	}

	public function testUnauthenticatedDailyNoteAccessIsRejected(): void {
		$get = self::$http->request('GET', 'daily-notes/2026-08-17');
		$put = self::$http->request('PUT', 'daily-notes/2026-08-17', [
			'json' => ['content' => 'Unauthenticated'],
		]);

		self::assertSame(401, $get->getStatusCode());
		self::assertSame(401, $put->getStatusCode());
	}

	public function testMissingNoteReturnsExplicitEmptyRepresentation(): void {
		$response = $this->getAs(self::$userA, '2026-08-17');

		self::assertSame(200, $response->getStatusCode());
		self::assertSame([
			'date' => '2026-08-17',
			'content' => null,
			'createdAt' => null,
			'updatedAt' => null,
		], $this->ocsData($response));
	}

	public function testOwnerCanCreateUpdateAndReadDailyNote(): void {
		$created = $this->putAs(self::$userA, '2026-08-17', 'A busy but good day.');

		self::assertSame(200, $created->getStatusCode());
		$createdData = $this->ocsData($created);
		self::assertSame('2026-08-17', $createdData['date']);
		self::assertSame('A busy but good day.', $createdData['content']);
		self::assertMatchesRegularExpression('/Z$/', $createdData['createdAt']);

		$updated = $this->putAs(self::$userA, '2026-08-17', 'A calmer afternoon.');
		self::assertSame(200, $updated->getStatusCode());
		self::assertSame('A calmer afternoon.', $this->ocsData($updated)['content']);

		$read = $this->getAs(self::$userA, '2026-08-17');
		self::assertSame('A calmer afternoon.', $this->ocsData($read)['content']);
		self::assertSame(1, $this->countNotesForUserAndDate(self::$userA, '2026-08-17'));
	}

	public function testUsersCannotReadOrOverwriteEachOthersDailyNote(): void {
		$this->putAs(self::$userA, '2026-08-17', 'Private note for user A.');

		$foreignRead = $this->getAs(self::$userB, '2026-08-17');
		self::assertSame(200, $foreignRead->getStatusCode());
		self::assertNull($this->ocsData($foreignRead)['content']);

		$foreignWrite = $this->requestAs(self::$userB, 'PUT', 'daily-notes/2026-08-17', [
			'json' => [
				'content' => 'User B note.',
				'userId' => self::$userA,
			],
		]);
		self::assertSame(200, $foreignWrite->getStatusCode());
		self::assertSame('Private note for user A.', $this->ocsData($this->getAs(self::$userA, '2026-08-17'))['content']);
		self::assertSame('User B note.', $this->ocsData($this->getAs(self::$userB, '2026-08-17'))['content']);
	}

	public function testChangingOneDateDoesNotChangeAnotherDate(): void {
		$this->putAs(self::$userA, '2026-08-16', 'Yesterday note.');
		$this->putAs(self::$userA, '2026-08-17', 'Today note.');

		self::assertSame('Yesterday note.', $this->ocsData($this->getAs(self::$userA, '2026-08-16'))['content']);
		self::assertSame('Today note.', $this->ocsData($this->getAs(self::$userA, '2026-08-17'))['content']);
	}

	public function testInvalidDateAndTooLongContentAreRejected(): void {
		$invalidDate = $this->putAs(self::$userA, '2026-02-30', 'Invalid date.');
		$tooLong = $this->putAs(self::$userA, '2026-08-17', str_repeat('a', 2001));

		self::assertSame(400, $invalidDate->getStatusCode());
		self::assertSame(400, $tooLong->getStatusCode());
	}

	public function testScriptLookingContentRemainsPlainText(): void {
		$content = '<script>alert("not executed")</script>';
		$response = $this->putAs(self::$userA, '2026-08-17', $content);

		self::assertSame(200, $response->getStatusCode());
		self::assertSame($content, $this->ocsData($response)['content']);
	}

	private function getAs(string $userId, string $date): ResponseInterface {
		return $this->requestAs($userId, 'GET', 'daily-notes/' . $date);
	}

	private function putAs(string $userId, string $date, string $content): ResponseInterface {
		return $this->requestAs($userId, 'PUT', 'daily-notes/' . $date, [
			'json' => ['content' => $content],
		]);
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

	private function countNotesForUserAndDate(string $userId, string $date): int {
		$qb = self::$db->getQueryBuilder();
		$qb->select($qb->func()->count('*'))
			->from('health_daily_notes')
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('local_date', $qb->createNamedParameter($date, IQueryBuilder::PARAM_STR)));

		return (int)$qb->executeQuery()->fetchOne();
	}

	private static function deleteTestNotes(): void {
		$qb = self::$db->getQueryBuilder();
		$qb->delete('health_daily_notes')
			->where($qb->expr()->orX(
				$qb->expr()->eq('user_id', $qb->createNamedParameter(self::$userA, IQueryBuilder::PARAM_STR)),
				$qb->expr()->eq('user_id', $qb->createNamedParameter(self::$userB, IQueryBuilder::PARAM_STR)),
			));
		$qb->executeStatement();
	}
}
