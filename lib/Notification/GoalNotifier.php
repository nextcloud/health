<?php

declare(strict_types=1);

namespace OCA\Health\Notification;

use OCA\Health\AppInfo\Application;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Notification\UnknownNotificationException;

class GoalNotifier implements INotifier {
	public function __construct(
		private IFactory $l10nFactory,
		private IURLGenerator $urlGenerator,
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
		$message = match ($notification->getSubject()) {
			'behind_progress' => $l->t('A personal goal is still in progress.'),
			'limit_reached' => $l->t('A personal daily limit was reached.'),
			'limit_exceeded' => $l->t('A personal daily limit was exceeded.'),
			'measurement_missing' => $l->t('No matching Health value has been recorded yet.'),
			'stale_measurement' => $l->t('A Health value has not been recorded recently.'),
			default => throw new UnknownNotificationException(),
		};
		$notification->setParsedSubject($l->t('Health reminder'));
		$notification->setParsedMessage($message);
		$notification->setLink($this->urlGenerator->linkToRoute('health.page.goals'));
		$notification->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath(Application::APP_ID, 'app.svg')));
		return $notification;
	}
}
