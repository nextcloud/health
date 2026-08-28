<?php

declare(strict_types=1);

namespace OCA\Health\Service;

use DateTime;
use DateTimeImmutable;
use OCA\Health\AppInfo\Application;
use OCA\Health\Db\GoalMapper;
use OCA\Health\Db\GoalReminderState;
use OCA\Health\Db\GoalReminderStateMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\Notification\IManager;

/** Sends only policy-approved private Nextcloud notifications. */
class GoalReminderService {
	public function __construct(
		private GoalMapper $goalMapper,
		private GoalReminderStateMapper $goalReminderStateMapper,
		private GoalProgressService $goalProgressService,
		private GoalReminderEvaluationService $goalReminderEvaluationService,
		private GoalTargetRegistry $goalTargetRegistry,
		private ConfigurationService $configurationService,
		private IManager $notificationManager,
	) {
	}

	public function process(DateTimeImmutable $now): void {
		foreach ($this->goalMapper->findActiveWithReminders() as $goal) {
			$progress = $this->goalProgressService->evaluateCurrentGoal($goal->getUserId(), $goal, $now);
			if ($progress === null) {
				continue;
			}
			$target = $this->goalTargetRegistry->getDefinition($goal->getTargetKey());
			$configuration = $this->configurationService->get($goal->getUserId());
			$metricEnabled = $configuration['metrics'][$target['metricKey']]['enabled'] ?? false;
			$state = $this->state($goal->getId(), (string)$progress['periodKey']);
			$reason = $this->goalReminderEvaluationService->reason($goal, $progress, $metricEnabled, $state, $now);
			if ($reason === null) {
				continue;
			}
			$notification = $this->notificationManager->createNotification();
			$notification->setApp(Application::APP_ID)
				->setUser($goal->getUserId())
				->setDateTime(DateTime::createFromImmutable($now))
				->setObject('goal_reminder', $goal->getId() . ':' . $progress['periodKey'] . ':' . $reason)
				->setSubject($reason);
			$this->notificationManager->notify($notification);
			$this->record($goal->getId(), (string)$progress['periodKey'], $reason, $state, $now);
		}
	}

	private function state(int $goalId, string $periodKey): ?GoalReminderState {
		try {
			return $this->goalReminderStateMapper->findForGoalPeriod($goalId, $periodKey);
		} catch (DoesNotExistException|MultipleObjectsReturnedException) {
			return null;
		}
	}

	private function record(int $goalId, string $periodKey, string $reason, ?GoalReminderState $state, DateTimeImmutable $now): void {
		$isNew = $state === null;
		$state ??= new GoalReminderState();
		if ($isNew) {
			$state->setGoalId($goalId);
			$state->setPeriodKey($periodKey);
			$state->setNotificationCount(0);
		}
		$state->setLastNotificationAt($now);
		$state->setNotificationCount($state->getNotificationCount() + 1);
		$state->setLastNotificationReason($reason);
		$state->setUpdatedAt($now);
		if ($isNew) {
			$this->goalReminderStateMapper->create($state);
			return;
		}
		$this->goalReminderStateMapper->updateForGoal($state);
	}
}
