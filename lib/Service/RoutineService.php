<?php

declare(strict_types=1);

namespace OCA\Health\Service;

use OCA\Health\Exception\InvalidEntryException;
use OCP\IDBConnection;

/** @psalm-import-type HealthRoutineResult from \OCA\Health\ResponseDefinitions */
class RoutineService {
	/** @psalm-suppress PossiblyUnusedMethod Instantiated through Nextcloud dependency injection. */
	public function __construct(
		private ConfigurationService $configurationService,
		private EntryService $entryService,
		private MeasurementService $measurementService,
		private DailyValueService $dailyValueService,
		private IDBConnection $db,
	) {
	}

	/** @return HealthRoutineResult */
	public function submit(string $userId, string $context, mixed $date, mixed $recordedAt, mixed $journalMetrics, mixed $measurements, mixed $dailyValues): array {
		if (!in_array($context, ['checkin', 'checkout'], true) || !is_string($date)) {
			throw new InvalidEntryException('Invalid routine submission.');
		}
		if (!is_array($journalMetrics) || !is_array($measurements) || !is_array($dailyValues)) {
			throw new InvalidEntryException('Invalid routine submission.');
		}
		$configuration = $this->configurationService->get($userId)['metrics'];
		$journalMetrics = $this->normalizeJournalMetrics($journalMetrics);
		$measurements = $this->normalizeMeasurements($measurements);
		$dailyValues = $this->normalizeDailyValues($dailyValues);
		$this->db->beginTransaction();
		try {
			/** @var HealthRoutineResult $result */
			$result = ['createdEntries' => [], 'createdMeasurements' => [], 'updatedDailyValues' => []];
			foreach ($journalMetrics as $item) {
				$this->assertRoutineMetric($configuration, $item['metricKey'], $context);
				$result['createdEntries'][] = $this->entryService->create($userId, $item['metricKey'], $item['numericValue'], $item['optionValue'], $context, $recordedAt, $item['note'], 'web');
			}
			foreach ($measurements as $item) {
				$this->assertRoutineMetric($configuration, $item['metricKey'], $context);
				$result['createdMeasurements'][] = $this->measurementService->create($userId, $item['metricKey'], $item['numericValue'], $item['values'], $item['unit'], $recordedAt, $item['note'], $context, 'web');
			}
			foreach ($dailyValues as $item) {
				$this->assertRoutineMetric($configuration, $item['metricKey'], $context);
				$result['updatedDailyValues'][] = $this->dailyValueService->upsert($userId, $item['metricKey'], $date, $item['numericValue'], $item['unit']);
			}
			$this->db->commit();
			return $result;
		} catch (\Throwable $exception) {
			$this->db->rollBack();
			throw $exception;
		}
	}

	/** @return list<array{metricKey: string, numericValue: mixed, optionValue: mixed, note: mixed}> */
	private function normalizeJournalMetrics(array $items): array {
		$result = [];
		foreach ($items as $item) {
			if (!is_array($item) || !array_key_exists('metricKey', $item) || !is_string($item['metricKey'])) {
				throw new InvalidEntryException('Invalid journal metric.');
			}
			$result[] = ['metricKey' => $item['metricKey'], 'numericValue' => $item['numericValue'] ?? null, 'optionValue' => $item['optionValue'] ?? null, 'note' => $item['note'] ?? null];
		}
		return $result;
	}

	/** @return list<array{metricKey: string, numericValue: mixed, values: mixed, unit: mixed, note: mixed}> */
	private function normalizeMeasurements(array $items): array {
		$result = [];
		foreach ($items as $item) {
			if (!is_array($item) || !array_key_exists('metricKey', $item) || !is_string($item['metricKey'])) {
				throw new InvalidEntryException('Invalid measurement.');
			}
			$result[] = ['metricKey' => $item['metricKey'], 'numericValue' => $item['numericValue'] ?? null, 'values' => $item['values'] ?? null, 'unit' => $item['unit'] ?? null, 'note' => $item['note'] ?? null];
		}
		return $result;
	}

	/** @return list<array{metricKey: string, numericValue: mixed, unit: mixed}> */
	private function normalizeDailyValues(array $items): array {
		$result = [];
		foreach ($items as $item) {
			if (!is_array($item) || !array_key_exists('metricKey', $item) || !is_string($item['metricKey'])) {
				throw new InvalidEntryException('Invalid daily value.');
			}
			$result[] = ['metricKey' => $item['metricKey'], 'numericValue' => $item['numericValue'] ?? null, 'unit' => $item['unit'] ?? null];
		}
		return $result;
	}

	/** @param array<string, array{enabled: bool, checkInEnabled: bool, checkOutEnabled: bool, displayUnit: string|null}> $configuration */
	private function assertRoutineMetric(array $configuration, string $metricKey, string $context): void {
		$metric = $configuration[$metricKey] ?? null;
		if ($metric === null || !$metric['enabled'] || ($context === 'checkin' ? !$metric['checkInEnabled'] : !$metric['checkOutEnabled'])) {
			throw new InvalidEntryException('Metric is not configured for this routine.');
		}
	}
}
