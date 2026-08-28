<?php

declare(strict_types=1);

namespace OCA\Health\AppInfo;

use OCA\Health\Dashboard\HealthDashboardWidget;
use OCA\Health\Notification\GoalNotifier;
use OCA\Health\Search\DailyNoteSearchProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
	public const APP_ID = 'health';

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerCapability(Capabilities::class);
		$context->registerSearchProvider(DailyNoteSearchProvider::class);
		$context->registerDashboardWidget(HealthDashboardWidget::class);
		$context->registerNotifierService(GoalNotifier::class);
	}

	public function boot(IBootContext $context): void {
	}
}
