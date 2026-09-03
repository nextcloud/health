<?php

declare(strict_types=1);

namespace OCA\Health\Service;

use DateTimeImmutable;
use DateTimeZone;
use OCA\Health\Db\DailyValueMapper;
use OCA\Health\Db\EntryMapper;
use OCA\Health\Db\Goal;
use OCA\Health\Db\GoalMapper;
use OCA\Health\Db\GoalRevision;
use OCA\Health\Db\GoalRevisionMapper;
use OCA\Health\Db\MeasurementMapper;
use OCA\Health\Exception\InvalidEntryException;
use OCP\IDateTimeZone;

/**
 * Builds owner-scoped, day-level descriptive statistics from canonical Health data.
 *
 * The service deliberately aggregates after owner-scoped range queries instead of
 * relying on database-specific date functions. This keeps local-day semantics
 * portable across supported Nextcloud database backends.
 *
 * @psalm-import-type HealthGoalTarget from \OCA\Health\ResponseDefinitions
 * @psalm-import-type HealthStatisticsMetric from \OCA\Health\ResponseDefinitions
 * @psalm-import-type HealthStatisticsResponse from \OCA\Health\ResponseDefinitions
 */
class StatisticsService {
	private const DEFAULT_PERIOD = 'last_30_days';

	/** @var list<string> */
	private const PERIODS = [
		'this_week',
		'last_week',
		'last_7_days',
		'last_30_days',
		'this_month',
		'last_month',
		'this_year',
		'last_year',
	];

	private DateTimeZone $utc;

	/** @psalm-suppress PossiblyUnusedMethod Instantiated by Nextcloud dependency injection. */
	public function __construct(
		private EntryMapper $entryMapper,
		private MeasurementMapper $measurementMapper,
		private DailyValueMapper $dailyValueMapper,
		private GoalMapper $goalMapper,
		private GoalRevisionMapper $goalRevisionMapper,
		private MetricService $metricService,
		private GoalTargetRegistry $goalTargetRegistry,
		private ConfigurationService $configurationService,
		private IDateTimeZone $dateTimeZone,
	) {
		$this->utc = new DateTimeZone('UTC');
	}

	/**
	 * @return HealthStatisticsResponse
	 */
	public function get(string $userId, mixed $period = self::DEFAULT_PERIOD, mixed $metrics = null): array {
		$configuration = $this->validateConfiguration($userId, $period, $metrics);
		$selection = $this->selectPeriod($userId, $configuration['period']);
		$metricKeys = $configuration['metricKeys'];
		$definitions = [];
		foreach ($metricKeys as $metricKey) {
			$definitions[$metricKey] = $this->metricService->getDefinition($metricKey);
		}

		$dateKeys = $this->dateKeys($selection['from'], $selection['to']);
		$sourceData = $this->sourceData($userId, $definitions, $selection, $dateKeys);
		$goals = $this->goalSegments($userId, $definitions, $selection);
		/** @var list<HealthStatisticsMetric> $result */
		$result = [];

		foreach ($metricKeys as $metricKey) {
			$definition = $definitions[$metricKey];
			if ($metricKey === 'blood_pressure') {
				$statistics = $this->bloodPressureStatistics(
					$dateKeys,
					$sourceData['bloodPressure'],
					$sourceData['sourceCounts'][$metricKey],
				);
			} elseif ($definition['valueType'] === 'event') {
				$statistics = $this->eventStatistics(
					$dateKeys,
					$sourceData['events'][$metricKey] ?? [],
					$sourceData['eventGroups'][$metricKey] ?? [],
					$sourceData['sourceCounts'][$metricKey],
				);
			} else {
				$statistics = $this->numericStatistics(
					$dateKeys,
					$sourceData['numeric'][$metricKey] ?? [],
					$sourceData['sourceCounts'][$metricKey],
				);
			}

			/** @var HealthStatisticsMetric $metric */
			$metric = [
				'metricKey' => $metricKey,
				'category' => $definition['category'],
				'valueType' => $definition['valueType'],
				'canonicalUnit' => $definition['canonicalUnit'],
				'minimum' => $definition['minimum'],
				'maximum' => $definition['maximum'],
				'series' => $statistics['series'],
				'summary' => $statistics['summary'],
				'goals' => $goals[$metricKey],
			];
			$result[] = $metric;
		}

		/** @var HealthStatisticsResponse $response */
		$response = [
			'period' => $selection['period'],
			'from' => $selection['from']->format('Y-m-d'),
			'to' => $selection['to']->format('Y-m-d'),
			'metrics' => $result,
		];
		return $response;
	}

	/**
	 * Validate the reusable, public Statistics configuration shared by interactive and saved views.
	 *
	 * @return array{
	 *   period: 'this_week'|'last_week'|'last_7_days'|'last_30_days'|'this_month'|'last_month'|'this_year'|'last_year',
	 *   metricKeys: list<string>
	 * }
	 */
	public function validateConfiguration(string $userId, mixed $period = self::DEFAULT_PERIOD, mixed $metrics = null): array {
		if ($userId === '') {
			throw new InvalidEntryException('An authenticated user is required.');
		}

		return [
			'period' => $this->validatePeriod($period),
			'metricKeys' => $this->selectedMetricKeys($userId, $metrics),
		];
	}

	/**
	 * @return array{period: string, from: DateTimeImmutable, to: DateTimeImmutable, timezone: DateTimeZone}
	 */
	private function selectPeriod(string $userId, mixed $period): array {
		$period = $this->validatePeriod($period);

		$timezone = $this->dateTimeZone->getTimeZone(false, $userId);
		$today = (new DateTimeImmutable('now', $this->utc))->setTimezone($timezone)->setTime(0, 0);
		$tomorrow = $today->modify('+1 day');
		$weekStart = $today->modify('monday this week')->setTime(0, 0);
		$monthStart = $today->modify('first day of this month')->setTime(0, 0);
		$yearStart = $today->setDate((int)$today->format('Y'), 1, 1)->setTime(0, 0);

		[$from, $to] = match ($period) {
			'this_week' => [$weekStart, $tomorrow],
			'last_week' => [$weekStart->modify('-1 week'), $weekStart],
			'last_7_days' => [$today->modify('-6 days'), $tomorrow],
			'last_30_days' => [$today->modify('-29 days'), $tomorrow],
			'this_month' => [$monthStart, $tomorrow],
			'last_month' => [$monthStart->modify('-1 month'), $monthStart],
			'this_year' => [$yearStart, $tomorrow],
			'last_year' => [$yearStart->modify('-1 year'), $yearStart],
		};

		return [
			'period' => $period,
			'from' => $from,
			'to' => $to,
			'timezone' => $timezone,
		];
	}

	/** @return 'this_week'|'last_week'|'last_7_days'|'last_30_days'|'this_month'|'last_month'|'this_year'|'last_year' */
	private function validatePeriod(mixed $period): string {
		if (!is_string($period) || !in_array($period, self::PERIODS, true)) {
			throw new InvalidEntryException('Unsupported statistics period.');
		}

		return $period;
	}

	/** @return list<string> */
	private function selectedMetricKeys(string $userId, mixed $metrics): array {
		if ($metrics === null) {
			$configuration = $this->configurationService->get($userId)['metrics'];
			$defaults = [];
			foreach (MetricService::getMetricDefinitions() as $definition) {
				$metricKey = $definition['metricKey'];
				if ($definition['category'] === 'journal' && ($configuration[$metricKey]['enabled'] ?? false) === true) {
					$defaults[] = $metricKey;
				}
			}
			return $defaults;
		}

		if (!is_string($metrics)) {
			throw new InvalidEntryException('metrics must be a comma-separated string.');
		}
		if (trim($metrics) === '') {
			return [];
		}
		$requested = explode(',', $metrics);

		$selected = [];
		foreach ($requested as $requestedMetric) {
			if (trim($requestedMetric) === '') {
				throw new InvalidEntryException('metrics must contain supported metric keys.');
			}

			$metricKey = $this->metricService->validateMetricKey(trim($requestedMetric));
			if (!in_array($metricKey, $selected, true)) {
				$selected[] = $metricKey;
			}
		}

		return $selected;
	}

	/** @return list<string> */
	private function dateKeys(DateTimeImmutable $from, DateTimeImmutable $to): array {
		$dates = [];
		for ($date = $from; $date < $to; $date = $date->modify('+1 day')) {
			$dates[] = $date->format('Y-m-d');
		}

		return $dates;
	}

	/**
	 * @param array<string, array<string, mixed>> $definitions
	 * @param array{period: string, from: DateTimeImmutable, to: DateTimeImmutable, timezone: DateTimeZone} $selection
	 * @param list<string> $dateKeys
	 * @return array{
	 *   numeric: array<string, array<string, list<float>>>,
	 *   events: array<string, array<string, array<string, int>>>,
	 *   eventGroups: array<string, array<string, list<string>>>,
	 *   sourceCounts: array<string, int>,
	 *   bloodPressure: array{
	 *     systolic: array<string, list<float>>,
	 *     diastolic: array<string, list<float>>,
	 *     groups: array<string, true>
	 *   }
	 * }
	 */
	private function sourceData(string $userId, array $definitions, array $selection, array $dateKeys): array {
		$numeric = [];
		$events = [];
		$eventGroups = [];
		$sourceCounts = [];
		$bloodPressure = ['systolic' => [], 'diastolic' => [], 'groups' => []];
		$journalMetricKeys = [];
		$dailyValueMetricKeys = [];
		$measurementMetricKeys = [];
		$knownDates = array_fill_keys($dateKeys, true);

		foreach ($definitions as $metricKey => $definition) {
			$sourceCounts[$metricKey] = 0;
			if ($definition['category'] === 'journal') {
				$journalMetricKeys[] = $metricKey;
			}
			if ($definition['category'] === 'daily_value') {
				$dailyValueMetricKeys[] = $metricKey;
			}
			if ($definition['category'] === 'measurement') {
				if ($metricKey === 'blood_pressure') {
					$measurementMetricKeys[] = 'blood_pressure_systolic';
					$measurementMetricKeys[] = 'blood_pressure_diastolic';
				} else {
					$measurementMetricKeys[] = $metricKey;
				}
			}
			if ($definition['valueType'] === 'event') {
				$events[$metricKey] = [];
				$eventGroups[$metricKey] = $this->eventSubseriesGroups($metricKey);
			} elseif ($metricKey !== 'blood_pressure') {
				$numeric[$metricKey] = [];
			}
		}

		$fromUtc = $selection['from']->setTimezone($this->utc);
		$toUtc = $selection['to']->setTimezone($this->utc);
		foreach ($this->entryMapper->findForUserMetricRange($userId, $journalMetricKeys, $fromUtc, $toUtc) as $entry) {
			$metricKey = $entry->getMetricKey();
			$dateKey = $entry->getRecordedAt()->setTimezone($selection['timezone'])->format('Y-m-d');
			if (!isset($knownDates[$dateKey])) {
				continue;
			}

			if (($definitions[$metricKey]['valueType'] ?? null) === 'event') {
				$optionValue = $entry->getOptionValue();
				if ($optionValue === null) {
					continue;
				}
				$subseriesKey = $this->eventSubseriesKey($optionValue, $eventGroups[$metricKey]);
				if ($subseriesKey === null) {
					continue;
				}
				$events[$metricKey][$dateKey][$subseriesKey] = ($events[$metricKey][$dateKey][$subseriesKey] ?? 0) + 1;
				$sourceCounts[$metricKey]++;
				continue;
			}

			$numericValue = $entry->getNumericValue();
			if ($numericValue !== null) {
				$this->appendNumericValue($numeric, $metricKey, $dateKey, (float)$numericValue);
				$sourceCounts[$metricKey]++;
			}
		}

		foreach ($this->measurementMapper->findForUserMetricsRange($userId, $measurementMetricKeys, $fromUtc, $toUtc) as $measurement) {
			$dateKey = $measurement->getRecordedAt()->setTimezone($selection['timezone'])->format('Y-m-d');
			if (!isset($knownDates[$dateKey])) {
				continue;
			}

			$measurementKey = $measurement->getMetricKey();
			if ($measurementKey === 'blood_pressure_systolic' || $measurementKey === 'blood_pressure_diastolic') {
				if (!isset($definitions['blood_pressure'])) {
					continue;
				}
				$subseriesKey = $measurementKey === 'blood_pressure_systolic' ? 'systolic' : 'diastolic';
				$this->appendBloodPressureValue($bloodPressure, $subseriesKey, $dateKey, (float)$measurement->getNumericValue());
				$groupKey = $measurement->getGroupId() ?? 'measurement-' . $measurement->getId();
				$bloodPressure['groups'][$groupKey] = true;
				continue;
			}

			if (isset($numeric[$measurementKey])) {
				$this->appendNumericValue($numeric, $measurementKey, $dateKey, (float)$measurement->getNumericValue());
				$sourceCounts[$measurementKey]++;
			}
		}

		$sourceCounts['blood_pressure'] = isset($definitions['blood_pressure']) ? count($bloodPressure['groups']) : 0;
		foreach ($this->dailyValueMapper->findForUserMetricDateRange(
			$userId,
			$dailyValueMetricKeys,
			$selection['from']->format('Y-m-d'),
			$selection['to']->format('Y-m-d'),
		) as $dailyValue) {
			$metricKey = $dailyValue->getMetricKey();
			$dateKey = $dailyValue->getLocalDate();
			if (isset($numeric[$metricKey]) && isset($knownDates[$dateKey])) {
				$this->appendNumericValue($numeric, $metricKey, $dateKey, (float)$dailyValue->getNumericValue());
				$sourceCounts[$metricKey]++;
			}
		}

		return [
			'numeric' => $numeric,
			'events' => $events,
			'eventGroups' => $eventGroups,
			'sourceCounts' => $sourceCounts,
			'bloodPressure' => $bloodPressure,
		];
	}

	/**
	 * @param array<string, array<string, list<float>>> $numeric
	 */
	private function appendNumericValue(array &$numeric, string $metricKey, string $dateKey, float $value): void {
		$numeric[$metricKey][$dateKey] ??= [];
		$numeric[$metricKey][$dateKey][] = $value;
	}

	/**
	 * @param array{
	 *   systolic: array<string, list<float>>,
	 *   diastolic: array<string, list<float>>,
	 *   groups: array<string, true>
	 * } $bloodPressure
	 * @param 'systolic'|'diastolic' $subseriesKey
	 */
	private function appendBloodPressureValue(array &$bloodPressure, string $subseriesKey, string $dateKey, float $value): void {
		if ($subseriesKey === 'systolic') {
			$bloodPressure['systolic'][$dateKey] ??= [];
			$bloodPressure['systolic'][$dateKey][] = $value;
			return;
		}
		$bloodPressure['diastolic'][$dateKey] ??= [];
		$bloodPressure['diastolic'][$dateKey][] = $value;
	}

	/** @return array<string, list<string>> */
	private function eventSubseriesGroups(string $metricKey): array {
		$definition = $this->metricService->getDefinition($metricKey);
		$allowedOptions = [];
		foreach ($definition['allowedOptions'] ?? [] as $option) {
			$allowedOptions[] = $option;
		}

		if ($metricKey === 'hydration') {
			$groups = [];
			$assignedOptions = [];
			foreach (['water', 'coffee', 'tea'] as $groupKey) {
				$target = $this->goalTargetRegistry->getDefinition('hydration.' . $groupKey);
				$options = [];
				foreach ($target['options'] ?? [] as $option) {
					$options[] = $option;
					$assignedOptions[] = $option;
				}
				$groups[$groupKey] = $options;
			}
			$groups['other'] = array_values(array_diff($allowedOptions, $assignedOptions));
			return $groups;
		}

		$groups = [];
		foreach ($allowedOptions as $option) {
			$groups[$option] = [$option];
		}
		return $groups;
	}

	/** @param array<string, list<string>> $groups */
	private function eventSubseriesKey(string $optionValue, array $groups): ?string {
		foreach ($groups as $groupKey => $options) {
			if (in_array($optionValue, $options, true)) {
				return $groupKey;
			}
		}

		return null;
	}

	/**
	 * @param list<string> $dateKeys
	 * @param array<string, list<float>> $values
	 * @return array{series: list<array<string, mixed>>, summary: array<string, mixed>}
	 */
	private function numericStatistics(array $dateKeys, array $values, int $sourceCount): array {
		$series = [];
		$dailyValues = [];
		foreach ($dateKeys as $dateKey) {
			$rawValues = $values[$dateKey] ?? [];
			$value = $rawValues === [] ? null : array_sum($rawValues) / count($rawValues);
			$series[] = ['date' => $dateKey, 'value' => $value, 'subseries' => null];
			$dailyValues[] = $value;
		}

		return [
			'series' => $series,
			'summary' => $this->numericSummary($dailyValues, $sourceCount),
		];
	}

	/**
	 * @param list<string> $dateKeys
	 * @param array<string, array<string, int>> $values
	 * @param array<string, list<string>> $groups
	 * @return array{series: list<array<string, mixed>>, summary: array<string, mixed>}
	 */
	private function eventStatistics(array $dateKeys, array $values, array $groups, int $sourceCount): array {
		$series = [];
		$dailyValues = [];
		$subseriesValues = [];
		foreach (array_keys($groups) as $groupKey) {
			$subseriesValues[$groupKey] = [];
		}

		foreach ($dateKeys as $dateKey) {
			$subseries = [];
			$total = 0.0;
			foreach (array_keys($groups) as $groupKey) {
				$value = (float)($values[$dateKey][$groupKey] ?? 0);
				$subseries[$groupKey] = $value;
				$subseriesValues[$groupKey][] = $value;
				$total += $value;
			}
			$series[] = ['date' => $dateKey, 'value' => $total, 'subseries' => $subseries];
			$dailyValues[] = $total;
		}

		$subseriesSummary = [];
		foreach ($subseriesValues as $groupKey => $dailySubseriesValues) {
			$subseriesSummary[$groupKey] = $this->eventSummary($dailySubseriesValues, (int)array_sum($dailySubseriesValues));
		}
		$summary = $this->eventSummary($dailyValues, $sourceCount);
		$summary['subseries'] = $subseriesSummary;

		return [
			'series' => $series,
			'summary' => $summary,
		];
	}

	/**
	 * @param list<float> $dailyValues
	 * @return array{average: float|null, minimum: float|null, maximum: float|null, count: int, activeDays: int, subseries: null}
	 */
	private function eventSummary(array $dailyValues, int $sourceCount): array {
		return [
			'average' => $dailyValues === [] ? null : array_sum($dailyValues) / count($dailyValues),
			'minimum' => $dailyValues === [] ? null : min($dailyValues),
			'maximum' => $dailyValues === [] ? null : max($dailyValues),
			'count' => $sourceCount,
			'activeDays' => count(array_filter($dailyValues, static fn (float $value): bool => $value > 0)),
			'subseries' => null,
		];
	}

	/**
	 * @param list<string> $dateKeys
	 * @param array{
	 *   systolic: array<string, list<float>>,
	 *   diastolic: array<string, list<float>>,
	 *   groups: array<string, true>
	 * } $bloodPressure
	 * @return array{series: list<array<string, mixed>>, summary: array<string, mixed>}
	 */
	private function bloodPressureStatistics(array $dateKeys, array $bloodPressure, int $sourceCount): array {
		$series = [];
		$values = ['systolic' => [], 'diastolic' => []];
		$activeDays = 0;
		foreach ($dateKeys as $dateKey) {
			$subseries = [];
			$hasValue = false;
			foreach (['systolic', 'diastolic'] as $subseriesKey) {
				$rawValues = $subseriesKey === 'systolic'
					? ($bloodPressure['systolic'][$dateKey] ?? [])
					: ($bloodPressure['diastolic'][$dateKey] ?? []);
				$value = $rawValues === [] ? null : array_sum($rawValues) / count($rawValues);
				$subseries[$subseriesKey] = $value;
				$values[$subseriesKey][] = $value;
				$hasValue = $hasValue || $value !== null;
			}
			if ($hasValue) {
				$activeDays++;
			}
			$series[] = ['date' => $dateKey, 'value' => null, 'subseries' => $subseries];
		}

		return [
			'series' => $series,
			'summary' => [
				'average' => null,
				'minimum' => null,
				'maximum' => null,
				'count' => $sourceCount,
				'activeDays' => $activeDays,
				'subseries' => [
					'systolic' => $this->numericSummary($values['systolic'], $sourceCount),
					'diastolic' => $this->numericSummary($values['diastolic'], $sourceCount),
				],
			],
		];
	}

	/**
	 * @param list<float|null> $dailyValues
	 * @return array{average: float|null, minimum: float|null, maximum: float|null, count: int, activeDays: int, subseries: null}
	 */
	private function numericSummary(array $dailyValues, int $sourceCount): array {
		$points = [];
		foreach ($dailyValues as $dailyValue) {
			if ($dailyValue !== null) {
				$points[] = $dailyValue;
			}
		}

		if ($points === []) {
			return [
				'average' => null,
				'minimum' => null,
				'maximum' => null,
				'count' => $sourceCount,
				'activeDays' => 0,
				'subseries' => null,
			];
		}

		return [
			'average' => array_sum($points) / count($points),
			'minimum' => min($points),
			'maximum' => max($points),
			'count' => $sourceCount,
			'activeDays' => count($points),
			'subseries' => null,
		];
	}

	/**
	 * @param array<string, array<string, mixed>> $definitions
	 * @param array{period: string, from: DateTimeImmutable, to: DateTimeImmutable, timezone: DateTimeZone} $selection
	 * @return array<string, list<array<string, mixed>>>
	 */
	private function goalSegments(string $userId, array $definitions, array $selection): array {
		$result = [];
		foreach (array_keys($definitions) as $metricKey) {
			$result[$metricKey] = [];
		}

		$fromDate = $selection['from']->format('Y-m-d');
		$toDate = $selection['to']->format('Y-m-d');
		foreach ($this->goalMapper->findAllForUser($userId) as $goal) {
			if ($goal->getPeriod() !== 'day' || (!$goal->isActive() && $goal->getRetiredAt() === null)) {
				continue;
			}

			$target = $this->goalTargetRegistry->getDefinition($goal->getTargetKey());
			$metricKey = $target['metricKey'];
			if (!isset($definitions[$metricKey])) {
				continue;
			}
			$seriesKey = $this->goalSeriesKey($target);
			if ($seriesKey === null) {
				continue;
			}

			$retiredAt = $goal->getRetiredAt();
			$retiredDate = $retiredAt?->setTimezone($selection['timezone'])->format('Y-m-d');
			foreach ($this->goalRevisionMapper->findForGoalDateRange($goal->getId(), $fromDate, $toDate) as $revision) {
				$effectiveTo = $this->effectiveTo($revision, $retiredDate);
				if ($revision->getEffectiveFrom() >= $toDate || ($effectiveTo !== null && $effectiveTo < $fromDate)) {
					continue;
				}

				$result[$metricKey][] = $this->formatGoalSegment($goal, $revision, $target, $seriesKey, $effectiveTo);
			}
		}

		foreach ($result as &$segments) {
			usort($segments, static fn (array $left, array $right): int => [$left['effectiveFrom'], $left['goalId']] <=> [$right['effectiveFrom'], $right['goalId']]);
		}
		unset($segments);

		return $result;
	}

	/** @param HealthGoalTarget $target */
	private function goalSeriesKey(array $target): ?string {
		return match ($target['targetKey']) {
			'hydration.water' => 'water',
			'hydration.coffee' => 'coffee',
			'hydration.tea' => 'tea',
			'break.all' => 'total',
			'break.mindfulness' => 'mindfulness',
			default => in_array($target['kind'], ['period_value', 'threshold_occurrence'], true) ? 'value' : null,
		};
	}

	private function effectiveTo(GoalRevision $revision, ?string $retiredDate): ?string {
		$effectiveTo = $revision->getEffectiveTo();
		if ($retiredDate !== null && ($effectiveTo === null || $retiredDate < $effectiveTo)) {
			return $retiredDate;
		}
		return $effectiveTo;
	}

	/**
	 * @param HealthGoalTarget $target
	 * @return array<string, mixed>
	 */
	private function formatGoalSegment(Goal $goal, GoalRevision $revision, array $target, string $seriesKey, ?string $effectiveTo): array {
		$options = [];
		foreach ($target['options'] ?? [] as $option) {
			$options[] = $option;
		}

		return [
			'goalId' => $goal->getId(),
			'targetKey' => $target['targetKey'],
			'metricKey' => $target['metricKey'],
			'kind' => $target['kind'],
			'seriesKey' => $seriesKey,
			'comparator' => $revision->getComparator(),
			'targetValue' => (float)$revision->getTargetValue(),
			'options' => $options === [] ? null : $options,
			'effectiveFrom' => $revision->getEffectiveFrom(),
			'effectiveTo' => $effectiveTo,
		];
	}
}
