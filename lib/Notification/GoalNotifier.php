<?php

declare(strict_types=1);

namespace OCA\Health\Notification;

use OCA\Health\AppInfo\Application;
use OCA\Health\Exception\InvalidEntryException;
use OCA\Health\Service\GoalTargetRegistry;
use OCA\Health\Service\MetricService;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Notification\UnknownNotificationException;

/** @psalm-suppress PossiblyUnusedMethod Instantiated through Nextcloud notification discovery. */
class GoalNotifier implements INotifier {
	/** @psalm-suppress PossiblyUnusedMethod Instantiated through Nextcloud notification discovery. */
	public function __construct(
		private IFactory $l10nFactory,
		private IURLGenerator $urlGenerator,
		private GoalTargetRegistry $goalTargetRegistry,
	) {
	}

	#[\Override]
	public function getID(): string {
		return Application::APP_ID;
	}

	#[\Override]
	public function getName(): string {
		return $this->l10nFactory->get(Application::APP_ID)->t('Health');
	}

	#[\Override]
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== Application::APP_ID || $notification->getObjectType() !== 'goal_reminder') {
			throw new UnknownNotificationException();
		}
		$l = $this->l10nFactory->get(Application::APP_ID, $languageCode);
		$metricKey = $this->metricKey($notification);
		$iconName = $metricKey === null ? null : MetricService::getNotificationIconName($metricKey);
		$notification->setParsedSubject($metricKey === null
			? $l->t('Health reminder')
			: $l->t('%s reminder', [$this->topicLabel($l, $this->targetKey($notification) ?? '')]));
		$notification->setParsedMessage($l->t('Open Health to review your reminder.'));
		$notification->setLink($this->urlGenerator->linkToRoute('health.page.goals'));
		$notification->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath(
			Application::APP_ID,
			$iconName === null
				? 'app-dark.svg'
				: 'notifications/' . $iconName . '.svg',
		)));
		return $notification;
	}

	private function metricKey(INotification $notification): ?string {
		$targetKey = $this->targetKey($notification);
		if ($targetKey === null) {
			return null;
		}

		try {
			return $this->goalTargetRegistry->getDefinition($targetKey)['metricKey'];
		} catch (InvalidEntryException) {
			return null;
		}
	}

	private function targetKey(INotification $notification): ?string {
		if ($notification->getSubject() === 'goal_reminder') {
			$parameters = $notification->getSubjectParameters();
			if (!isset($parameters['targetKey']) || !is_string($parameters['targetKey'])) {
				return null;
			}
			return $parameters['targetKey'];
		}

		if (in_array($notification->getSubject(), ['behind_progress', 'limit_reached', 'limit_exceeded', 'measurement_missing', 'stale_measurement'], true)) {
			return null;
		}

		throw new UnknownNotificationException();
	}

	private function topicLabel(IL10N $l, string $targetKey): string {
		return match ($targetKey) {
			'hydration.water' => $l->t('Water'),
			'hydration.coffee' => $l->t('Coffee'),
			'hydration.tea' => $l->t('Tea'),
			'break.all' => $l->t('Break'),
			'break.mindfulness' => $l->t('Mindfulness'),
			'steps' => $l->t('Steps'),
			'kilocalories' => $l->t('Kilocalories'),
			'fruit' => $l->t('Fruit'),
			'job_satisfaction' => $l->t('Job Satisfaction'),
			'pulse' => $l->t('Pulse'),
			'blood_pressure' => $l->t('Blood pressure'),
			'weight' => $l->t('Weight'),
			default => $l->t('Health'),
		};
	}
}
