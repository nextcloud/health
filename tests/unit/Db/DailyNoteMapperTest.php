<?php

declare(strict_types=1);

namespace OCA\Health\Tests\Unit\Db;

use DateTimeImmutable;
use DateTimeZone;
use OCA\Health\Db\DailyNote;
use OCA\Health\Db\DailyNoteMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use PHPUnit\Framework\TestCase;

class DailyNoteMapperTest extends TestCase {
	private IDBConnection $db;
	private DailyNoteMapper $mapper;
	private string $userA;
	private string $userB;

	protected function setUp(): void {
		$this->db = \OC::$server->get(IDBConnection::class);
		$this->mapper = new DailyNoteMapper($this->db);
		$suffix = bin2hex(random_bytes(5));
		$this->userA = 'health-search-a-' . $suffix;
		$this->userB = 'health-search-b-' . $suffix;
	}

	protected function tearDown(): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete('health_daily_notes')->where($qb->expr()->orX(
			$qb->expr()->eq('user_id', $qb->createNamedParameter($this->userA, IQueryBuilder::PARAM_STR)),
			$qb->expr()->eq('user_id', $qb->createNamedParameter($this->userB, IQueryBuilder::PARAM_STR)),
		));
		$qb->executeStatement();
	}

	public function testSearchUsesCaseInsensitiveAndTokenMatchingAndEscapesLikeCharacters(): void {
		$this->save($this->userA, '2026-08-20', 'A Calm afternoon WALK with tag_value and 100% focus.');
		$this->save($this->userA, '2026-08-19', 'A calm afternoon without movement.');
		$this->save($this->userA, '2026-08-18', 'A tagXvalue must not match an underscore search.');

		$caseInsensitive = $this->mapper->findMatchingForUser($this->userA, ['calm', 'walk'], null, 10);
		$literalUnderscore = $this->mapper->findMatchingForUser($this->userA, ['tag_value'], null, 10);
		$literalPercent = $this->mapper->findMatchingForUser($this->userA, ['100%'], null, 10);

		self::assertSame(['2026-08-20'], array_map(static fn (DailyNote $note): string => $note->getLocalDate(), $caseInsensitive));
		self::assertSame(['2026-08-20'], array_map(static fn (DailyNote $note): string => $note->getLocalDate(), $literalUnderscore));
		self::assertSame(['2026-08-20'], array_map(static fn (DailyNote $note): string => $note->getLocalDate(), $literalPercent));
	}

	public function testSearchScopesResultsToOwnerAndUsesLocalDateCursor(): void {
		$this->save($this->userA, '2026-08-20', 'Private calm note.');
		$this->save($this->userA, '2026-08-19', 'Older calm note.');
		$this->save($this->userB, '2026-08-21', 'Another user calm note.');

		$firstPage = $this->mapper->findMatchingForUser($this->userA, ['calm'], null, 1);
		$nextPage = $this->mapper->findMatchingForUser($this->userA, ['calm'], $firstPage[0]->getLocalDate(), 1);

		self::assertSame('2026-08-20', $firstPage[0]->getLocalDate());
		self::assertSame('2026-08-19', $nextPage[0]->getLocalDate());
		self::assertCount(1, $firstPage);
		self::assertCount(1, $nextPage);
	}

	private function save(string $userId, string $date, string $content): void {
		$now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
		$note = new DailyNote();
		$note->setUserId($userId);
		$note->setLocalDate($date);
		$note->setContent($content);
		$note->setCreatedAt($now);
		$note->setUpdatedAt($now);
		$this->mapper->create($note);
	}
}
