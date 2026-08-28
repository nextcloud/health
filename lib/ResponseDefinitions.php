<?php

declare(strict_types=1);

namespace OCA\Health;

/**
 * @psalm-type HealthEntry = array{
 *   id: int,
 *   metricKey: string,
 *   numericValue: int|float|null,
 *   optionValue: string|null,
 *   context: string,
 *   source: 'web'|'api'|'mobile'|'notification',
 *   recordedAt: string,
 *   createdAt: string,
 *   updatedAt: string,
 *   note: string|null,
 * }
 *
 * @psalm-type HealthEntriesPage = array{
 *   entries: list<HealthEntry>,
 *   nextCursor: string|null,
 * }
 *
 * @psalm-type HealthDailyNote = array{
 *   date: string,
 *   content: string|null,
 *   createdAt: string|null,
 *   updatedAt: string|null,
 * }
 *
 * @psalm-type HealthDailyValue = array{
 *   id: int, metricKey: string, numericValue: float, localDate: string,
 *   createdAt: string, updatedAt: string, bmi: float|null
 * }
 * @psalm-type HealthDailyValuesPage = array{values: list<HealthDailyValue>}
 * @psalm-type HealthSingleMeasurement = array{
 *   id: int, metricKey: string, numericValue: float, values: null, context: string,
 *   source: string, recordedAt: string, createdAt: string, updatedAt: string, note: string|null
 * }
 * @psalm-type HealthBloodPressureMeasurement = array{
 *   id: int, metricKey: 'blood_pressure', numericValue: null,
 *   values: array{systolic: float, diastolic: float}, context: string,
 *   source: string, recordedAt: string, createdAt: string, updatedAt: string, note: string|null
 * }
 * @psalm-type HealthMeasurement = HealthSingleMeasurement|HealthBloodPressureMeasurement
 * @psalm-type HealthMeasurementsPage = array{measurements: list<HealthMeasurement>}
 * @psalm-type HealthRoutineResult = array{
 *   createdEntries: list<HealthEntry>,
 *   createdMeasurements: list<HealthMeasurement>,
 *   updatedDailyValues: list<HealthDailyValue>
 * }
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
 * @psalm-type HealthConfiguration = array{
 *   profile: array{heightCm: float|null, heightDisplayUnit: 'cm'|'in', dateOfBirth: string|null, growthReferenceSex: 'female'|'male'|null},
 *   metrics: array<string, array{enabled: bool, checkInEnabled: bool, checkOutEnabled: bool, displayUnit: string|null}>,
 *   searchDailyNotes: bool
 * }
 * @psalm-type HealthGoalRevision = array{
 *   id: int, comparator: 'gte'|'lte', targetValue: float, secondaryTargetValue: float|null,
 *   effectiveFrom: string, effectiveTo: string|null
 * }
 * @psalm-type HealthGoal = array{
 *   id: int, targetKey: string, period: 'day'|'week'|'month'|'long_term', active: bool,
 *   remindersEnabled: bool, reminderPolicy: 'gentle', retiredAt: string|null, createdAt: string, updatedAt: string,
 *   currentRevision: HealthGoalRevision
 * }
 * @psalm-type HealthGoalTarget = array{
 *   targetKey: string, metricKey: string, category: 'journal'|'measurement'|'daily_value',
 *   periods: list<'day'|'week'|'month'|'long_term'>, comparators: list<'gte'|'lte'>,
 *   kind: 'count'|'period_value'|'threshold_occurrence'|'latest_value', unit: string|null,
 *   minimum?: float, maximum?: float, options?: list<string>
 * }
 * @psalm-type HealthGoalsPage = array{goals: list<HealthGoal>, targets: list<HealthGoalTarget>}
 * @psalm-type HealthGoalProgress = array{
 *   goalId: int, targetKey: string, metricKey: string, period: 'day'|'week'|'month'|'long_term',
 *   periodStart: string, periodEnd: string|null, periodKey: string, active: bool,
 *   remindersEnabled: bool, comparator: 'gte'|'lte', targetValue: float, currentValue: float|null,
 *   observedValue: float|null, progressRatio: float|null, remaining: float|null,
 *   status: 'in_progress'|'reached'|'within_limit'|'exceeded'|'not_reached'|'paused', effectiveFrom: string
 * }
 * @psalm-type HealthGoalProgressPage = array{goals: list<HealthGoalProgress>}
 * @psalm-type HealthStatisticsPoint = array{
 *   date: string,
 *   value: float|null,
 *   subseries: array<string, float|null>|null
 * }
 * @psalm-type HealthStatisticsSubseriesSummary = array{
 *   average: float|null,
 *   minimum: float|null,
 *   maximum: float|null,
 *   count: int,
 *   activeDays: int,
 *   subseries: null
 * }
 * @psalm-type HealthStatisticsSummary = array{
 *   average: float|null,
 *   minimum: float|null,
 *   maximum: float|null,
 *   count: int,
 *   activeDays: int,
 *   subseries: array<string, HealthStatisticsSubseriesSummary>|null
 * }
 * @psalm-type HealthStatisticsGoalSegment = array{
 *   goalId: int,
 *   targetKey: string,
 *   metricKey: string,
 *   kind: 'count'|'period_value'|'threshold_occurrence'|'latest_value',
 *   seriesKey: string,
 *   comparator: 'gte'|'lte',
 *   targetValue: float,
 *   options: list<string>|null,
 *   effectiveFrom: string,
 *   effectiveTo: string|null
 * }
 * @psalm-type HealthStatisticsMetric = array{
 *   metricKey: string,
 *   category: 'journal'|'measurement'|'daily_value',
 *   valueType: 'scale'|'event'|'numeric'|'composite',
 *   canonicalUnit: string|null,
 *   minimum: int|null,
 *   maximum: int|null,
 *   series: list<HealthStatisticsPoint>,
 *   summary: HealthStatisticsSummary,
 *   goals: list<HealthStatisticsGoalSegment>
 * }
 * @psalm-type HealthStatisticsResponse = array{
 *   period: 'this_week'|'last_week'|'last_7_days'|'last_30_days'|'this_month'|'last_month'|'this_year'|'last_year',
 *   from: string,
 *   to: string,
 *   metrics: list<HealthStatisticsMetric>
 * }
 * @psalm-suppress UnusedClass Types are imported by API controller documentation.
 */
final class ResponseDefinitions {
}
