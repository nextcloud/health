<?php

declare(strict_types=1);

namespace OCA\Health\Service;

use DateTimeImmutable;
use DateTimeZone;
use OCA\Health\Db\DailyValueMapper;
use OCA\Health\Db\EntryMapper;
use OCA\Health\Db\Goal;
use OCA\Health\Db\GoalMapper;
use OCA\Health\Db\GoalRevision;
use OCA\Health\Db\GoalRevisionMapper;
use OCA\Health\Db\MeasurementMapper;
use OCA\Health\Exception\InvalidEntryException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\IDateTimeZone;

/** Calculates all goal progress from owner-scoped Health source data. */
class GoalProgressService {
	private DateTimeZone $utc;

	public function __construct(
		private GoalMapper $goalMapper,
		private GoalRevisionMapper $goalRevisionMapper,
		private GoalTargetRegistry $goalTargetRegistry,
		private EntryMapper $entryMapper,
		private DailyValueMapper $dailyValueMapper,
		private MeasurementMapper $measurementMapper,
		private IDateTimeZone $dateTimeZone,
	) {
		$this->utc = new DateTimeZone('UTC');
	}

	/** @return list<array<string, mixed>> */
	public function list(string $userId, mixed $period, mixed $date = null): array {
		$selection = $this->selection($userId, $period, $date);
		$result = [];
		foreach ($this->goalMapper->findAllForUser($userId) as $goal) {
			if ($goal->getPeriod() !== $selection['period']) {
				continue;
			}
			$progress = $this->evaluateGoal($userId, $goal, $selection);
			if ($progress !== null) {
				$result[] = $this->publicResult($progress);
			}
		}
		return $result;
	}

	/**
	 * @return array<string, mixed>|null Internal evaluation includes lastActivityAt for reminder policy decisions.
	 */
	public function evaluateCurrentGoal(string $userId, Goal $goal, ?DateTimeImmutable $now = null): ?array {
		$localNow = ($now ?? new DateTimeImmutable('now', $this->utc))->setTimezone($this->dateTimeZone->getTimeZone(false, $userId));
		$selection = $this->selection($userId, $goal->getPeriod(), $goal->getPeriod() === 'long_term' ? null : $localNow->format('Y-m-d'), $now);
		return $this->evaluateGoal($userId, $goal, $selection);
	}

	/** @return array{period: 'day'|'week'|'month'|'long_term', periodStart: DateTimeImmutable, periodEnd: DateTimeImmutable|null, periodStartKey: string, periodEndKey: string|null, periodKey: string, closed: bool, current: bool, timezone: DateTimeZone} */
	public function selection(string $userId, mixed $period, mixed $date = null, ?DateTimeImmutable $now = null): array {
		if (!is_string($period) || !in_array($period, ['day', 'week', 'month', 'long_term'], true)) {
			throw new InvalidEntryException('Unsupported goal period.');
		}
		$timezone = $this->dateTimeZone->getTimeZone(false, $userId);
		$localNow = ($now ?? new DateTimeImmutable('now', $this->utc))->setTimezone($timezone);
		$today = $localNow->setTime(0, 0);

		if ($period === 'long_term') {
			if ($date !== null) {
				throw new InvalidEntryException('Long-term goals do not take a date.');
			}
			return [
				'period' => 'long_term',
				'periodStart' => $today,
				'periodEnd' => null,
				'periodStartKey' => $today->format('Y-m-d'),
				'periodEndKey' => null,
				'periodKey' => 'long_term',
				'closed' => false,
				'current' => true,
				'timezone' => $timezone,
			];
		}

		if (!is_string($date) || !$this->isDate($date)) {
			throw new InvalidEntryException('date must be a valid YYYY-MM-DD date.');
		}
		$requested = (new DateTimeImmutable($date, $timezone))->setTime(0, 0);
		$start = match ($period) {
			'day' => $requested,
			'week' => $requested->modify('monday this week')->setTime(0, 0),
			'month' => $requested->modify('first day of this month')->setTime(0, 0),
		};
		$currentStart = match ($period) {
			'day' => $today,
			'week' => $today->modify('monday this week')->setTime(0, 0),
			'month' => $today->modify('first day of this month')->setTime(0, 0),
		};
		if ($start > $currentStart) {
			throw new InvalidEntryException('Goal periods cannot be in the future.');
		}
		$end = match ($period) {
			'day' => $start->modify('+1 day'),
			'week' => $start->modify('+1 week'),
			'month' => $start->modify('+1 month'),
		};
		return [
			'period' => $period,
			'periodStart' => $start,
			'periodEnd' => $end,
			'periodStartKey' => $start->format('Y-m-d'),
			'periodEndKey' => $end->format('Y-m-d'),
			'periodKey' => $period === 'month' ? $start->format('Y-m') : $start->format('Y-m-d'),
			'closed' => $end <= $localNow,
			'current' => $start == $currentStart,
			'timezone' => $timezone,
		];
	}

	/** @param array{period: string, periodStart: DateTimeImmutable, periodEnd: DateTimeImmutable|null, periodStartKey: string, periodEndKey: string|null, periodKey: string, closed: bool, current: bool, timezone: DateTimeZone} $selection @return array<string, mixed>|null */
	private function evaluateGoal(string $userId, Goal $goal, array $selection): ?array {
		try {
			$revision = $this->goalRevisionMapper->findForGoalPeriod($goal->getId(), $selection['periodStartKey']);
		} catch (DoesNotExistException|MultipleObjectsReturnedException) {
			return null;
		}
		$target = $this->goalTargetRegistry->getDefinition($goal->getTargetKey());
		$targetValue = (float)$revision->getTargetValue();
		$fromUtc = $selection['periodStart']->setTimezone($this->utc);
		$toUtc = $selection['periodEnd']?->setTimezone($this->utc);
		$currentValue = 0.0;
		$observedValue = null;
		$lastActivityAt = null;

		if ($target['category'] === 'journal') {
			if ($toUtc === null) {
				throw new \LogicException('Journal goal must have a finite period.');
			}
			/** @var list<string> $options */
			$options = $target['options'] ?? [];
			$currentValue = (float)$this->entryMapper->countForUserMetricOptionsRange($userId, $target['metricKey'], $options, $fromUtc, $toUtc);
			$lastActivityAt = $this->entryMapper->findLatestForUserMetricOptionsSince($userId, $target['metricKey'], $options, $fromUtc);
		} elseif ($target['category'] === 'daily_value') {
			if ($target['kind'] === 'latest_value') {
				$value = $this->dailyValueMapper->findLatestForUserMetricOnOrBefore($userId, $target['metricKey'], $selection['periodStartKey']);
				$currentValue = $value === null ? null : (float)$value->getNumericValue();
				$lastActivityAt = $value?->getUpdatedAt();
			} else {
				if ($selection['periodEndKey'] === null) {
					throw new \LogicException('Daily value goal must have a finite period.');
				}
				$currentValue = $this->dailyValueMapper->sumForUserMetricDateRange($userId, $target['metricKey'], $selection['periodStartKey'], $selection['periodEndKey']);
				$value = $this->dailyValueMapper->findLatestForUserMetricDateRange($userId, $target['metricKey'], $selection['periodStartKey'], $selection['periodEndKey']);
				$lastActivityAt = $value?->getUpdatedAt();
			}
		} elseif ($target['kind'] === 'threshold_occurrence') {
			if ($toUtc === null) {
				throw new \LogicException('Threshold goal must have a finite period.');
			}
			$measurements = $this->measurementMapper->findForUserMetricRange($userId, $target['metricKey'], $fromUtc, $toUtc);
			$values = array_map(static fn ($item): float => (float)$item->getNumericValue(), $measurements);
			if ($values !== []) {
				$observedValue = $revision->getComparator() === 'lte' ? min($values) : max($values);
				$lastActivityAt = $measurements[0]->getRecordedAt();
			}
			$currentValue = (float)count(array_filter($values, fn (float $value): bool => $this->matches($value, $targetValue, $revision->getComparator())));
		} else {
			if ($toUtc === null) {
				throw new \LogicException('Measurement count goal must have a finite period.');
			}
			$currentValue = (float)$this->measurementMapper->countBloodPressureGroupsForUserRange($userId, $fromUtc, $toUtc);
			$measurements = $this->measurementMapper->findForUserMetricRange($userId, 'blood_pressure_systolic', $fromUtc, $toUtc);
			$lastActivityAt = $measurements === [] ? null : $measurements[0]->getRecordedAt();
		}

		$status = $this->status($goal, $target, $revision, $currentValue, $targetValue, $selection['closed'], $selection['current']);
		$minimumStyle = $revision->getComparator() === 'gte' && $target['kind'] !== 'latest_value';
		return [
			'goalId' => $goal->getId(),
			'targetKey' => $goal->getTargetKey(),
			'metricKey' => $target['metricKey'],
			'period' => $goal->getPeriod(),
			'periodStart' => $selection['periodStartKey'],
			'periodEnd' => $selection['periodEndKey'],
			'periodKey' => $selection['periodKey'],
			'active' => $goal->isActive(),
			'remindersEnabled' => $goal->isRemindersEnabled(),
			'comparator' => $revision->getComparator(),
			'targetValue' => $targetValue,
			'currentValue' => $currentValue,
			'observedValue' => $observedValue,
			'progressRatio' => $minimumStyle && $currentValue !== null ? min($currentValue / $targetValue, 1.0) : null,
			'remaining' => $currentValue === null ? null : $targetValue - $currentValue,
			'status' => $status,
			'effectiveFrom' => $revision->getEffectiveFrom(),
			'lastActivityAt' => $lastActivityAt,
		];
	}

	/** @param array<string, mixed> $target */
	private function status(Goal $goal, array $target, GoalRevision $revision, ?float $currentValue, float $targetValue, bool $closed, bool $current): string {
		if (!$goal->isActive() && $current) {
			return 'paused';
		}
		if ($target['kind'] === 'threshold_occurrence') {
			if (($currentValue ?? 0.0) >= 1.0) {
				return 'reached';
			}
			return $closed ? 'not_reached' : 'in_progress';
		}
		if ($currentValue === null) {
			return $closed ? 'not_reached' : 'in_progress';
		}
		if ($revision->getComparator() === 'gte') {
			if ($currentValue >= $targetValue) {
				return 'reached';
			}
			return $closed ? 'not_reached' : 'in_progress';
		}
		if ($currentValue > $targetValue) {
			return 'exceeded';
		}
		return $closed ? 'reached' : 'within_limit';
	}

	private function matches(float $value, float $targetValue, string $comparator): bool {
		return $comparator === 'gte' ? $value >= $targetValue : $value <= $targetValue;
	}

	private function isDate(string $date): bool {
		return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $date, $matches) === 1 && checkdate((int)$matches[2], (int)$matches[3], (int)$matches[1]);
	}

	/** @param array<string, mixed> $progress @return array<string, mixed> */
	private function publicResult(array $progress): array {
		unset($progress['lastActivityAt']);
		return $progress;
	}
}
