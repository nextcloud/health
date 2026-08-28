<?php

declare(strict_types=1);

namespace OCA\Health\Service;

use OCA\Health\Db\ConfigurationMapper;
use OCA\Health\Exception\InvalidEntryException;

/**
 * @psalm-import-type HealthConfiguration from \OCA\Health\ResponseDefinitions
 * @psalm-import-type HealthMetricDefinition from \OCA\Health\ResponseDefinitions
 */
class ConfigurationService {
	/** @psalm-suppress PossiblyUnusedMethod Instantiated through Nextcloud dependency injection. */
	public function __construct(
		private ConfigurationMapper $configurationMapper,
		private MetricService $metricService,
		private UnitConversionService $unitConversionService,
	) {
	}

	/** @return HealthConfiguration */
	public function get(string $userId): array {
		$stored = $this->configurationMapper->findMetricsForUser($userId);
		$metrics = [];
		foreach (MetricService::getMetricDefinitions() as $definition) {
			$metricKey = $definition['metricKey'];
			$metrics[$metricKey] = $stored[$metricKey] ?? $this->defaultMetricConfiguration($definition);
		}
		$profile = $this->configurationMapper->findProfileForUser($userId) ?? ['heightCm' => null, 'heightDisplayUnit' => 'cm', 'dateOfBirth' => null, 'growthReferenceSex' => null];
		/** @var HealthConfiguration $configuration */
		$configuration = ['profile' => $profile, 'metrics' => $metrics, 'searchDailyNotes' => $this->configurationMapper->findSearchDailyNotesForUser($userId)];
		return $configuration;
	}

	/** @return HealthConfiguration */
	public function update(string $userId, mixed $profile, mixed $metrics, mixed $searchDailyNotes): array {
		$current = $this->get($userId);
		if ($profile !== null) {
			if (!is_array($profile)) {
				throw new InvalidEntryException('profile must be an object.');
			}
			$heightUnit = $profile['heightUnit'] ?? $current['profile']['heightDisplayUnit'];
			$heightProvided = array_key_exists('height', $profile);
			$dateOfBirth = array_key_exists('dateOfBirth', $profile) ? $profile['dateOfBirth'] : $current['profile']['dateOfBirth'];
			$growthReferenceSex = array_key_exists('growthReferenceSex', $profile) ? $profile['growthReferenceSex'] : $current['profile']['growthReferenceSex'];
			if (!is_string($heightUnit) || !in_array($heightUnit, ['cm', 'in'], true)) {
				throw new InvalidEntryException('Unsupported height unit.');
			}
			if ($dateOfBirth !== null && (!is_string($dateOfBirth) || !$this->isDate($dateOfBirth))) {
				throw new InvalidEntryException('dateOfBirth must be a valid YYYY-MM-DD date.');
			}
			if ($growthReferenceSex !== null && (!is_string($growthReferenceSex) || !in_array($growthReferenceSex, ['female', 'male'], true))) {
				throw new InvalidEntryException('Unsupported growth reference sex.');
			}
			$heightCm = !$heightProvided ? $current['profile']['heightCm'] : (($profile['height'] ?? null) === null ? null : $this->unitConversionService->toCanonical('height', $profile['height'], $heightUnit));
			$this->configurationMapper->saveProfileForUser($userId, $heightCm, $heightUnit, $dateOfBirth, $growthReferenceSex);
		}
		if ($metrics !== null) {
			if (!is_array($metrics)) {
				throw new InvalidEntryException('metrics must be an object.');
			}
			foreach ($metrics as $metricKey => $configuration) {
				if (!is_string($metricKey) || !is_array($configuration)) {
					throw new InvalidEntryException('Invalid metric configuration.');
				}
				$this->metricService->validateMetricKey($metricKey);
				$existing = $current['metrics'][$metricKey];
				$enabled = $configuration['enabled'] ?? $existing['enabled'];
				$checkIn = $configuration['checkInEnabled'] ?? $existing['checkInEnabled'];
				$checkOut = $configuration['checkOutEnabled'] ?? $existing['checkOutEnabled'];
				$displayUnit = $configuration['displayUnit'] ?? $existing['displayUnit'];
				if (!is_bool($enabled) || !is_bool($checkIn) || !is_bool($checkOut)) {
					throw new InvalidEntryException('Metric flags must be booleans.');
				}
				if ($displayUnit !== null && (!is_string($displayUnit) || !in_array($displayUnit, $this->metricService->getSupportedUnits($metricKey), true))) {
					throw new InvalidEntryException('Unsupported display unit.');
				}
				$this->configurationMapper->saveMetricForUser($userId, $metricKey, ['enabled' => $enabled, 'checkInEnabled' => $checkIn, 'checkOutEnabled' => $checkOut, 'displayUnit' => $displayUnit]);
			}
		}
		if ($searchDailyNotes !== null) {
			if (!is_bool($searchDailyNotes)) {
				throw new InvalidEntryException('searchDailyNotes must be a boolean.');
			}
			$this->configurationMapper->saveSearchDailyNotesForUser($userId, $searchDailyNotes);
		}
		return $this->get($userId);
	}

	public function isDailyNoteSearchEnabled(string $userId): bool {
		return $this->configurationMapper->findSearchDailyNotesForUser($userId);
	}

	private function isDate(string $date): bool {
		return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $date, $matches) === 1 && checkdate((int)$matches[2], (int)$matches[3], (int)$matches[1]);
	}

	/** @param HealthMetricDefinition $definition @return array{enabled: bool, checkInEnabled: bool, checkOutEnabled: bool, displayUnit: string|null} */
	private function defaultMetricConfiguration(array $definition): array {
		$units = $definition['supportedUnits'];
		return [
			'enabled' => $definition['category'] === 'journal',
			'checkInEnabled' => false,
			'checkOutEnabled' => false,
			'displayUnit' => $units === [] ? null : $units[0],
		];
	}
}
