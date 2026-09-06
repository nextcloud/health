<?php

declare(strict_types=1);

namespace OCA\Health\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @extends QBMapper<DailyValue> */
class DailyValueMapper extends QBMapper {
	/** @psalm-suppress PossiblyUnusedMethod Instantiated through Nextcloud dependency injection. */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'health_daily_values', DailyValue::class);
	}

	/** @throws DoesNotExistException|MultipleObjectsReturnedException */
	public function findForUserDateMetric(string $userId, string $date, string $metricKey): DailyValue {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('local_date', $qb->createNamedParameter($date, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('metric_key', $qb->createNamedParameter($metricKey, IQueryBuilder::PARAM_STR)));
		return $this->findEntity($qb);
	}

	/** @return list<DailyValue> */
	public function findForUserDate(string $userId, string $date): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('local_date', $qb->createNamedParameter($date, IQueryBuilder::PARAM_STR)));
		return $this->findEntities($qb);
	}

	/**
	 * @param list<string> $metricKeys
	 * @return list<DailyValue>
	 */
	public function findForUserMetricDateRange(string $userId, array $metricKeys, string $fromDate, string $toDate): array {
		if ($metricKeys === []) {
			return [];
		}

		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->in('metric_key', $qb->createNamedParameter($metricKeys, IQueryBuilder::PARAM_STR_ARRAY)))
			->andWhere($qb->expr()->gte('local_date', $qb->createNamedParameter($fromDate, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->lt('local_date', $qb->createNamedParameter($toDate, IQueryBuilder::PARAM_STR)))
			->orderBy('local_date', 'ASC')
			->addOrderBy('metric_key', 'ASC')
			->addOrderBy('id', 'ASC');

		return $this->findEntities($qb);
	}

	public function sumForUserMetricDateRange(string $userId, string $metricKey, string $fromDate, string $toDate): float {
		$qb = $this->db->getQueryBuilder();
		$qb->select($qb->func()->sum('numeric_value'))
			->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('metric_key', $qb->createNamedParameter($metricKey, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->gte('local_date', $qb->createNamedParameter($fromDate, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->lt('local_date', $qb->createNamedParameter($toDate, IQueryBuilder::PARAM_STR)));
		/** @psalm-suppress MixedAssignment The database API exposes an untyped scalar, narrowed below. */
		$raw = $qb->executeQuery()->fetchOne();
		if (is_int($raw) || is_float($raw)) {
			return (float)$raw;
		}
		return is_string($raw) && is_numeric($raw) ? (float)$raw : 0.0;
	}

	public function findLatestForUserMetricOnOrBefore(string $userId, string $metricKey, string $date): ?DailyValue {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('metric_key', $qb->createNamedParameter($metricKey, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->lte('local_date', $qb->createNamedParameter($date, IQueryBuilder::PARAM_STR)))
			->orderBy('local_date', 'DESC')->addOrderBy('id', 'DESC')->setMaxResults(1);
		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException|MultipleObjectsReturnedException) {
			return null;
		}
	}

	public function findFirstForUserMetricOnOrAfter(string $userId, string $metricKey, string $date, string $throughDate): ?DailyValue {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('metric_key', $qb->createNamedParameter($metricKey, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->gte('local_date', $qb->createNamedParameter($date, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->lte('local_date', $qb->createNamedParameter($throughDate, IQueryBuilder::PARAM_STR)))
			->orderBy('local_date', 'ASC')->addOrderBy('id', 'ASC')->setMaxResults(1);
		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException|MultipleObjectsReturnedException) {
			return null;
		}
	}

	public function findLatestForUserMetricDateRange(string $userId, string $metricKey, string $fromDate, string $toDate): ?DailyValue {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('metric_key', $qb->createNamedParameter($metricKey, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->gte('local_date', $qb->createNamedParameter($fromDate, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->lt('local_date', $qb->createNamedParameter($toDate, IQueryBuilder::PARAM_STR)))
			->orderBy('local_date', 'DESC')->addOrderBy('id', 'DESC')->setMaxResults(1);
		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException|MultipleObjectsReturnedException) {
			return null;
		}
	}

	public function create(DailyValue $value): DailyValue {
		return $this->insert($value);
	}
	public function updateForUser(DailyValue $value): DailyValue {
		return $this->update($value);
	}
	public function deleteForUser(DailyValue $value): void {
		$this->delete($value);
	}
}
