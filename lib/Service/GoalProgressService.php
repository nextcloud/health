<?php

declare(strict_types=1);

namespace OCA\Health\Service;

use DateTimeImmutable;
use DateTimeZone;
use OCA\Health\Db\DailyValueMapper;
use OCA\Health\Db\EntryMapper;
use OCA\Health\Db\Goal;
use OCA\Health\Db\GoalMapper;
use OCA\Health\Db\GoalRevisionMapper;
use OCA\Health\Db\MeasurementMapper;
use OCA\Health\Exception\InvalidEntryException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\IDateTimeZone;

/**
 * Calculates all goal progress from owner-scoped Health source data.
 *
 * @psalm-import-type HealthGoalProgress from \OCA\Health\ResponseDefinitions
 * @psalm-import-type HealthGoalTarget from \OCA\Health\ResponseDefinitions
 * @psalm-type GoalProgressInternal = array{
 *   goalId: int, targetKey: string, metricKey: string,
 *   period: 'day'|'week'|'month'|'long_term', periodStart: string, periodEnd: string|null,
 *   periodKey: string, active: bool, remindersEnabled: bool, comparator: 'gte'|'lte',
 *   targetValue: float, currentValue: float|null, observedValue: float|null,
 *   progressRatio: float|null, remaining: float|null,
 *   status: 'in_progress'|'reached'|'within_limit'|'exceeded'|'not_reached'|'paused',
 *   effectiveFrom: string, lastActivityAt: DateTimeImmutable|null
 * }
 */
class GoalProgressService {
	private DateTimeZone $utc;

	/** @psalm-suppress PossiblyUnusedMethod Instantiated through Nextcloud dependency injection. */
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

	/**
	 * @psalm-return list<HealthGoalProgress>
	 * @psalm-suppress MixedReturnTypeCoercion Psalm loses this fixed response shape while composing owner-scoped source results.
	 */
	public function list(string $userId, mixed $period, mixed $date = null): array {
		$selection = $this->selection($userId, $period, $date);
		/** @var list<HealthGoalProgress> $result */
		$result = [];
		foreach ($this->goalMapper->findAllForUser($userId) as $goal) {
			if ($goal->getPeriod() !== $selection['period']) {
				continue;
			}
			$progress = $this->evaluateGoal($userId, $goal, $selection);
			if ($progress !== null) {
				/** @psalm-suppress MixedArgumentTypeCoercion Psalm loses the internal fixed shape across the private evaluator boundary. */
				$result[] = $this->publicResult($progress);
			}
		}
		return $result;
	}

	/**
	 * @psalm-return GoalProgressInternal|null Internal evaluation includes lastActivityAt for reminder policy decisions.
	 * @psalm-suppress MixedReturnTypeCoercion Psalm loses this fixed internal shape while composing source results.
	 */
	public function evaluateCurrentGoal(string $userId, Goal $goal, ?DateTimeImmutable $now = null): ?array {
		$localNow = ($now ?? new DateTimeImmutable('now', $this->utc))->setTimezone($this->dateTimeZone->getTimeZone(false, $userId));
		$selection = $this->selection($userId, $goal->getPeriod(), $goal->getPeriod() === 'long_term' ? null : $localNow->format('Y-m-d'), $now);
		return $this->evaluateGoal($userId, $goal, $selection);
	}

	/** @psalm-return array{period: 'day'|'week'|'month'|'long_term', periodStart: DateTimeImmutable, periodEnd: DateTimeImmutable|null, periodStartKey: string, periodEndKey: string|null, periodKey: string, closed: bool, current: bool, timezone: DateTimeZone} */
	public function selection(string $userId, mixed $period, mixed $date = null, ?DateTimeImmutable $now = null): array {
		$period = match ($period) {
			'day' => 'day',
			'week' => 'week',
			'month' => 'month',
			'long_term' => 'long_term',
			default => throw new InvalidEntryException('Unsupported goal period.'),
		};
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

	/**
	 * @param array{period: 'day'|'week'|'month'|'long_term', periodStart: DateTimeImmutable, periodEnd: DateTimeImmutable|null, periodStartKey: string, periodEndKey: string|null, periodKey: string, closed: bool, current: bool, timezone: DateTimeZone} $selection
	 * @psalm-return GoalProgressInternal|null
	 * @psalm-suppress MixedReturnTypeCoercion Psalm loses this fixed internal shape while composing source results.
	 */
	private function evaluateGoal(string $userId, Goal $goal, array $selection): ?array {
		try {
			$revision = $this->goalRevisionMapper->findForGoalPeriod($goal->getId(), $selection['periodStartKey']);
		} catch (DoesNotExistException|MultipleObjectsReturnedException) {
			return null;
		}
		$target = $this->goalTargetRegistry->getDefinition($goal->getTargetKey());
		$comparator = match ($revision->getComparator()) {
			'gte' => 'gte',
			'lte' => 'lte',
			default => throw new \LogicException('Unsupported persisted goal comparator.'),
		};
		$targetValue = (float)$revision->getTargetValue();
		$fromUtc = $selection['periodStart']->setTimezone($this->utc);
		$toUtc = $selection['periodEnd']?->setTimezone($this->utc);
		/** @var float|null $observedValue */
		$observedValue = null;
		/** @var DateTimeImmutable|null $lastActivityAt */
		$lastActivityAt = null;

		if ($target['category'] === 'journal') {
			if ($toUtc === null) {
				throw new \LogicException('Journal goal must have a finite period.');
			}
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
				$observedValue = $comparator === 'lte' ? min($values) : max($values);
				$lastActivityAt = $measurements[0]->getRecordedAt();
			}
			$currentValue = (float)count(array_filter($values, fn (float $value): bool => $this->matches($value, $targetValue, $comparator)));
		} else {
			if ($toUtc === null) {
				throw new \LogicException('Measurement count goal must have a finite period.');
			}
			$currentValue = (float)$this->measurementMapper->countBloodPressureGroupsForUserRange($userId, $fromUtc, $toUtc);
			$measurements = $this->measurementMapper->findForUserMetricRange($userId, 'blood_pressure_systolic', $fromUtc, $toUtc);
			$lastActivityAt = $measurements === [] ? null : $measurements[0]->getRecordedAt();
		}

		$status = $this->status($goal, $target, $comparator, $currentValue, $targetValue, $selection['closed'], $selection['current']);
		$minimumStyle = $comparator === 'gte' && $target['kind'] !== 'latest_value';
		$goalId = $goal->getId();
		$progress = [
			'goalId' => $goalId,
			'targetKey' => $goal->getTargetKey(),
			'metricKey' => $target['metricKey'],
			'period' => $selection['period'],
			'periodStart' => $selection['periodStartKey'],
			'periodEnd' => $selection['periodEndKey'],
			'periodKey' => $selection['periodKey'],
			'active' => $goal->isActive(),
			'remindersEnabled' => $goal->isRemindersEnabled(),
			'comparator' => $comparator,
			'targetValue' => $targetValue,
			'currentValue' => $currentValue,
			'observedValue' => $observedValue,
			'progressRatio' => $minimumStyle && $currentValue !== null ? min($currentValue / $targetValue, 1.0) : null,
			'remaining' => $currentValue === null ? null : $targetValue - $currentValue,
			'status' => $status,
			'effectiveFrom' => $revision->getEffectiveFrom(),
			'lastActivityAt' => $lastActivityAt,
		];
		return $progress;
	}

	/**
	 * @param HealthGoalTarget $target
	 * @param 'gte'|'lte' $comparator
	 * @return 'in_progress'|'reached'|'within_limit'|'exceeded'|'not_reached'|'paused'
	 */
	private function status(Goal $goal, array $target, string $comparator, ?float $currentValue, float $targetValue, bool $closed, bool $current): string {
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
		if ($comparator === 'gte') {
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

	/** @param GoalProgressInternal $progress @psalm-return HealthGoalProgress */
	private function publicResult(array $progress): array {
		$public = [
			'goalId' => $progress['goalId'],
			'targetKey' => $progress['targetKey'],
			'metricKey' => $progress['metricKey'],
			'period' => $progress['period'],
			'periodStart' => $progress['periodStart'],
			'periodEnd' => $progress['periodEnd'],
			'periodKey' => $progress['periodKey'],
			'active' => $progress['active'],
			'remindersEnabled' => $progress['remindersEnabled'],
			'comparator' => $progress['comparator'],
			'targetValue' => $progress['targetValue'],
			'currentValue' => $progress['currentValue'],
			'observedValue' => $progress['observedValue'],
			'progressRatio' => $progress['progressRatio'],
			'remaining' => $progress['remaining'],
			'status' => $progress['status'],
			'effectiveFrom' => $progress['effectiveFrom'],
		];
		return $public;
	}
}
