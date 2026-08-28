<?php

declare(strict_types=1);

namespace OCA\Health\Service;

use DateInterval;
use DateTimeImmutable;
use OCA\Health\Db\Goal;
use OCA\Health\Db\GoalReminderState;
use OCP\IDateTimeZone;

/**
 * Decides whether a reminder is useful without using Health interpretation or AI.
 *
 * @psalm-import-type GoalProgressInternal from GoalProgressService
 * @psalm-import-type HealthGoalTarget from \OCA\Health\ResponseDefinitions
 */
class GoalReminderEvaluationService {
	/** @psalm-suppress PossiblyUnusedMethod Instantiated through Nextcloud dependency injection. */
	public function __construct(
		private GoalTargetRegistry $goalTargetRegistry,
		private IDateTimeZone $dateTimeZone,
	) {
	}

	/** @param GoalProgressInternal $progress */
	public function reason(Goal $goal, array $progress, bool $metricEnabled, ?GoalReminderState $state, DateTimeImmutable $now): ?string {
		if (!$goal->isActive() || !$goal->isRemindersEnabled() || !$metricEnabled || ($progress['status'] ?? null) === 'paused') {
			return null;
		}
		$definition = $this->goalTargetRegistry->getDefinition($goal->getTargetKey());
		$timezone = $this->dateTimeZone->getTimeZone(false, $goal->getUserId());
		$localNow = $now->setTimezone($timezone);
		if (!$this->insideWindow($goal->getPeriod(), $localNow)) {
			return null;
		}

		$reason = $this->candidateReason($goal, $progress, $definition, $localNow);
		if ($reason === null || !$this->stateAllows($state, $reason, $goal->getPeriod(), $now)) {
			return null;
		}
		$lastActivity = $progress['lastActivityAt'] ?? null;
		if ($lastActivity instanceof DateTimeImmutable && $lastActivity >= $now->sub(new DateInterval('PT' . GoalReminderPolicy::RECENT_ACTIVITY_SECONDS . 'S'))) {
			return null;
		}
		return $reason;
	}

	/** @param GoalProgressInternal $progress @param HealthGoalTarget $definition */
	private function candidateReason(Goal $goal, array $progress, array $definition, DateTimeImmutable $localNow): ?string {
		$current = $progress['currentValue'] ?? null;
		$target = $progress['targetValue'];
		$comparator = $progress['comparator'];
		if ($definition['kind'] === 'threshold_occurrence' && ($progress['observedValue'] ?? null) === null) {
			return 'measurement_missing';
		}
		if ($definition['targetKey'] === 'job_satisfaction' && ($current === null || $current === 0.0)) {
			return 'measurement_missing';
		}
		if ($comparator === 'lte') {
			if ($current !== null && $current > $target) {
				return 'limit_exceeded';
			}
			if ($current !== null && $current === $target) {
				return 'limit_reached';
			}
			return null;
		}
		if (($progress['status'] ?? null) === 'reached') {
			return null;
		}
		if ($goal->getPeriod() === 'long_term') {
			$lastActivity = $progress['lastActivityAt'] ?? null;
			if ($lastActivity instanceof DateTimeImmutable) {
				return $lastActivity < $localNow->setTimezone($lastActivity->getTimezone())->sub(new DateInterval('PT' . GoalReminderPolicy::STALE_LONG_TERM_SECONDS . 'S')) ? 'stale_measurement' : null;
			}
			return $goal->getCreatedAt() < $localNow->setTimezone($goal->getCreatedAt()->getTimezone())->sub(new DateInterval('PT' . GoalReminderPolicy::STALE_LONG_TERM_SECONDS . 'S')) ? 'stale_measurement' : null;
		}
		$ratio = $progress['progressRatio'] ?? null;
		if ($ratio === null || !$this->meaningfullyBehind($ratio, $goal->getPeriod(), $progress, $localNow)) {
			return null;
		}
		return 'behind_progress';
	}

	/** @param GoalProgressInternal $progress */
	private function meaningfullyBehind(float $actualRatio, string $period, array $progress, DateTimeImmutable $localNow): bool {
		if ($period === 'day') {
			$start = $localNow->setTime(GoalReminderPolicy::DAILY_WINDOW_START_HOUR, 0);
			$end = $localNow->setTime(GoalReminderPolicy::DAILY_WINDOW_END_HOUR, 0);
			$expected = min(1.0, max(0.0, ($localNow->getTimestamp() - $start->getTimestamp()) / max(1, $end->getTimestamp() - $start->getTimestamp())));
			return $actualRatio + GoalReminderPolicy::EXPECTED_PROGRESS_MARGIN < $expected;
		}
		$periodStart = new DateTimeImmutable($progress['periodStart'], $localNow->getTimezone());
		$periodEnd = new DateTimeImmutable((string)$progress['periodEnd'], $localNow->getTimezone());
		$expected = min(1.0, max(0.0, ($localNow->getTimestamp() - $periodStart->getTimestamp()) / max(1, $periodEnd->getTimestamp() - $periodStart->getTimestamp())));
		return $actualRatio + GoalReminderPolicy::EXPECTED_PROGRESS_MARGIN < $expected;
	}

	private function insideWindow(string $period, DateTimeImmutable $localNow): bool {
		if ($period === 'long_term') {
			return true;
		}
		$hour = (int)$localNow->format('G');
		return $hour >= GoalReminderPolicy::DAILY_WINDOW_START_HOUR && $hour < GoalReminderPolicy::DAILY_WINDOW_END_HOUR;
	}

	private function stateAllows(?GoalReminderState $state, string $reason, string $period, DateTimeImmutable $now): bool {
		if ($state === null) {
			return true;
		}
		if ($state->getLastNotificationReason() === $reason && in_array($reason, ['limit_reached', 'limit_exceeded', 'measurement_missing', 'stale_measurement'], true)) {
			return false;
		}
		if ($state->getLastNotificationAt() !== null && $state->getLastNotificationAt() >= $now->sub(new DateInterval('PT' . GoalReminderPolicy::COOLDOWN_SECONDS . 'S'))) {
			return false;
		}
		$maximum = match ($period) {
			'day' => GoalReminderPolicy::DAILY_MAXIMUM_NOTIFICATIONS,
			'week' => GoalReminderPolicy::WEEKLY_MAXIMUM_NOTIFICATIONS,
			'month' => GoalReminderPolicy::MONTHLY_MAXIMUM_NOTIFICATIONS,
			'long_term' => GoalReminderPolicy::LONG_TERM_MAXIMUM_NOTIFICATIONS,
		};
		return $state->getNotificationCount() < $maximum;
	}
}
