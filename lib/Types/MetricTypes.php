<?php

declare(strict_types=1);

namespace OCA\Health\Types;

/**
 * Internal Psalm types for the built-in metric registry.
 *
 * This carrier is deliberately separate from public OpenAPI response
 * definitions: metric registry data is reused internally but is not a
 * standalone Health OCS response schema.
 *
 * @psalm-type HealthMetricDefinition = array{
 *   metricKey: string,
 *   category: 'journal'|'measurement'|'daily_value',
 *   valueType: 'scale'|'event'|'numeric'|'composite',
 *   minimum: int|null,
 *   maximum: int|null,
 *   allowedOptions: list<string>|null,
 *   aggregation: 'average'|'count'|'daily',
 *   canonicalUnit: string|null,
 *   supportedUnits: list<string>
 * }
 * @psalm-suppress UnusedClass Psalm-only internal type carrier.
 */
final class MetricTypes {
	/** @psalm-suppress UnusedConstructor Psalm-only internal type carrier. */
	private function __construct() {
	}
}
