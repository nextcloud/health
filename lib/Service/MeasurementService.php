<?php

declare(strict_types=1);

namespace OCA\Health\Service;

use DateTimeImmutable;
use DateTimeZone;
use OCA\Health\Db\Measurement;
use OCA\Health\Db\MeasurementMapper;
use OCA\Health\Exception\EntryNotFoundException;
use OCA\Health\Exception\InvalidEntryException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

class MeasurementService {
	private const CONTEXTS = ['manual', 'checkin', 'checkout'];
	private DateTimeZone $utc;

	public function __construct(
		private MeasurementMapper $measurementMapper,
		private MetricService $metricService,
		private UnitConversionService $unitConversionService,
	) {
		$this->utc = new DateTimeZone('UTC');
	}

	/** @return array<string, mixed> */
	public function create(string $userId, mixed $metricKey, mixed $numericValue, mixed $values, mixed $unit, mixed $recordedAt, mixed $note, mixed $context = 'manual', mixed $source = 'api'): array {
		$metricKey = $this->metricService->validateMeasurementMetricKey($metricKey);
		$timestamp = $this->parseTimestamp($recordedAt);
		$context = $this->validateContext($context);
		$source = $this->validateSource($source);
		$note = $this->validateNote($note);
		if ($metricKey === 'blood_pressure') {
			if (!is_array($values) || !array_key_exists('systolic', $values) || !array_key_exists('diastolic', $values)) {
				throw new InvalidEntryException('Blood pressure requires systolic and diastolic values.');
			}
			$groupId = bin2hex(random_bytes(16));
			$rows = [
				$this->newMeasurement($userId, 'blood_pressure_systolic', $this->unitConversionService->toCanonical('blood_pressure', $values['systolic'], $unit), $groupId, $context, $source, $timestamp, $note),
				$this->newMeasurement($userId, 'blood_pressure_diastolic', $this->unitConversionService->toCanonical('blood_pressure', $values['diastolic'], $unit), $groupId, $context, $source, $timestamp, $note),
			];
			return $this->formatBloodPressure($rows);
		}
		if ($values !== null) {
			throw new InvalidEntryException('Only blood pressure accepts composite values.');
		}
		$value = $this->unitConversionService->toCanonical($metricKey, $numericValue, $unit);
		return $this->formatSingle($this->newMeasurement($userId, $metricKey, $value, null, $context, $source, $timestamp, $note));
	}

	/** @return list<array<string, mixed>> */
	public function list(string $userId, mixed $from = null, mixed $to = null): array {
		$items = $this->measurementMapper->findForUserRange($userId, $from === null ? null : $this->parseTimestamp($from), $to === null ? null : $this->parseTimestamp($to));
		$result = [];
		$groups = [];
		foreach ($items as $item) {
			if ($item->getGroupId() === null) {
				$result[] = $this->formatSingle($item);
				continue;
			}
			$groups[$item->getGroupId()][] = $item;
		}
		foreach ($groups as $group) {
			$result[] = $this->formatBloodPressure($group);
		}
		usort($result, static fn (array $left, array $right): int => [$right['recordedAt'], $right['id']] <=> [$left['recordedAt'], $left['id']]);
		return $result;
	}

	/** @return array<string, mixed> */
	public function update(string $userId, int $id, mixed $numericValue, mixed $values, mixed $unit, mixed $recordedAt, mixed $note, mixed $context): array {
		$measurement = $this->findForUser($id, $userId);
		$timestamp = $this->parseTimestamp($recordedAt);
		$context = $this->validateContext($context);
		$note = $this->validateNote($note);
		if ($measurement->getGroupId() !== null) {
			if (!is_array($values) || !array_key_exists('systolic', $values) || !array_key_exists('diastolic', $values)) {
				throw new InvalidEntryException('Blood pressure requires systolic and diastolic values.');
			}
			$rows = $this->measurementMapper->findForUserGroup($userId, $measurement->getGroupId());
			if (count($rows) !== 2) {
				throw new EntryNotFoundException('Blood pressure measurement not found.');
			}
			foreach ($rows as $row) {
				$row->setNumericValue((string)$this->unitConversionService->toCanonical('blood_pressure', $row->getMetricKey() === 'blood_pressure_systolic' ? $values['systolic'] : $values['diastolic'], $unit));
				$row->setRecordedAt($timestamp);
				$row->setContext($context);
				$row->setNote($note);
				$row->setUpdatedAt(new DateTimeImmutable('now', $this->utc));
				$this->measurementMapper->updateForUser($row);
			}
			return $this->formatBloodPressure($rows);
		}
		$metricKey = $measurement->getMetricKey();
		$measurement->setNumericValue((string)$this->unitConversionService->toCanonical($metricKey, $numericValue, $unit));
		$measurement->setRecordedAt($timestamp);
		$measurement->setContext($context);
		$measurement->setNote($note);
		$measurement->setUpdatedAt(new DateTimeImmutable('now', $this->utc));
		return $this->formatSingle($this->measurementMapper->updateForUser($measurement));
	}

	public function delete(string $userId, int $id): void {
		$measurement = $this->findForUser($id, $userId);
		if ($measurement->getGroupId() === null) {
			$this->measurementMapper->deleteForUser($measurement);
			return;
		}
		foreach ($this->measurementMapper->findForUserGroup($userId, $measurement->getGroupId()) as $row) {
			$this->measurementMapper->deleteForUser($row);
		}
	}

	private function newMeasurement(string $userId, string $metricKey, float $value, ?string $groupId, string $context, string $source, DateTimeImmutable $recordedAt, ?string $note): Measurement {
		$now = new DateTimeImmutable('now', $this->utc);
		$item = new Measurement();
		$item->setUserId($userId);
		$item->setMetricKey($metricKey);
		$item->setNumericValue((string)$value);
		$item->setGroupId($groupId);
		$item->setContext($context);
		$item->setSource($source);
		$item->setRecordedAt($recordedAt);
		$item->setCreatedAt($now);
		$item->setUpdatedAt($now);
		$item->setNote($note);
		return $this->measurementMapper->create($item);
	}

	private function findForUser(int $id, string $userId): Measurement {
		try {
			return $this->measurementMapper->findForUser($id, $userId);
		} catch (DoesNotExistException|MultipleObjectsReturnedException $exception) {
			throw new EntryNotFoundException('Measurement not found.', 0, $exception);
		}
	}

	/** @return array<string, mixed> */
	private function formatSingle(Measurement $item): array {
		return ['id' => $item->getId(), 'metricKey' => $item->getMetricKey(), 'numericValue' => (float)$item->getNumericValue(), 'values' => null, 'context' => $item->getContext(), 'source' => $item->getSource(), 'recordedAt' => $item->getRecordedAt()->format('Y-m-d\TH:i:s\Z'), 'createdAt' => $item->getCreatedAt()->format('Y-m-d\TH:i:s\Z'), 'updatedAt' => $item->getUpdatedAt()->format('Y-m-d\TH:i:s\Z'), 'note' => $item->getNote()];
	}

	/** @param list<Measurement> $items @return array<string, mixed> */
	private function formatBloodPressure(array $items): array {
		$values = [];
		foreach ($items as $item) {
			$values[$item->getMetricKey() === 'blood_pressure_systolic' ? 'systolic' : 'diastolic'] = (float)$item->getNumericValue();
		}
		if (!isset($values['systolic'], $values['diastolic'])) {
			throw new \LogicException('Incomplete blood pressure group.');
		}
		$first = $items[0];
		return ['id' => $first->getId(), 'metricKey' => 'blood_pressure', 'numericValue' => null, 'values' => $values, 'context' => $first->getContext(), 'source' => $first->getSource(), 'recordedAt' => $first->getRecordedAt()->format('Y-m-d\TH:i:s\Z'), 'createdAt' => $first->getCreatedAt()->format('Y-m-d\TH:i:s\Z'), 'updatedAt' => $first->getUpdatedAt()->format('Y-m-d\TH:i:s\Z'), 'note' => $first->getNote()];
	}

	private function validateContext(mixed $context): string {
		if (!is_string($context) || !in_array($context, self::CONTEXTS, true)) {
			throw new InvalidEntryException('Invalid measurement context.');
		} return $context;
	}
	private function validateSource(mixed $source): string {
		if (!is_string($source) || !in_array($source, ['web', 'api', 'mobile', 'notification'], true)) {
			throw new InvalidEntryException('Invalid measurement source.');
		} return $source;
	}
	private function validateNote(mixed $note): ?string {
		if ($note === null) {
			return null;
		} if (!is_string($note) || mb_strlen($note) > 1000) {
			throw new InvalidEntryException('Measurement note must be plain text of at most 1000 characters.');
		} return $note;
	}
	private function parseTimestamp(mixed $value): DateTimeImmutable {
		if (!is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/D', $value) !== 1) {
			throw new InvalidEntryException('recordedAt must be an RFC3339 timestamp.');
		} try {
			return (new DateTimeImmutable($value))->setTimezone($this->utc);
		} catch (\Exception) {
			throw new InvalidEntryException('recordedAt must be an RFC3339 timestamp.');
		}
	}
}
