<?php

declare(strict_types=1);

namespace OCA\Health\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @extends QBMapper<SavedStatisticsView> */
class SavedStatisticsViewMapper extends QBMapper {
	/** @psalm-suppress PossiblyUnusedMethod Instantiated through Nextcloud dependency injection. */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'health_statistics_views', SavedStatisticsView::class);
	}

	public function create(SavedStatisticsView $view): SavedStatisticsView {
		return $this->insert($view);
	}
	public function updateForUser(SavedStatisticsView $view): SavedStatisticsView {
		return $this->update($view);
	}
	public function deleteForUser(SavedStatisticsView $view): void {
		$this->delete($view);
	}

	/** @throws DoesNotExistException|MultipleObjectsReturnedException */
	public function findForUser(int $id, string $userId): SavedStatisticsView {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
		return $this->findEntity($qb);
	}

	/** @return list<SavedStatisticsView> */
	public function findAllForUser(string $userId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->orderBy('created_at', 'ASC')->addOrderBy('id', 'ASC');
		return $this->findEntities($qb);
	}
}
