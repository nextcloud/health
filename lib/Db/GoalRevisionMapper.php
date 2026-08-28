<?php

declare(strict_types=1);

namespace OCA\Health\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @extends QBMapper<GoalRevision> */
class GoalRevisionMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'health_goal_revisions', GoalRevision::class);
	}
	public function create(GoalRevision $revision): GoalRevision {
		return $this->insert($revision);
	}
	public function updateForGoal(GoalRevision $revision): GoalRevision {
		return $this->update($revision);
	}

	/** @throws DoesNotExistException|MultipleObjectsReturnedException */
	public function findCurrentForGoal(int $goalId): GoalRevision {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('goal_id', $qb->createNamedParameter($goalId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->isNull('effective_to'));
		return $this->findEntity($qb);
	}

	/** @throws DoesNotExistException|MultipleObjectsReturnedException */
	public function findForGoalEffectiveFrom(int $goalId, string $effectiveFrom): GoalRevision {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('goal_id', $qb->createNamedParameter($goalId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('effective_from', $qb->createNamedParameter($effectiveFrom, IQueryBuilder::PARAM_STR)));
		return $this->findEntity($qb);
	}

	/** @throws DoesNotExistException|MultipleObjectsReturnedException */
	public function findForGoalPeriod(int $goalId, string $periodStart): GoalRevision {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('goal_id', $qb->createNamedParameter($goalId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->lte('effective_from', $qb->createNamedParameter($periodStart, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->orX(
				$qb->expr()->isNull('effective_to'),
				$qb->expr()->gte('effective_to', $qb->createNamedParameter($periodStart, IQueryBuilder::PARAM_STR)),
			))
			->orderBy('effective_from', 'DESC')->setMaxResults(1);
		return $this->findEntity($qb);
	}

	/**
	 * Returns revisions whose inclusive effective date interval overlaps the requested
	 * local-date range. The upper range boundary is exclusive.
	 *
	 * @return list<GoalRevision>
	 */
	public function findForGoalDateRange(int $goalId, string $fromDate, string $toDate): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('goal_id', $qb->createNamedParameter($goalId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->lt('effective_from', $qb->createNamedParameter($toDate, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->orX(
				$qb->expr()->isNull('effective_to'),
				$qb->expr()->gte('effective_to', $qb->createNamedParameter($fromDate, IQueryBuilder::PARAM_STR)),
			))
			->orderBy('effective_from', 'ASC')
			->addOrderBy('id', 'ASC');

		return $this->findEntities($qb);
	}
}
