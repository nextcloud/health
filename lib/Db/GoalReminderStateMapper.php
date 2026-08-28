<?php

declare(strict_types=1);

namespace OCA\Health\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @extends QBMapper<GoalReminderState> */
class GoalReminderStateMapper extends QBMapper {
	/** @psalm-suppress PossiblyUnusedMethod Instantiated through Nextcloud dependency injection. */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'health_goal_reminder_state', GoalReminderState::class);
	}
	/** @psalm-suppress PossiblyUnusedReturnValue Persistence result is intentionally ignored after notification state is recorded. */
	public function create(GoalReminderState $state): GoalReminderState {
		return $this->insert($state);
	}
	/** @psalm-suppress PossiblyUnusedReturnValue Persistence result is intentionally ignored after notification state is recorded. */
	public function updateForGoal(GoalReminderState $state): GoalReminderState {
		return $this->update($state);
	}

	/** @throws DoesNotExistException|MultipleObjectsReturnedException */
	public function findForGoalPeriod(int $goalId, string $periodKey): GoalReminderState {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('goal_id', $qb->createNamedParameter($goalId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('period_key', $qb->createNamedParameter($periodKey, IQueryBuilder::PARAM_STR)));
		return $this->findEntity($qb);
	}
}
