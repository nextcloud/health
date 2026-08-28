<?php

declare(strict_types=1);

namespace OCA\Health\Service;

/** Central timing constants for deterministic, non-gamified Gentle reminders. */
final class GoalReminderPolicy {
	public const DAILY_WINDOW_START_HOUR = 9;
	public const DAILY_WINDOW_END_HOUR = 20;
	public const COOLDOWN_SECONDS = 2 * 60 * 60;
	public const RECENT_ACTIVITY_SECONDS = 90 * 60;
	public const DAILY_MAXIMUM_NOTIFICATIONS = 3;
	public const WEEKLY_MAXIMUM_NOTIFICATIONS = 2;
	public const MONTHLY_MAXIMUM_NOTIFICATIONS = 2;
	public const LONG_TERM_MAXIMUM_NOTIFICATIONS = 1;
	public const EXPECTED_PROGRESS_MARGIN = 0.20;
	public const STALE_LONG_TERM_SECONDS = 7 * 24 * 60 * 60;

	private function __construct() {
	}
}
