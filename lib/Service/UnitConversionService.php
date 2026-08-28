<?php

declare(strict_types=1);

namespace OCA\Health\Service;

use OCA\Health\Exception\InvalidEntryException;

/** @psalm-suppress PossiblyUnusedMethod Instantiated through Nextcloud dependency injection. */
class UnitConversionService {
	/** @psalm-suppress PossiblyUnusedMethod Instantiated through Nextcloud dependency injection. */
	public function __construct(
		private MetricService $metricService,
	) {
	}

	public function toCanonical(string $metricKey, mixed $value, mixed $unit): float {
		if (!is_int($value) && !is_float($value)) {
			throw new InvalidEntryException('Numeric value must be a JSON number.');
		}
		$supportedUnits = $metricKey === 'height' ? ['cm', 'in'] : $this->metricService->getSupportedUnits($metricKey);
		if (!is_finite((float)$value)) {
			throw new InvalidEntryException('Unsupported unit or numeric value.');
		}

		$value = (float)$value;
		if ($supportedUnits === []) {
			if ($unit !== null) {
				throw new InvalidEntryException('Unsupported unit or numeric value.');
			}
			return $value;
		}
		if (!is_string($unit) || !in_array($unit, $supportedUnits, true)) {
			throw new InvalidEntryException('Unsupported unit or numeric value.');
		}
		return match ($metricKey . ':' . $unit) {
			'weight:lb' => $value / 2.20462262,
			'weight:st' => $value * 6.35029318,
			'waist:in', 'hip:in', 'height:in' => $value * 2.54,
			'temperature:fahrenheit' => ($value - 32) * 5 / 9,
			'blood_glucose:mg_dl' => $value / 18.0182,
			'blood_pressure:kpa' => $value / 0.133322,
			default => $value,
		};
	}

	/** @psalm-suppress PossiblyUnusedMethod Public conversion operation retained for presentation integrations. */
	public function fromCanonical(string $metricKey, float $value, string $unit): float {
		$supportedUnits = $metricKey === 'height' ? ['cm', 'in'] : $this->metricService->getSupportedUnits($metricKey);
		if (!in_array($unit, $supportedUnits, true)) {
			throw new InvalidEntryException('Unsupported display unit.');
		}

		return match ($metricKey . ':' . $unit) {
			'weight:lb' => $value * 2.20462262,
			'weight:st' => $value / 6.35029318,
			'waist:in', 'hip:in', 'height:in' => $value / 2.54,
			'temperature:fahrenheit' => $value * 9 / 5 + 32,
			'blood_glucose:mg_dl' => $value * 18.0182,
			'blood_pressure:kpa' => $value * 0.133322,
			default => $value,
		};
	}
}
