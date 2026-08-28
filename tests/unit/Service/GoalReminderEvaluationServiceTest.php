<?php

declare(strict_types=1);

namespace OCA\Health\Tests\Unit\Service;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use OCA\Health\Db\Goal;
use OCA\Health\Db\GoalReminderState;
use OCA\Health\Service\GoalReminderEvaluationService;
use OCA\Health\Service\GoalReminderPolicy;
use OCA\Health\Service\GoalTargetRegistry;
use OCP\IDateTimeZone;
use PHPUnit\Framework\TestCase;

class GoalReminderEvaluationServiceTest extends TestCase {
	private DateTimeImmutable $now;
	private GoalReminderEvaluationService $service;

	protected function setUp(): void {
		$this->now = new DateTimeImmutable('2026-08-24T17:00:00Z');
		$timezone = $this->createMock(IDateTimeZone::class);
		$timezone->method('getTimeZone')->willReturn(new DateTimeZone('UTC'));
		$this->service = new GoalReminderEvaluationService(new GoalTargetRegistry(), $timezone);
	}

	public function testBehindProgressCanRepeatOnlyAfterCooldownAndWithinPeriodCap(): void {
		$goal = $this->goal('hydration.water', 'day', 'gte');
		$progress = $this->progress(['progressRatio' => 0.1, 'currentValue' => 1.0, 'targetValue' => 5.0]);
		self::assertSame('behind_progress', $this->service->reason($goal, $progress, true, null, $this->now));

		$state = $this->state('behind_progress', $this->now->sub(new DateInterval('PT121M')), 1);
		self::assertSame('behind_progress', $this->service->reason($goal, $progress, true, $state, $this->now));
		self::assertNull($this->service->reason($goal, $progress, true, $this->state('behind_progress', $this->now->sub(new DateInterval('PT30M')), 1), $this->now));
		self::assertNull($this->service->reason($goal, $progress, true, $this->state('behind_progress', $this->now->sub(new DateInterval('PT121M')), GoalReminderPolicy::DAILY_MAXIMUM_NOTIFICATIONS), $this->now));
	}

	public function testLimitAndMissingRemindersAreDeduplicatedAndRecentActivitySuppressesAnyReminder(): void {
		$limitGoal = $this->goal('hydration.coffee', 'day', 'lte');
		$limitProgress = $this->progress(['comparator' => 'lte', 'currentValue' => 3.0, 'targetValue' => 2.0, 'status' => 'exceeded', 'progressRatio' => null]);
		self::assertSame('limit_exceeded', $this->service->reason($limitGoal, $limitProgress, true, null, $this->now));
		self::assertNull($this->service->reason($limitGoal, $limitProgress, true, $this->state('limit_exceeded', $this->now->sub(new DateInterval('PT121M')), 1), $this->now));

		$missingGoal = $this->goal('pulse', 'day', 'lte');
		$missingProgress = $this->progress(['comparator' => 'lte', 'currentValue' => 0.0, 'targetValue' => 60.0, 'status' => 'in_progress', 'progressRatio' => null]);
		self::assertSame('measurement_missing', $this->service->reason($missingGoal, $missingProgress, true, null, $this->now));
		$missingProgress['lastActivityAt'] = $this->now->sub(new DateInterval('PT30M'));
		self::assertNull($this->service->reason($missingGoal, $missingProgress, true, null, $this->now));
		self::assertNull($this->service->reason($missingGoal, $this->progress(), false, null, $this->now));
	}

	public function testPausedDisabledReachedAndOnTrackGoalsDoNotNotify(): void {
		$goal = $this->goal('hydration.water', 'day', 'gte');
		$behind = $this->progress(['progressRatio' => 0.1, 'currentValue' => 1.0, 'targetValue' => 5.0]);
		$goal->setActive(false);
		self::assertNull($this->service->reason($goal, $behind, true, null, $this->now));

		$goal->setActive(true);
		$goal->setRemindersEnabled(false);
		self::assertNull($this->service->reason($goal, $behind, true, null, $this->now));

		$goal->setRemindersEnabled(true);
		self::assertNull($this->service->reason($goal, $behind, false, null, $this->now));
		self::assertNull($this->service->reason($goal, $this->progress(['progressRatio' => 1.0, 'currentValue' => 5.0, 'status' => 'reached']), true, null, $this->now));
		self::assertNull($this->service->reason($goal, $this->progress(['progressRatio' => 0.6, 'currentValue' => 3.0]), true, null, $this->now));
	}

	/** @param 'gte'|'lte' $comparator */
	private function goal(string $targetKey, string $period, string $comparator): Goal {
		$goal = new Goal();
		$goal->setUserId('test-user');
		$goal->setTargetKey($targetKey);
		$goal->setPeriod($period);
		$goal->setActive(true);
		$goal->setRemindersEnabled(true);
		$goal->setReminderPolicy('gentle');
		$goal->setCreatedAt($this->now->sub(new DateInterval('P10D')));
		$goal->setUpdatedAt($this->now);
		return $goal;
	}

	/** @param array<string, mixed> $overrides @return array<string, mixed> */
	private function progress(array $overrides = []): array {
		return array_replace([
			'periodStart' => '2026-08-24', 'periodEnd' => '2026-08-25', 'periodKey' => '2026-08-24',
			'comparator' => 'gte', 'currentValue' => 0.0, 'targetValue' => 5.0,
			'progressRatio' => 0.0, 'status' => 'in_progress', 'lastActivityAt' => null,
		], $overrides);
	}

	private function state(string $reason, DateTimeImmutable $at, int $count): GoalReminderState {
		$state = new GoalReminderState();
		$state->setGoalId(1);
		$state->setPeriodKey('2026-08-24');
		$state->setLastNotificationReason($reason);
		$state->setLastNotificationAt($at);
		$state->setNotificationCount($count);
		$state->setUpdatedAt($at);
		return $state;
	}
}
