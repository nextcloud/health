<?php

declare(strict_types=1);

namespace OCA\Health\Db;

use DateTimeImmutable;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @extends QBMapper<Entry>
 */
class EntryMapper extends QBMapper {
	/** @psalm-suppress PossiblyUnusedMethod Instantiated by Nextcloud dependency injection. */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'health_entries', Entry::class);
	}

	public function create(Entry $entry): Entry {
		return $this->insert($entry);
	}

	/**
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 */
	public function findForUser(int $id, string $userId): Entry {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->tableName)
			->where($qb->expr()->eq(
				'id',
				$qb->createNamedParameter($id, IQueryBuilder::PARAM_INT),
			))
			->andWhere($qb->expr()->eq(
				'user_id',
				$qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR),
			));

		return $this->findEntity($qb);
	}

	/** @throws DoesNotExistException|MultipleObjectsReturnedException */
	public function findForUserOperation(string $userId, string $operationId): Entry {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('client_operation_id', $qb->createNamedParameter($operationId, IQueryBuilder::PARAM_STR)));
		return $this->findEntity($qb);
	}

	public function updateForUser(Entry $entry): Entry {
		return $this->update($entry);
	}

	public function deleteForUser(Entry $entry): void {
		$this->delete($entry);
	}

	/**
	 * @return list<Entry>
	 */
	public function findPageForUser(
		string $userId,
		?string $metricKey,
		?DateTimeImmutable $from,
		?DateTimeImmutable $to,
		?DateTimeImmutable $cursorRecordedAt,
		?int $cursorId,
		int $fetchLimit,
	): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->tableName)
			->where($qb->expr()->eq(
				'user_id',
				$qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR),
			));

		if ($metricKey !== null) {
			$qb->andWhere($qb->expr()->eq(
				'metric_key',
				$qb->createNamedParameter($metricKey, IQueryBuilder::PARAM_STR),
			));
		}

		if ($from !== null) {
			$qb->andWhere($qb->expr()->gte(
				'recorded_at',
				$qb->createNamedParameter($from, IQueryBuilder::PARAM_DATETIME_IMMUTABLE),
			));
		}

		if ($to !== null) {
			$qb->andWhere($qb->expr()->lt(
				'recorded_at',
				$qb->createNamedParameter($to, IQueryBuilder::PARAM_DATETIME_IMMUTABLE),
			));
		}

		if ($cursorRecordedAt !== null && $cursorId !== null) {
			$cursorTimeBefore = $qb->expr()->lt(
				'recorded_at',
				$qb->createNamedParameter($cursorRecordedAt, IQueryBuilder::PARAM_DATETIME_IMMUTABLE),
			);
			$cursorSameTime = $qb->expr()->andX(
				$qb->expr()->eq(
					'recorded_at',
					$qb->createNamedParameter($cursorRecordedAt, IQueryBuilder::PARAM_DATETIME_IMMUTABLE),
				),
				$qb->expr()->lt(
					'id',
					$qb->createNamedParameter($cursorId, IQueryBuilder::PARAM_INT),
				),
			);
			$qb->andWhere($qb->expr()->orX($cursorTimeBefore, $cursorSameTime));
		}

		$qb->orderBy('recorded_at', 'DESC')
			->addOrderBy('id', 'DESC')
			->setMaxResults($fetchLimit);

		return $this->findEntities($qb);
	}

	/**
	 * Fetches all entries for a known set of journal metrics in an owner-scoped time range.
	 *
	 * Statistics uses this intentionally unpaginated internal query after constraining both
	 * the owner, metric set, and a fixed preset range. Public API pagination remains in
	 * {@see findPageForUser()}.
	 *
	 * @param list<string> $metricKeys
	 * @return list<Entry>
	 */
	public function findForUserMetricRange(
		string $userId,
		array $metricKeys,
		DateTimeImmutable $from,
		DateTimeImmutable $to,
	): array {
		if ($metricKeys === []) {
			return [];
		}

		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->tableName)
			->where($qb->expr()->eq(
				'user_id',
				$qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR),
			))
			->andWhere($qb->expr()->in(
				'metric_key',
				$qb->createNamedParameter($metricKeys, IQueryBuilder::PARAM_STR_ARRAY),
			))
			->andWhere($qb->expr()->gte(
				'recorded_at',
				$qb->createNamedParameter($from, IQueryBuilder::PARAM_DATETIME_IMMUTABLE),
			))
			->andWhere($qb->expr()->lt(
				'recorded_at',
				$qb->createNamedParameter($to, IQueryBuilder::PARAM_DATETIME_IMMUTABLE),
			))
			->orderBy('recorded_at', 'ASC')
			->addOrderBy('id', 'ASC');

		return $this->findEntities($qb);
	}

	/** @param list<string> $options */
	public function countForUserMetricOptionsRange(string $userId, string $metricKey, array $options, DateTimeImmutable $from, DateTimeImmutable $to): int {
		$qb = $this->db->getQueryBuilder();
		$qb->select($qb->func()->count('id'))
			->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('metric_key', $qb->createNamedParameter($metricKey, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->in('option_value', $qb->createNamedParameter($options, IQueryBuilder::PARAM_STR_ARRAY)))
			->andWhere($qb->expr()->gte('recorded_at', $qb->createNamedParameter($from, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
			->andWhere($qb->expr()->lt('recorded_at', $qb->createNamedParameter($to, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)));
		return (int)$qb->executeQuery()->fetchOne();
	}

	/** @param list<string> $options */
	public function findLatestForUserMetricOptionsSince(string $userId, string $metricKey, array $options, DateTimeImmutable $since): ?DateTimeImmutable {
		$qb = $this->db->getQueryBuilder();
		$qb->select('recorded_at')
			->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('metric_key', $qb->createNamedParameter($metricKey, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->in('option_value', $qb->createNamedParameter($options, IQueryBuilder::PARAM_STR_ARRAY)))
			->andWhere($qb->expr()->gte('recorded_at', $qb->createNamedParameter($since, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
			->orderBy('recorded_at', 'DESC')->setMaxResults(1);
		/** @psalm-suppress MixedAssignment The database API exposes an untyped timestamp scalar, narrowed below. */
		$raw = $qb->executeQuery()->fetchOne();
		return is_string($raw) ? new DateTimeImmutable($raw) : null;
	}
}
