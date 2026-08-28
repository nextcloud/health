<?php

declare(strict_types=1);

namespace OCA\Health\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class ConfigurationMapper {
	public function __construct(
		private IDBConnection $db,
	) {
	}

	/** @return array<string, array{enabled: bool, checkInEnabled: bool, checkOutEnabled: bool, displayUnit: string|null}> */
	public function findMetricsForUser(string $userId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('metric_key', 'enabled', 'check_in_enabled', 'check_out_enabled', 'display_unit')->from('health_user_metrics')
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
		$rows = $qb->executeQuery()->fetchAllAssociative();
		$result = [];
		foreach ($rows as $row) {
			$result[(string)$row['metric_key']] = [
				'enabled' => (bool)$row['enabled'],
				'checkInEnabled' => (bool)$row['check_in_enabled'],
				'checkOutEnabled' => (bool)$row['check_out_enabled'],
				'displayUnit' => $row['display_unit'] === null ? null : (string)$row['display_unit'],
			];
		}
		return $result;
	}

	/** @param array{enabled: bool, checkInEnabled: bool, checkOutEnabled: bool, displayUnit: string|null} $configuration */
	public function saveMetricForUser(string $userId, string $metricKey, array $configuration): void {
		$qb = $this->db->getQueryBuilder();
		$qb->select('id')->from('health_user_metrics')
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('metric_key', $qb->createNamedParameter($metricKey, IQueryBuilder::PARAM_STR)));
		$id = $qb->executeQuery()->fetchOne();
		if ($id === false) {
			$insert = $this->db->getQueryBuilder();
			$insert->insert('health_user_metrics')->values([
				'user_id' => $insert->createNamedParameter($userId, IQueryBuilder::PARAM_STR),
				'metric_key' => $insert->createNamedParameter($metricKey, IQueryBuilder::PARAM_STR),
				'enabled' => $insert->createNamedParameter($configuration['enabled'], IQueryBuilder::PARAM_BOOL),
				'check_in_enabled' => $insert->createNamedParameter($configuration['checkInEnabled'], IQueryBuilder::PARAM_BOOL),
				'check_out_enabled' => $insert->createNamedParameter($configuration['checkOutEnabled'], IQueryBuilder::PARAM_BOOL),
				'display_unit' => $insert->createNamedParameter($configuration['displayUnit'], IQueryBuilder::PARAM_STR),
			]);
			$insert->executeStatement();
			return;
		}
		$update = $this->db->getQueryBuilder();
		$update->update('health_user_metrics')->set('enabled', $update->createNamedParameter($configuration['enabled'], IQueryBuilder::PARAM_BOOL))
			->set('check_in_enabled', $update->createNamedParameter($configuration['checkInEnabled'], IQueryBuilder::PARAM_BOOL))
			->set('check_out_enabled', $update->createNamedParameter($configuration['checkOutEnabled'], IQueryBuilder::PARAM_BOOL))
			->set('display_unit', $update->createNamedParameter($configuration['displayUnit'], IQueryBuilder::PARAM_STR))
			->where($update->expr()->eq('id', $update->createNamedParameter((int)$id, IQueryBuilder::PARAM_INT)))->executeStatement();
	}

	/** @return array{heightCm: float|null, heightDisplayUnit: string, dateOfBirth: string|null, growthReferenceSex: string|null}|null */
	public function findProfileForUser(string $userId): ?array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('height_cm', 'height_display_unit', 'date_of_birth', 'growth_reference_sex')->from('health_user_settings')
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
		$row = $qb->executeQuery()->fetchAssociative();
		if ($row === false) {
			return null;
		}
		return ['heightCm' => $row['height_cm'] === null ? null : (float)$row['height_cm'], 'heightDisplayUnit' => (string)$row['height_display_unit'], 'dateOfBirth' => $row['date_of_birth'] === null ? null : (string)$row['date_of_birth'], 'growthReferenceSex' => $row['growth_reference_sex'] === null ? null : (string)$row['growth_reference_sex']];
	}

	public function saveProfileForUser(string $userId, ?float $heightCm, string $heightDisplayUnit, ?string $dateOfBirth, ?string $growthReferenceSex): void {
		$existing = $this->findProfileForUser($userId);
		if ($existing === null) {
			$qb = $this->db->getQueryBuilder();
			$qb->insert('health_user_settings')->values([
				'user_id' => $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR),
				'height_cm' => $qb->createNamedParameter($heightCm, IQueryBuilder::PARAM_STR),
				'height_display_unit' => $qb->createNamedParameter($heightDisplayUnit, IQueryBuilder::PARAM_STR),
				'date_of_birth' => $qb->createNamedParameter($dateOfBirth, IQueryBuilder::PARAM_STR),
				'growth_reference_sex' => $qb->createNamedParameter($growthReferenceSex, IQueryBuilder::PARAM_STR),
				'search_daily_notes' => $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL),
			])->executeStatement();
			return;
		}
		$qb = $this->db->getQueryBuilder();
		$qb->update('health_user_settings')->set('height_cm', $qb->createNamedParameter($heightCm, IQueryBuilder::PARAM_STR))
			->set('height_display_unit', $qb->createNamedParameter($heightDisplayUnit, IQueryBuilder::PARAM_STR))
			->set('date_of_birth', $qb->createNamedParameter($dateOfBirth, IQueryBuilder::PARAM_STR))
			->set('growth_reference_sex', $qb->createNamedParameter($growthReferenceSex, IQueryBuilder::PARAM_STR))
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))->executeStatement();
	}

	public function findSearchDailyNotesForUser(string $userId): bool {
		$qb = $this->db->getQueryBuilder();
		$qb->select('search_daily_notes')->from('health_user_settings')
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
		$value = $qb->executeQuery()->fetchOne();
		return $value !== false && (bool)$value;
	}

	public function saveSearchDailyNotesForUser(string $userId, bool $enabled): void {
		$existing = $this->findProfileForUser($userId);
		if ($existing === null) {
			$qb = $this->db->getQueryBuilder();
			$qb->insert('health_user_settings')->values([
				'user_id' => $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR),
				'height_cm' => $qb->createNamedParameter(null, IQueryBuilder::PARAM_STR),
				'height_display_unit' => $qb->createNamedParameter('cm', IQueryBuilder::PARAM_STR),
				'date_of_birth' => $qb->createNamedParameter(null, IQueryBuilder::PARAM_STR),
				'growth_reference_sex' => $qb->createNamedParameter(null, IQueryBuilder::PARAM_STR),
				'search_daily_notes' => $qb->createNamedParameter($enabled, IQueryBuilder::PARAM_BOOL),
			])->executeStatement();
			return;
		}

		$qb = $this->db->getQueryBuilder();
		$qb->update('health_user_settings')->set('search_daily_notes', $qb->createNamedParameter($enabled, IQueryBuilder::PARAM_BOOL))
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))->executeStatement();
	}
}
