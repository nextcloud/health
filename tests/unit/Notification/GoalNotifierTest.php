<?php

declare(strict_types=1);

namespace OCA\Health\Tests\Unit\Notification;

use OCA\Health\AppInfo\Application;
use OCA\Health\Notification\GoalNotifier;
use OCA\Health\Service\GoalTargetRegistry;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use PHPUnit\Framework\TestCase;

class GoalNotifierTest extends TestCase {
	public function testGoalReminderUsesTheLocalizedTopicAndItsMetricIconWithoutValues(): void {
		$l10n = $this->l10n();
		$factory = $this->createMock(IFactory::class);
		$factory->method('get')->willReturn($l10n);
		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->expects(self::once())->method('linkToRoute')->with('health.page.goals')->willReturn('/apps/health/goals');
		$urlGenerator->expects(self::once())->method('imagePath')->with(Application::APP_ID, 'notifications/water.svg')->willReturn('/apps/health/img/notifications/water.svg');
		$urlGenerator->expects(self::once())->method('getAbsoluteURL')->with('/apps/health/img/notifications/water.svg')->willReturn('https://cloud.example/apps/health/img/notifications/water.svg');
		$notification = $this->notification('goal_reminder', ['targetKey' => 'hydration.water']);
		$notification->expects(self::once())->method('setParsedSubject')->with('Water reminder')->willReturnSelf();
		$notification->expects(self::once())->method('setParsedMessage')->with('Open Health to review your reminder.')->willReturnSelf();
		$notification->expects(self::once())->method('setLink')->with('/apps/health/goals')->willReturnSelf();
		$notification->expects(self::once())->method('setIcon')->with('https://cloud.example/apps/health/img/notifications/water.svg')->willReturnSelf();

		$result = (new GoalNotifier($factory, $urlGenerator, new GoalTargetRegistry()))->prepare($notification, 'de');

		self::assertSame($notification, $result);
		self::assertStringNotContainsString('1.2', 'Water reminder');
		self::assertStringNotContainsString('2.5', 'Open Health to review your reminder.');
		self::assertStringNotContainsString('%', 'Open Health to review your reminder.');
	}

	public function testLegacyReminderFallsBackToTheThemeSafeAppIcon(): void {
		$l10n = $this->l10n();
		$factory = $this->createMock(IFactory::class);
		$factory->method('get')->willReturn($l10n);
		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->method('linkToRoute')->willReturn('/apps/health/goals');
		$urlGenerator->expects(self::once())->method('imagePath')->with(Application::APP_ID, 'app-dark.svg')->willReturn('/apps/health/img/app-dark.svg');
		$urlGenerator->expects(self::once())->method('getAbsoluteURL')->with('/apps/health/img/app-dark.svg')->willReturn('https://cloud.example/apps/health/img/app-dark.svg');
		$notification = $this->notification('behind_progress', []);
		$notification->expects(self::once())->method('setParsedSubject')->with('Health reminder')->willReturnSelf();
		$notification->expects(self::once())->method('setParsedMessage')->with('Open Health to review your reminder.')->willReturnSelf();
		$notification->expects(self::once())->method('setLink')->willReturnSelf();
		$notification->expects(self::once())->method('setIcon')->with('https://cloud.example/apps/health/img/app-dark.svg')->willReturnSelf();

		(new GoalNotifier($factory, $urlGenerator, new GoalTargetRegistry()))->prepare($notification, 'en');
	}

	private function l10n(): IL10N {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static function (string $text, array $parameters = []): string {
			return strtr($text, ['{topic}' => $parameters['topic'] ?? '']);
		});
		return $l10n;
	}

	/** @param array<string, string> $parameters */
	private function notification(string $subject, array $parameters): INotification {
		$notification = $this->createMock(INotification::class);
		$notification->method('getApp')->willReturn(Application::APP_ID);
		$notification->method('getObjectType')->willReturn('goal_reminder');
		$notification->method('getSubject')->willReturn($subject);
		$notification->method('getSubjectParameters')->willReturn($parameters);
		return $notification;
	}
}
