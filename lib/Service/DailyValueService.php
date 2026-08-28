<?php

declare(strict_types=1);

namespace OCA\Health\Service;

use DateTimeImmutable;
use DateTimeZone;
use OCA\Health\Db\DailyValue;
use OCA\Health\Db\DailyValueMapper;
use OCA\Health\Exception\InvalidEntryException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

/** @psalm-suppress PossiblyUnusedMethod Instantiated through Nextcloud dependency injection. */
class DailyValueService {
	private DateTimeZone $utc;

	/** @psalm-suppress PossiblyUnusedMethod Instantiated through Nextcloud dependency injection. */
	public function __construct(
		private DailyValueMapper $dailyValueMapper,
		private MetricService $metricService,
		private UnitConversionService $unitConversionService,
		private ConfigurationService $configurationService,
	) {
		$this->utc = new DateTimeZone('UTC');
	}

	/** @return list<array{id: int, metricKey: string, numericValue: float, localDate: string, createdAt: string, updatedAt: string, bmi: float|null}> */
	public function list(string $userId, mixed $date): array {
		$date = $this->validateDate($date);
		$profile = $this->configurationService->get($userId)['profile'];
		$values = $this->dailyValueMapper->findForUserDate($userId, $date);
		$weight = null;
		foreach ($values as $value) {
			if ($value->getMetricKey() === 'weight') {
				$weight = (float)$value->getNumericValue();
			}
		}
		$bmi = $weight === null || $profile['heightCm'] === null || $profile['heightCm'] <= 0
			? null : $weight / (($profile['heightCm'] / 100) ** 2);
		return array_map(fn (DailyValue $value): array => $this->format($value, $bmi), $values);
	}

	/** @return array{id: int, metricKey: string, numericValue: float, localDate: string, createdAt: string, updatedAt: string, bmi: float|null} */
	public function upsert(string $userId, mixed $metricKey, mixed $date, mixed $numericValue, mixed $unit): array {
		$metricKey = $this->metricService->validateDailyValueMetricKey($metricKey);
		$date = $this->validateDate($date);
		$canonical = $this->metricService->validateDailyValueNumericValue($metricKey, $this->unitConversionService->toCanonical($metricKey, $numericValue, $unit));
		$now = new DateTimeImmutable('now', $this->utc);
		try {
			$value = $this->dailyValueMapper->findForUserDateMetric($userId, $date, $metricKey);
			$value->setNumericValue((string)$canonical);
			$value->setUpdatedAt($now);
			$value = $this->dailyValueMapper->updateForUser($value);
		} catch (DoesNotExistException|MultipleObjectsReturnedException) {
			$value = new DailyValue();
			$value->setUserId($userId);
			$value->setMetricKey($metricKey);
			$value->setNumericValue((string)$canonical);
			$value->setLocalDate($date);
			$value->setCreatedAt($now);
			$value->setUpdatedAt($now);
			$value = $this->dailyValueMapper->create($value);
		}
		$bmi = null;
		if ($metricKey === 'weight') {
			$heightCm = $this->configurationService->get($userId)['profile']['heightCm'];
			$bmi = $heightCm === null || $heightCm <= 0 ? null : $canonical / (($heightCm / 100) ** 2);
		}
		return $this->format($value, $bmi);
	}

	public function delete(string $userId, mixed $metricKey, mixed $date): void {
		try {
			$this->dailyValueMapper->deleteForUser($this->dailyValueMapper->findForUserDateMetric($userId, $this->validateDate($date), $this->metricService->validateDailyValueMetricKey($metricKey)));
		} catch (DoesNotExistException|MultipleObjectsReturnedException) {
			throw new InvalidEntryException('Daily value not found.');
		}
	}

	private function validateDate(mixed $date): string {
		if (!is_string($date) || preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $date, $matches) !== 1 || !checkdate((int)$matches[2], (int)$matches[3], (int)$matches[1])) {
			throw new InvalidEntryException('date must be a valid YYYY-MM-DD date.');
		}
		return $date;
	}

	/** @return array{id: int, metricKey: string, numericValue: float, localDate: string, createdAt: string, updatedAt: string, bmi: float|null} */
	private function format(DailyValue $value, ?float $bmi): array {
		return ['id' => $value->getId(), 'metricKey' => $value->getMetricKey(), 'numericValue' => (float)$value->getNumericValue(), 'localDate' => $value->getLocalDate(), 'createdAt' => $value->getCreatedAt()->format('Y-m-d\TH:i:s\Z'), 'updatedAt' => $value->getUpdatedAt()->format('Y-m-d\TH:i:s\Z'), 'bmi' => $value->getMetricKey() === 'weight' ? $bmi : null];
	}
}
