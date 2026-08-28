<?php

declare(strict_types=1);

namespace OCA\Health\Db;

use DateTimeImmutable;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @extends QBMapper<Measurement> */
class MeasurementMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'health_measurements', Measurement::class);
	}
	public function create(Measurement $measurement): Measurement {
		return $this->insert($measurement);
	}
	public function updateForUser(Measurement $measurement): Measurement {
		return $this->update($measurement);
	}
	public function deleteForUser(Measurement $measurement): void {
		$this->delete($measurement);
	}

	/** @throws DoesNotExistException|MultipleObjectsReturnedException */
	public function findForUser(int $id, string $userId): Measurement {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
		return $this->findEntity($qb);
	}

	/** @return list<Measurement> */
	public function findForUserRange(string $userId, ?DateTimeImmutable $from, ?DateTimeImmutable $to): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
		if ($from !== null) {
			$qb->andWhere($qb->expr()->gte('recorded_at', $qb->createNamedParameter($from, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)));
		}
		if ($to !== null) {
			$qb->andWhere($qb->expr()->lt('recorded_at', $qb->createNamedParameter($to, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)));
		}
		$qb->orderBy('recorded_at', 'DESC')->addOrderBy('id', 'DESC');
		return $this->findEntities($qb);
	}

	/** @return list<Measurement> */
	public function findForUserGroup(string $userId, string $groupId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('group_id', $qb->createNamedParameter($groupId, IQueryBuilder::PARAM_STR)))
			->orderBy('id', 'ASC');
		return $this->findEntities($qb);
	}

	/** @return list<Measurement> */
	public function findForUserMetricRange(string $userId, string $metricKey, DateTimeImmutable $from, DateTimeImmutable $to): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('metric_key', $qb->createNamedParameter($metricKey, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->gte('recorded_at', $qb->createNamedParameter($from, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
			->andWhere($qb->expr()->lt('recorded_at', $qb->createNamedParameter($to, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
			->orderBy('recorded_at', 'DESC')->addOrderBy('id', 'DESC');
		return $this->findEntities($qb);
	}

	/**
	 * @param list<string> $metricKeys
	 * @return list<Measurement>
	 */
	public function findForUserMetricsRange(string $userId, array $metricKeys, DateTimeImmutable $from, DateTimeImmutable $to): array {
		if ($metricKeys === []) {
			return [];
		}

		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->in('metric_key', $qb->createNamedParameter($metricKeys, IQueryBuilder::PARAM_STR_ARRAY)))
			->andWhere($qb->expr()->gte('recorded_at', $qb->createNamedParameter($from, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
			->andWhere($qb->expr()->lt('recorded_at', $qb->createNamedParameter($to, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
			->orderBy('recorded_at', 'ASC')->addOrderBy('id', 'ASC');
		return $this->findEntities($qb);
	}

	public function countBloodPressureGroupsForUserRange(string $userId, DateTimeImmutable $from, DateTimeImmutable $to): int {
		$qb = $this->db->getQueryBuilder();
		$qb->select('group_id')->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->in('metric_key', $qb->createNamedParameter(['blood_pressure_systolic', 'blood_pressure_diastolic'], IQueryBuilder::PARAM_STR_ARRAY)))
			->andWhere($qb->expr()->isNotNull('group_id'))
			->andWhere($qb->expr()->gte('recorded_at', $qb->createNamedParameter($from, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
			->andWhere($qb->expr()->lt('recorded_at', $qb->createNamedParameter($to, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)));
		$groups = $qb->executeQuery()->fetchFirstColumn();
		return count(array_unique(array_map('strval', $groups)));
	}
}
