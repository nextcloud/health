<?php

declare(strict_types=1);

namespace OCA\Health\Dashboard;

use OCA\Health\AppInfo\Application;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\IWidget;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Util;

/** @psalm-suppress PossiblyUnusedMethod Instantiated through Nextcloud dashboard discovery. */
class HealthDashboardWidget implements IWidget, IIconWidget {
	/** @psalm-suppress PossiblyUnusedMethod Instantiated through Nextcloud dashboard discovery. */
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getId(): string {
		return Application::APP_ID;
	}

	public function getTitle(): string {
		return $this->l10n->t('Health');
	}

	public function getOrder(): int {
		return 40;
	}

	public function getIconClass(): string {
		return 'icon-health';
	}

	public function getIconUrl(): string {
		return $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg'));
	}

	public function getUrl(): ?string {
		return $this->urlGenerator->linkToRouteAbsolute('health.page.index');
	}

	public function load(): void {
		Util::addScript(Application::APP_ID, Application::APP_ID . '-dashboard');
		Util::addStyle(Application::APP_ID, Application::APP_ID . '-dashboard');
	}
}
