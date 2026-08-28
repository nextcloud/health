<?php

declare(strict_types=1);

namespace OCA\Health\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @extends QBMapper<Goal> */
class GoalMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'health_goals', Goal::class);
	}
	public function create(Goal $goal): Goal {
		return $this->insert($goal);
	}
	public function updateForUser(Goal $goal): Goal {
		return $this->update($goal);
	}

	/** @throws DoesNotExistException|MultipleObjectsReturnedException */
	public function findForUser(int $id, string $userId): Goal {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
		return $this->findEntity($qb);
	}

	/** @throws DoesNotExistException|MultipleObjectsReturnedException */
	public function findForIdentity(string $userId, string $targetKey, string $period): Goal {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('target_key', $qb->createNamedParameter($targetKey, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('period', $qb->createNamedParameter($period, IQueryBuilder::PARAM_STR)));
		return $this->findEntity($qb);
	}

	/** @return list<Goal> */
	public function findAllForUser(string $userId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->orderBy('created_at', 'ASC')->addOrderBy('id', 'ASC');
		return $this->findEntities($qb);
	}

	/** @return list<Goal> */
	public function findManageableForUser(string $userId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->isNull('retired_at'))
			->orderBy('created_at', 'ASC')->addOrderBy('id', 'ASC');
		return $this->findEntities($qb);
	}

	/** @return list<Goal> */
	public function findActiveWithReminders(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('active', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
			->andWhere($qb->expr()->eq('reminders_enabled', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)));
		return $this->findEntities($qb);
	}
}
