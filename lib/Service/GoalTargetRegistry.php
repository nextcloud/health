<?php

declare(strict_types=1);

namespace OCA\Health\Service;

use OCA\Health\Exception\InvalidEntryException;

/**
 * Authoritative registry for the intentionally small set of user-facing goal targets.
 *
 * Goal keys are separate from metric keys because one atomic metric can expose several
 * meaningful targets (for example water and coffee hydration events).
 *
 * @psalm-import-type HealthGoalTarget from \OCA\Health\ResponseDefinitions
 */
class GoalTargetRegistry {
	/**
	 * @var array<string, HealthGoalTarget>
	 */
	private const TARGETS = [
		'hydration.water' => ['targetKey' => 'hydration.water', 'metricKey' => 'hydration', 'category' => 'journal', 'periods' => ['day', 'week', 'month'], 'comparators' => ['gte', 'lte'], 'kind' => 'count', 'unit' => 'glasses', 'options' => ['small_glass', 'large_glass'], 'minimum' => 1.0],
		'hydration.coffee' => ['targetKey' => 'hydration.coffee', 'metricKey' => 'hydration', 'category' => 'journal', 'periods' => ['day', 'week', 'month'], 'comparators' => ['gte', 'lte'], 'kind' => 'count', 'unit' => null, 'options' => ['coffee', 'cappuccino', 'espresso', 'double_espresso', 'latte_macchiato', 'cafe_au_lait'], 'minimum' => 1.0],
		'hydration.tea' => ['targetKey' => 'hydration.tea', 'metricKey' => 'hydration', 'category' => 'journal', 'periods' => ['day', 'week', 'month'], 'comparators' => ['gte', 'lte'], 'kind' => 'count', 'unit' => null, 'options' => ['tea'], 'minimum' => 1.0],
		'break.all' => ['targetKey' => 'break.all', 'metricKey' => 'break', 'category' => 'journal', 'periods' => ['day', 'week', 'month'], 'comparators' => ['gte', 'lte'], 'kind' => 'count', 'unit' => null, 'options' => ['short', 'regular', 'short_walk', 'long_walk', 'mindfulness', 'fresh_air'], 'minimum' => 1.0],
		'break.mindfulness' => ['targetKey' => 'break.mindfulness', 'metricKey' => 'break', 'category' => 'journal', 'periods' => ['day', 'week', 'month'], 'comparators' => ['gte', 'lte'], 'kind' => 'count', 'unit' => null, 'options' => ['mindfulness'], 'minimum' => 1.0],
		'steps' => ['targetKey' => 'steps', 'metricKey' => 'steps', 'category' => 'daily_value', 'periods' => ['day', 'week', 'month'], 'comparators' => ['gte', 'lte'], 'kind' => 'period_value', 'unit' => 'steps', 'minimum' => 1.0],
		'job_satisfaction' => ['targetKey' => 'job_satisfaction', 'metricKey' => 'job_satisfaction', 'category' => 'daily_value', 'periods' => ['day'], 'comparators' => ['gte', 'lte'], 'kind' => 'period_value', 'unit' => null, 'minimum' => 1.0, 'maximum' => 5.0],
		'pulse' => ['targetKey' => 'pulse', 'metricKey' => 'pulse', 'category' => 'measurement', 'periods' => ['day', 'week', 'month'], 'comparators' => ['gte', 'lte'], 'kind' => 'threshold_occurrence', 'unit' => 'bpm', 'minimum' => 1.0],
		'blood_pressure' => ['targetKey' => 'blood_pressure', 'metricKey' => 'blood_pressure', 'category' => 'measurement', 'periods' => ['day', 'week', 'month'], 'comparators' => ['gte'], 'kind' => 'count', 'unit' => null, 'minimum' => 1.0],
		'weight' => ['targetKey' => 'weight', 'metricKey' => 'weight', 'category' => 'daily_value', 'periods' => ['long_term'], 'comparators' => ['gte', 'lte'], 'kind' => 'latest_value', 'unit' => 'kg', 'minimum' => 0.000001],
	];

	/** @return list<HealthGoalTarget> */
	public function getDefinitions(): array {
		return array_values(self::TARGETS);
	}

	/** @return HealthGoalTarget */
	public function getDefinition(mixed $targetKey): array {
		if (!is_string($targetKey) || !isset(self::TARGETS[$targetKey])) {
			throw new InvalidEntryException('Unsupported goal target.');
		}

		return self::TARGETS[$targetKey];
	}

	/** @return 'day'|'week'|'month'|'long_term' */
	public function validatePeriod(string $targetKey, mixed $period): string {
		$definition = $this->getDefinition($targetKey);
		if (!is_string($period) || !in_array($period, $definition['periods'], true)) {
			throw new InvalidEntryException('Unsupported goal period.');
		}
		return $period;
	}

	/** @return 'gte'|'lte' */
	public function validateComparator(string $targetKey, mixed $comparator): string {
		$definition = $this->getDefinition($targetKey);
		if (!is_string($comparator) || !in_array($comparator, $definition['comparators'], true)) {
			throw new InvalidEntryException('Unsupported goal direction.');
		}
		return $comparator;
	}

	public function validateTargetValue(string $targetKey, mixed $targetValue): float {
		$definition = $this->getDefinition($targetKey);
		if ((!is_int($targetValue) && !is_float($targetValue)) || !is_finite((float)$targetValue)) {
			throw new InvalidEntryException('Goal target must be a finite number.');
		}
		$value = (float)$targetValue;
		if (isset($definition['minimum']) && $value < $definition['minimum']) {
			throw new InvalidEntryException('Goal target is below the supported range.');
		}
		if (isset($definition['maximum']) && $value > $definition['maximum']) {
			throw new InvalidEntryException('Goal target is above the supported range.');
		}
		return $value;
	}
}
