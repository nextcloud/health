<?php

declare(strict_types=1);

namespace OCA\Health\Service;

use OCA\Health\Exception\InvalidEntryException;

/**
 * Central registry for built-in metric definitions.
 *
 * @psalm-import-type HealthMetricDefinition from \OCA\Health\Types\MetricTypes
 */
class MetricService {
	/** @psalm-suppress PossiblyUnusedMethod Instantiated through Nextcloud dependency injection. */
	private const SCALE_MINIMUM = 1;
	private const SCALE_MAXIMUM = 5;

	/**
	 * @var array<string, HealthMetricDefinition>
	 */
	private const METRIC_DEFINITIONS = [
		'stress' => ['metricKey' => 'stress', 'category' => 'journal', 'valueType' => 'scale', 'minimum' => self::SCALE_MINIMUM, 'maximum' => self::SCALE_MAXIMUM, 'allowedOptions' => null, 'aggregation' => 'average', 'canonicalUnit' => null, 'supportedUnits' => []],
		'energy' => ['metricKey' => 'energy', 'category' => 'journal', 'valueType' => 'scale', 'minimum' => self::SCALE_MINIMUM, 'maximum' => self::SCALE_MAXIMUM, 'allowedOptions' => null, 'aggregation' => 'average', 'canonicalUnit' => null, 'supportedUnits' => []],
		'mood' => ['metricKey' => 'mood', 'category' => 'journal', 'valueType' => 'scale', 'minimum' => self::SCALE_MINIMUM, 'maximum' => self::SCALE_MAXIMUM, 'allowedOptions' => null, 'aggregation' => 'average', 'canonicalUnit' => null, 'supportedUnits' => []],
		'hydration' => ['metricKey' => 'hydration', 'category' => 'journal', 'valueType' => 'event', 'minimum' => null, 'maximum' => null, 'allowedOptions' => ['small_glass', 'large_glass', 'coffee', 'cappuccino', 'espresso', 'double_espresso', 'latte_macchiato', 'cafe_au_lait', 'tea', 'other'], 'aggregation' => 'count', 'canonicalUnit' => null, 'supportedUnits' => []],
		'break' => ['metricKey' => 'break', 'category' => 'journal', 'valueType' => 'event', 'minimum' => null, 'maximum' => null, 'allowedOptions' => ['short', 'regular', 'short_walk', 'long_walk', 'mindfulness', 'fresh_air'], 'aggregation' => 'count', 'canonicalUnit' => null, 'supportedUnits' => []],
		'temperature' => ['metricKey' => 'temperature', 'category' => 'measurement', 'valueType' => 'numeric', 'minimum' => null, 'maximum' => null, 'allowedOptions' => null, 'aggregation' => 'count', 'canonicalUnit' => 'celsius', 'supportedUnits' => ['celsius', 'fahrenheit']],
		'oxygen_saturation' => ['metricKey' => 'oxygen_saturation', 'category' => 'measurement', 'valueType' => 'numeric', 'minimum' => null, 'maximum' => null, 'allowedOptions' => null, 'aggregation' => 'count', 'canonicalUnit' => 'percent', 'supportedUnits' => ['percent']],
		'blood_glucose' => ['metricKey' => 'blood_glucose', 'category' => 'measurement', 'valueType' => 'numeric', 'minimum' => null, 'maximum' => null, 'allowedOptions' => null, 'aggregation' => 'count', 'canonicalUnit' => 'mmol_l', 'supportedUnits' => ['mmol_l', 'mg_dl']],
		'pulse' => ['metricKey' => 'pulse', 'category' => 'measurement', 'valueType' => 'numeric', 'minimum' => null, 'maximum' => null, 'allowedOptions' => null, 'aggregation' => 'count', 'canonicalUnit' => 'bpm', 'supportedUnits' => ['bpm']],
		'blood_pressure' => ['metricKey' => 'blood_pressure', 'category' => 'measurement', 'valueType' => 'composite', 'minimum' => null, 'maximum' => null, 'allowedOptions' => null, 'aggregation' => 'count', 'canonicalUnit' => 'mmhg', 'supportedUnits' => ['mmhg', 'kpa']],
		'weight' => ['metricKey' => 'weight', 'category' => 'daily_value', 'valueType' => 'numeric', 'minimum' => null, 'maximum' => null, 'allowedOptions' => null, 'aggregation' => 'daily', 'canonicalUnit' => 'kg', 'supportedUnits' => ['kg', 'lb', 'st']],
		'body_fat' => ['metricKey' => 'body_fat', 'category' => 'daily_value', 'valueType' => 'numeric', 'minimum' => null, 'maximum' => null, 'allowedOptions' => null, 'aggregation' => 'daily', 'canonicalUnit' => 'percent', 'supportedUnits' => ['percent']],
		'waist' => ['metricKey' => 'waist', 'category' => 'daily_value', 'valueType' => 'numeric', 'minimum' => null, 'maximum' => null, 'allowedOptions' => null, 'aggregation' => 'daily', 'canonicalUnit' => 'cm', 'supportedUnits' => ['cm', 'in']],
		'hip' => ['metricKey' => 'hip', 'category' => 'daily_value', 'valueType' => 'numeric', 'minimum' => null, 'maximum' => null, 'allowedOptions' => null, 'aggregation' => 'daily', 'canonicalUnit' => 'cm', 'supportedUnits' => ['cm', 'in']],
		'muscle_percentage' => ['metricKey' => 'muscle_percentage', 'category' => 'daily_value', 'valueType' => 'numeric', 'minimum' => null, 'maximum' => null, 'allowedOptions' => null, 'aggregation' => 'daily', 'canonicalUnit' => 'percent', 'supportedUnits' => ['percent']],
		'sins' => ['metricKey' => 'sins', 'category' => 'daily_value', 'valueType' => 'numeric', 'minimum' => null, 'maximum' => null, 'allowedOptions' => null, 'aggregation' => 'daily', 'canonicalUnit' => 'count', 'supportedUnits' => ['count']],
		'steps' => ['metricKey' => 'steps', 'category' => 'daily_value', 'valueType' => 'numeric', 'minimum' => null, 'maximum' => null, 'allowedOptions' => null, 'aggregation' => 'daily', 'canonicalUnit' => 'steps', 'supportedUnits' => ['steps']],
		'job_satisfaction' => ['metricKey' => 'job_satisfaction', 'category' => 'daily_value', 'valueType' => 'scale', 'minimum' => self::SCALE_MINIMUM, 'maximum' => self::SCALE_MAXIMUM, 'allowedOptions' => null, 'aggregation' => 'daily', 'canonicalUnit' => null, 'supportedUnits' => []],
	];

	/** @return list<HealthMetricDefinition> */
	public static function getMetricDefinitions(): array {
		return array_values(self::METRIC_DEFINITIONS);
	}

	/** @return list<string> */
	public static function getModuleKeys(): array {
		return array_keys(self::METRIC_DEFINITIONS);
	}

	/**
	 * Returns the monochrome notification icon asset associated with a metric.
	 *
	 * The assets use the same Material Design icons as the Health frontend. A
	 * missing icon intentionally falls back to the app icon at presentation time.
	 */
	public static function getNotificationIconName(string $metricKey): ?string {
		return match ($metricKey) {
			'hydration' => 'water',
			'break' => 'break',
			'steps' => 'steps',
			'job_satisfaction' => 'job-satisfaction',
			'pulse' => 'pulse',
			'blood_pressure' => 'blood-pressure',
			'weight' => 'weight',
			default => null,
		};
	}

	/** @return list<string> */
	/** @psalm-suppress PossiblyUnusedMethod Public metric-registry helper retained for application integrations. */
	public static function getMetricKeysForCategory(string $category): array {
		return array_keys(array_filter(self::METRIC_DEFINITIONS, static fn (array $definition): bool => $definition['category'] === $category));
	}

	/** @return HealthMetricDefinition */
	public function getDefinition(string $metricKey): array {
		if (!isset(self::METRIC_DEFINITIONS[$metricKey])) {
			throw new InvalidEntryException('Unsupported metric key.');
		}
		return self::METRIC_DEFINITIONS[$metricKey];
	}

	public function validateMetricKey(mixed $metricKey): string {
		if (!is_string($metricKey) || !isset(self::METRIC_DEFINITIONS[$metricKey])) {
			throw new InvalidEntryException('Unsupported metric key.');
		}
		return $metricKey;
	}

	public function validateJournalMetricKey(mixed $metricKey): string {
		$metricKey = $this->validateMetricKey($metricKey);
		if (self::METRIC_DEFINITIONS[$metricKey]['category'] !== 'journal') {
			throw new InvalidEntryException('Unsupported journal metric key.');
		}
		return $metricKey;
	}

	public function validateDailyValueMetricKey(mixed $metricKey): string {
		$metricKey = $this->validateMetricKey($metricKey);
		if (self::METRIC_DEFINITIONS[$metricKey]['category'] !== 'daily_value') {
			throw new InvalidEntryException('Unsupported daily value metric key.');
		}
		return $metricKey;
	}

	public function validateDailyValueNumericValue(string $metricKey, float $value): float {
		$definition = $this->getDefinition($this->validateDailyValueMetricKey($metricKey));
		if ($definition['valueType'] === 'scale' && (!is_finite($value) || floor($value) !== $value || $value < self::SCALE_MINIMUM || $value > self::SCALE_MAXIMUM)) {
			throw new InvalidEntryException('Daily scale values must be integers between 1 and 5.');
		}
		return $value;
	}

	public function validateMeasurementMetricKey(mixed $metricKey): string {
		$metricKey = $this->validateMetricKey($metricKey);
		if (self::METRIC_DEFINITIONS[$metricKey]['category'] !== 'measurement') {
			throw new InvalidEntryException('Unsupported measurement metric key.');
		}
		return $metricKey;
	}

	/** @return list<string> */
	public function getSupportedUnits(string $metricKey): array {
		$units = $this->getDefinition($metricKey)['supportedUnits'];
		return $units;
	}

	/** @return array{numericValue: int|null, optionValue: string|null} */
	public function validateValue(string $metricKey, mixed $numericValue, mixed $optionValue): array {
		$metricKey = $this->validateJournalMetricKey($metricKey);
		$definition = self::METRIC_DEFINITIONS[$metricKey];
		if ($definition['valueType'] === 'scale') {
			if (!is_int($numericValue) || $numericValue < self::SCALE_MINIMUM || $numericValue > self::SCALE_MAXIMUM || $optionValue !== null) {
				throw new InvalidEntryException('Scale values must be integers between 1 and 5 without an option value.');
			}
			return ['numericValue' => $numericValue, 'optionValue' => null];
		}
		if ($numericValue !== null || !is_string($optionValue) || $definition['allowedOptions'] === null || !in_array($optionValue, $definition['allowedOptions'], true)) {
			throw new InvalidEntryException('Unsupported option value for this metric.');
		}
		return ['numericValue' => null, 'optionValue' => $optionValue];
	}
}
