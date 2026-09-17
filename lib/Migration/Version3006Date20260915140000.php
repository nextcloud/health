<?php

declare(strict_types=1);

namespace OCA\Health\Migration;

use Closure;
use DateTimeImmutable;
use DateTimeZone;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDateTimeZone;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Converts legacy single daily Kilocalories values into timestamped measurements.
 *
 * @psalm-suppress UnusedClass Migration is discovered by Nextcloud's lifecycle.
 */
class Version3006Date20260915140000 extends SimpleMigrationStep {
	private DateTimeZone $utc;

	/** @psalm-suppress PossiblyUnusedMethod Instantiated through Nextcloud dependency injection. */
	public function __construct(
		private IDBConnection $db,
		private IDateTimeZone $dateTimeZone,
	) {
		$this->utc = new DateTimeZone('UTC');
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$select = $this->db->getQueryBuilder();
		$select->select('id', 'user_id', 'numeric_value', 'local_date', 'created_at', 'updated_at')
			->from('health_daily_values')
			->where($select->expr()->eq('metric_key', $select->createNamedParameter('kilocalories', IQueryBuilder::PARAM_STR)))
			->orderBy('id', 'ASC');
		$rows = $select->executeQuery()->fetchAllAssociative();

		foreach ($rows as $row) {
			$this->migrateRow($row);
		}
	}

	/** @param array<string, mixed> $row */
	private function migrateRow(array $row): void {
		$id = $this->requiredInt($row, 'id');
		$userId = $this->requiredString($row, 'user_id');
		$localDate = $this->requiredString($row, 'local_date');
		$numericValue = $this->requiredString($row, 'numeric_value');
		$operationId = $this->operationId($id);
		if ($this->measurementExists($userId, $operationId)) {
			return;
		}

		$recordedAt = (new DateTimeImmutable($localDate . ' 12:00:00', $this->dateTimeZone->getTimeZone(false, $userId)))->setTimezone($this->utc);
		$insert = $this->db->getQueryBuilder();
		$insert->insert('health_measurements')->values([
			'user_id' => $insert->createNamedParameter($userId, IQueryBuilder::PARAM_STR),
			'metric_key' => $insert->createNamedParameter('kilocalories', IQueryBuilder::PARAM_STR),
			'numeric_value' => $insert->createNamedParameter($numericValue, IQueryBuilder::PARAM_STR),
			'group_id' => $insert->createNamedParameter(null, IQueryBuilder::PARAM_STR),
			'context' => $insert->createNamedParameter('manual', IQueryBuilder::PARAM_STR),
			'source' => $insert->createNamedParameter('web', IQueryBuilder::PARAM_STR),
			'client_operation_id' => $insert->createNamedParameter($operationId, IQueryBuilder::PARAM_STR),
			'recorded_at' => $insert->createNamedParameter($recordedAt, IQueryBuilder::PARAM_DATETIME_IMMUTABLE),
			'created_at' => $insert->createNamedParameter($this->dateTime($row, 'created_at'), IQueryBuilder::PARAM_DATETIME_IMMUTABLE),
			'updated_at' => $insert->createNamedParameter($this->dateTime($row, 'updated_at'), IQueryBuilder::PARAM_DATETIME_IMMUTABLE),
			'note' => $insert->createNamedParameter(null, IQueryBuilder::PARAM_STR),
		]);
		$insert->executeStatement();
	}

	private function measurementExists(string $userId, string $operationId): bool {
		$query = $this->db->getQueryBuilder();
		$query->select('id')->from('health_measurements')
			->where($query->expr()->eq('user_id', $query->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->andWhere($query->expr()->eq('metric_key', $query->createNamedParameter('kilocalories', IQueryBuilder::PARAM_STR)))
			->andWhere($query->expr()->eq('client_operation_id', $query->createNamedParameter($operationId, IQueryBuilder::PARAM_STR)))
			->setMaxResults(1);
		return $query->executeQuery()->fetchOne() !== false;
	}

	/** @param array<string, mixed> $row */
	private function dateTime(array $row, string $key): DateTimeImmutable {
		$value = $row[$key] ?? null;
		if ($value instanceof DateTimeImmutable) {
			return $value;
		}
		if (!is_string($value)) {
			throw new \RuntimeException('Legacy Kilocalories timestamp is missing.');
		}
		return new DateTimeImmutable($value, $this->utc);
	}

	/** @param array<string, mixed> $row */
	private function requiredString(array $row, string $key): string {
		$value = $row[$key] ?? null;
		if (!is_string($value) || $value === '') {
			throw new \RuntimeException('Legacy Kilocalories data is incomplete.');
		}
		return $value;
	}

	/** @param array<string, mixed> $row */
	private function requiredInt(array $row, string $key): int {
		if (array_key_exists($key, $row) && is_int($row[$key])) {
			return $row[$key];
		}
		if (array_key_exists($key, $row) && is_string($row[$key]) && ctype_digit($row[$key])) {
			return (int)$row[$key];
		}
		throw new \RuntimeException('Legacy Kilocalories record identifier is invalid.');
	}

	private function operationId(int $dailyValueId): string {
		$hash = hash('sha256', 'health-kilocalories-daily-value-' . $dailyValueId);
		return substr($hash, 0, 8) . '-' . substr($hash, 8, 4) . '-4' . substr($hash, 13, 3) . '-8' . substr($hash, 17, 3) . '-' . substr($hash, 20, 12);
	}
}
