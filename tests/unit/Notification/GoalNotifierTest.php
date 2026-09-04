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
	public function testWeightReminderUsesThePresentationLanguageAndMetricIconWithoutValues(): void {
		$l10n = $this->l10n([
			'%s reminder' => '%s Erinnerung',
			'Weight' => 'Gewicht',
			'Open Health to review your reminder.' => 'Öffne Health, um deine Erinnerung anzusehen.',
		]);
		$factory = $this->createMock(IFactory::class);
		$factory->expects(self::once())->method('get')->with(Application::APP_ID, 'de')->willReturn($l10n);
		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->expects(self::once())->method('linkToRoute')->with('health.page.goals')->willReturn('/apps/health/goals');
		$urlGenerator->expects(self::once())->method('imagePath')->with(Application::APP_ID, 'notifications/weight.svg')->willReturn('/apps/health/img/notifications/weight.svg');
		$urlGenerator->expects(self::once())->method('getAbsoluteURL')->with('/apps/health/img/notifications/weight.svg')->willReturn('https://cloud.example/apps/health/img/notifications/weight.svg');
		$notification = $this->notification('goal_reminder', ['targetKey' => 'weight']);
		$preparedSubject = '';
		$preparedMessage = '';
		$notification->expects(self::once())->method('setParsedSubject')->willReturnCallback(static function (string $subject) use (&$preparedSubject, $notification): INotification {
			$preparedSubject = $subject;
			return $notification;
		});
		$notification->expects(self::once())->method('setParsedMessage')->willReturnCallback(static function (string $message) use (&$preparedMessage, $notification): INotification {
			$preparedMessage = $message;
			return $notification;
		});
		$notification->expects(self::once())->method('setLink')->with('/apps/health/goals')->willReturnSelf();
		$notification->expects(self::once())->method('setIcon')->with('https://cloud.example/apps/health/img/notifications/weight.svg')->willReturnSelf();

		$result = (new GoalNotifier($factory, $urlGenerator, new GoalTargetRegistry()))->prepare($notification, 'de');

		self::assertSame($notification, $result);
		self::assertSame('Gewicht Erinnerung', $preparedSubject);
		self::assertStringNotContainsString('{topic}', $preparedSubject);
		$displayedNotification = $preparedSubject . "\n" . $preparedMessage;
		foreach (['72.5', '80', '48%', '7.5 remaining', 'Personal journal note'] as $privateValue) {
			self::assertStringNotContainsString($privateValue, $displayedNotification);
		}
	}

	public function testStepsReminderUsesTheCorrectTopicAndMetricIcon(): void {
		$l10n = $this->l10n();
		$factory = $this->createMock(IFactory::class);
		$factory->expects(self::once())->method('get')->with(Application::APP_ID, 'en')->willReturn($l10n);
		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->expects(self::once())->method('linkToRoute')->with('health.page.goals')->willReturn('/apps/health/goals');
		$urlGenerator->expects(self::once())->method('imagePath')->with(Application::APP_ID, 'notifications/steps.svg')->willReturn('/apps/health/img/notifications/steps.svg');
		$urlGenerator->expects(self::once())->method('getAbsoluteURL')->with('/apps/health/img/notifications/steps.svg')->willReturn('https://cloud.example/apps/health/img/notifications/steps.svg');
		$notification = $this->notification('goal_reminder', ['targetKey' => 'steps']);
		$notification->expects(self::once())->method('setParsedSubject')->with('Steps reminder')->willReturnSelf();
		$notification->expects(self::once())->method('setParsedMessage')->with('Open Health to review your reminder.')->willReturnSelf();
		$notification->expects(self::once())->method('setLink')->with('/apps/health/goals')->willReturnSelf();
		$notification->expects(self::once())->method('setIcon')->with('https://cloud.example/apps/health/img/notifications/steps.svg')->willReturnSelf();

		(new GoalNotifier($factory, $urlGenerator, new GoalTargetRegistry()))->prepare($notification, 'en');
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

	public function testUnknownGoalTargetUsesTheGenericReminderFallback(): void {
		$l10n = $this->l10n();
		$factory = $this->createMock(IFactory::class);
		$factory->expects(self::once())->method('get')->with(Application::APP_ID, 'en')->willReturn($l10n);
		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->expects(self::once())->method('linkToRoute')->with('health.page.goals')->willReturn('/apps/health/goals');
		$urlGenerator->expects(self::once())->method('imagePath')->with(Application::APP_ID, 'app-dark.svg')->willReturn('/apps/health/img/app-dark.svg');
		$urlGenerator->expects(self::once())->method('getAbsoluteURL')->with('/apps/health/img/app-dark.svg')->willReturn('https://cloud.example/apps/health/img/app-dark.svg');
		$notification = $this->notification('goal_reminder', ['targetKey' => 'removed.topic']);
		$notification->expects(self::once())->method('setParsedSubject')->with('Health reminder')->willReturnSelf();
		$notification->expects(self::once())->method('setParsedMessage')->with('Open Health to review your reminder.')->willReturnSelf();
		$notification->expects(self::once())->method('setLink')->with('/apps/health/goals')->willReturnSelf();
		$notification->expects(self::once())->method('setIcon')->with('https://cloud.example/apps/health/img/app-dark.svg')->willReturnSelf();

		(new GoalNotifier($factory, $urlGenerator, new GoalTargetRegistry()))->prepare($notification, 'en');
	}

	/** @param array<string, string> $translations */
	private function l10n(array $translations = []): IL10N {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static function (string $text, mixed $parameters = []) use ($translations): string {
			$translatedText = $translations[$text] ?? $text;
			if (!is_array($parameters)) {
				$parameters = [$parameters];
			}
			return $parameters === [] ? $translatedText : vsprintf($translatedText, $parameters);
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
