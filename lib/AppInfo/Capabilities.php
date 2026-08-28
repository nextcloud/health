<?php

declare(strict_types=1);

namespace OCA\Health\AppInfo;

use OCA\Health\Service\GoalTargetRegistry;
use OCA\Health\Service\MetricService;
use OCP\Capabilities\ICapability;

/** @psalm-suppress PossiblyUnusedMethod Instantiated through Nextcloud capabilities discovery. */
class Capabilities implements ICapability {
	/** @psalm-suppress PossiblyUnusedMethod Instantiated through Nextcloud capabilities discovery. */
	public function __construct(
		private GoalTargetRegistry $goalTargetRegistry,
	) {
	}

	/**
	 * @return array{
	 *   health: array{
	 *     apiVersions: list<string>,
	 *     features: list<string>,
	 *     modules: list<string>,
	 *     goalTargets: list<array{
	 *       targetKey: string,
	 *       metricKey: string,
	 *       category: 'journal'|'measurement'|'daily_value',
	 *       periods: list<string>,
	 *       comparators: list<string>,
	 *       kind: string,
	 *       unit: string|null,
	 *       minimum?: float,
	 *       maximum?: float,
	 *       options?: list<string>,
	 *     }>,
	 *     metrics: list<array{
	 *       metricKey: string,
	 *       category: 'journal'|'measurement'|'daily_value',
	 *       valueType: 'scale'|'event'|'numeric'|'composite',
	 *       minimum: int|null,
	 *       maximum: int|null,
	 *       allowedOptions: list<string>|null,
	 *       aggregation: 'average'|'count'|'daily',
	 *       canonicalUnit: string|null,
	 *       supportedUnits: list<string>,
	 *     }>,
	 *   },
	 * }
	 */
	public function getCapabilities(): array {
		return [
			Application::APP_ID => [
				'apiVersions' => ['2'],
				'features' => ['entries', 'daily-values', 'measurements', 'configuration', 'check-in', 'check-out', 'goals', 'goal-progress', 'gentle-reminders'],
				'modules' => MetricService::getModuleKeys(),
				'metrics' => MetricService::getMetricDefinitions(),
				'goalTargets' => $this->goalTargetRegistry->getDefinitions(),
			],
		];
	}
}
