<?php

declare(strict_types=1);

namespace OCA\Health\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @extends QBMapper<DailyNote> */
class DailyNoteMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'health_daily_notes', DailyNote::class);
	}

	/**
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 */
	public function findForUserAndDate(string $userId, string $localDate): DailyNote {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->tableName)
			->where($qb->expr()->eq(
				'user_id',
				$qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR),
			))
			->andWhere($qb->expr()->eq(
				'local_date',
				$qb->createNamedParameter($localDate, IQueryBuilder::PARAM_STR),
			));

		return $this->findEntity($qb);
	}

	public function create(DailyNote $dailyNote): DailyNote {
		return $this->insert($dailyNote);
	}

	public function updateDailyNote(DailyNote $dailyNote): DailyNote {
		return $this->update($dailyNote);
	}

	/**
	 * Finds Daily Notes owned by one user that contain every token. Results are
	 * ordered by their local journal date so a date cursor remains stable while
	 * newer notes are added.
	 *
	 * @param list<string> $tokens
	 * @return list<DailyNote>
	 */
	public function findMatchingForUser(string $userId, array $tokens, ?string $cursor, int $limit): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));

		foreach ($tokens as $token) {
			$qb->andWhere($qb->expr()->iLike(
				'content',
				$qb->createNamedParameter('%' . $this->db->escapeLikeParameter($token) . '%', IQueryBuilder::PARAM_STR),
			));
		}

		if ($cursor !== null) {
			$qb->andWhere($qb->expr()->lt('local_date', $qb->createNamedParameter($cursor, IQueryBuilder::PARAM_STR)));
		}

		$qb->orderBy('local_date', 'DESC')
			->addOrderBy('id', 'DESC')
			->setMaxResults($limit);

		return $this->findEntities($qb);
	}
}
