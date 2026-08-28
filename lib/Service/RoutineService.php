<?php

declare(strict_types=1);

namespace OCA\Health\Service;

use OCA\Health\Exception\InvalidEntryException;
use OCP\IDBConnection;

class RoutineService {
	public function __construct(
		private ConfigurationService $configurationService,
		private EntryService $entryService,
		private MeasurementService $measurementService,
		private DailyValueService $dailyValueService,
		private IDBConnection $db,
	) {
	}

	/** @return array{createdEntries: list<array<string, mixed>>, createdMeasurements: list<array<string, mixed>>, updatedDailyValues: list<array<string, mixed>>} */
	public function submit(string $userId, string $context, mixed $date, mixed $recordedAt, mixed $journalMetrics, mixed $measurements, mixed $dailyValues): array {
		if (!in_array($context, ['checkin', 'checkout'], true) || !is_string($date) || !is_array($journalMetrics) || !is_array($measurements) || !is_array($dailyValues)) {
			throw new InvalidEntryException('Invalid routine submission.');
		}
		$configuration = $this->configurationService->get($userId)['metrics'];
		$this->db->beginTransaction();
		try {
			$result = ['createdEntries' => [], 'createdMeasurements' => [], 'updatedDailyValues' => []];
			foreach ($journalMetrics as $item) {
				if (!is_array($item) || !is_string($item['metricKey'] ?? null)) { throw new InvalidEntryException('Invalid journal metric.'); }
				$this->assertRoutineMetric($configuration, $item['metricKey'], $context);
				$result['createdEntries'][] = $this->entryService->create($userId, $item['metricKey'], $item['numericValue'] ?? null, $item['optionValue'] ?? null, $context, $recordedAt, $item['note'] ?? null, 'web');
			}
			foreach ($measurements as $item) {
				if (!is_array($item) || !is_string($item['metricKey'] ?? null)) { throw new InvalidEntryException('Invalid measurement.'); }
				$this->assertRoutineMetric($configuration, $item['metricKey'], $context);
				$result['createdMeasurements'][] = $this->measurementService->create($userId, $item['metricKey'], $item['numericValue'] ?? null, $item['values'] ?? null, $item['unit'] ?? null, $recordedAt, $item['note'] ?? null, $context, 'web');
			}
			foreach ($dailyValues as $item) {
				if (!is_array($item) || !is_string($item['metricKey'] ?? null)) { throw new InvalidEntryException('Invalid daily value.'); }
				$this->assertRoutineMetric($configuration, $item['metricKey'], $context);
				$result['updatedDailyValues'][] = $this->dailyValueService->upsert($userId, $item['metricKey'], $date, $item['numericValue'] ?? null, $item['unit'] ?? null);
			}
			$this->db->commit();
			return $result;
		} catch (\Throwable $exception) {
			$this->db->rollBack();
			throw $exception;
		}
	}

	/** @param array<string, array{enabled: bool, checkInEnabled: bool, checkOutEnabled: bool, displayUnit: string|null}> $configuration */
	private function assertRoutineMetric(array $configuration, string $metricKey, string $context): void {
		$metric = $configuration[$metricKey] ?? null;
		if ($metric === null || !$metric['enabled'] || ($context === 'checkin' ? !$metric['checkInEnabled'] : !$metric['checkOutEnabled'])) {
			throw new InvalidEntryException('Metric is not configured for this routine.');
		}
	}
}
