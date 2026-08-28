<?php

declare(strict_types=1);

namespace OCA\Health\BackgroundJob;

use DateTimeImmutable;
use OCA\Health\Service\GoalReminderService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

/** Runs the deterministic Gentle reminder check approximately once per hour. */
/** @psalm-suppress UnusedClass Registered and instantiated by Nextcloud background-job discovery. */
class GoalReminderJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private GoalReminderService $goalReminderService,
	) {
		parent::__construct($time);
		$this->setInterval(60 * 60);
		$this->setAllowParallelRuns(false);
	}

	#[\Override]
	protected function run(mixed $argument): void {
		$this->goalReminderService->process(DateTimeImmutable::createFromInterface($this->time->getDateTime()));
	}
}
