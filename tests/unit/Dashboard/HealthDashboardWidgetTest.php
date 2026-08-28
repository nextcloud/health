<?php

declare(strict_types=1);

namespace OCA\Health\Tests\Unit\Dashboard;

use OCA\Health\Dashboard\HealthDashboardWidget;
use OCP\IL10N;
use OCP\IURLGenerator;
use PHPUnit\Framework\TestCase;

class HealthDashboardWidgetTest extends TestCase {
	public function testWidgetExposesHealthMetadataAndJournalUrl(): void {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->with('Health')->willReturn('Health');
		$url = $this->createMock(IURLGenerator::class);
		$url->method('imagePath')->with('health', 'app-dark.svg')->willReturn('/apps/health/img/app-dark.svg');
		$url->method('getAbsoluteURL')->with('/apps/health/img/app-dark.svg')->willReturn('https://cloud.test/apps/health/img/app-dark.svg');
		$url->method('linkToRouteAbsolute')->with('health.page.index')->willReturn('https://cloud.test/apps/health/');

		$widget = new HealthDashboardWidget($l10n, $url);

		self::assertSame('health', $widget->getId());
		self::assertSame('Health', $widget->getTitle());
		self::assertSame(40, $widget->getOrder());
		self::assertSame('icon-health', $widget->getIconClass());
		self::assertSame('https://cloud.test/apps/health/img/app-dark.svg', $widget->getIconUrl());
		self::assertSame('https://cloud.test/apps/health/', $widget->getUrl());
	}
}
