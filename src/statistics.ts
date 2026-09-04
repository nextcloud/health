import type { HealthConfiguration } from './api/configuration.ts'
import type { StatisticsGoalOverlay, StatisticsPeriod } from './api/statistics.ts'
import type { AllMetricKey, Unit } from './metrics.ts'

import { getCanonicalLocale, t } from '@nextcloud/l10n'
import { getMetricLabel, getMetricUnits } from './metrics.ts'

export interface StatisticsPeriodOption {
	id: StatisticsPeriod
	label: string
}

export interface StatisticsScaleRange {
	minimum: number
	maximum: number
}

export interface StatisticsGoalChartDatasetInput {
	label: string
	data: Array<number | null>
	color: string
	yAxisID: string
	stack: string
}

export interface StatisticsGoalChartDataset extends StatisticsGoalChartDatasetInput {
	type: 'line'
	parsing: true
	xAxisID: 'x'
	backgroundColor: 'transparent'
	borderColor: string
	borderWidth: number
	borderDash: number[]
	fill: false
	pointRadius: 0
	pointHoverRadius: 0
	pointStyle: 'circle'
	stepped: false
	spanGaps: false
	showLine: true
	hidden: false
	order: -1
}

/**
 * Builds the complete Chart.js configuration for one goal revision.
 *
 * Goal data follows the category-label representation used by measurement
 * lines. The values are visual overlays only; they are not measurements.
 *
 * @param dataset Goal revision data and its assigned metric axis.
 */
export function createStatisticsGoalChartDataset(dataset: StatisticsGoalChartDatasetInput): StatisticsGoalChartDataset {
	return {
		...dataset,
		type: 'line',
		parsing: true,
		xAxisID: 'x',
		backgroundColor: 'transparent',
		borderColor: dataset.color,
		borderWidth: 2,
		borderDash: [6, 4],
		fill: false,
		pointRadius: 0,
		pointHoverRadius: 0,
		pointStyle: 'circle',
		stepped: false,
		spanGaps: false,
		showLine: true,
		hidden: false,
		order: -1,
	}
}

/**
 * Returns rounded, non-negative chart bounds with space around the visible data.
 *
 * The step selection favours the value's natural order of magnitude when the
 * data already spans it (for example, 70–80 becomes 60–90), while retaining
 * useful precision for a narrow range such as 75–76.
 *
 * @param values Numeric values visible on one chart axis.
 */
export function getPaddedStatisticsScaleRange(values: readonly number[]): StatisticsScaleRange {
	const numericValues = values.filter((value) => Number.isFinite(value))
	if (numericValues.length === 0) {
		return { minimum: 0, maximum: 1 }
	}

	const actualMinimum = Math.min(...numericValues)
	const actualMaximum = Math.max(...numericValues)
	const actualRange = actualMaximum - actualMinimum
	const virtualRange = actualRange === 0
		? Math.max(Math.abs(actualMaximum) * 0.4, 1)
		: actualRange
	const padding = virtualRange * 0.25
	const paddedMinimum = Math.max(0, actualMinimum - padding)
	const paddedMaximum = actualMaximum + padding
	const magnitude = 10 ** Math.floor(Math.log10(Math.max(Math.abs(actualMinimum), Math.abs(actualMaximum), virtualRange)))
	const step = virtualRange >= magnitude * 0.5
		? magnitude
		: niceStatisticsScaleStep((paddedMaximum - paddedMinimum) / 4)
	const minimum = Math.max(0, Math.floor(paddedMinimum / step) * step)
	const maximum = Math.ceil(paddedMaximum / step) * step

	return maximum > minimum
		? { minimum, maximum }
		: { minimum, maximum: minimum + step }
}

/**
 * Returns the clipped visual series for one chart goal revision segment.
 *
 * Chart.js category scales require values aligned with their labels. Reusing
 * the chart's existing dates guarantees that active goal intervals form a
 * drawable horizontal line without creating persisted measurements.
 *
 * @param metricKey Metric rendered by the chart dataset.
 * @param goal One goal revision provided for the selected Statistics response.
 * @param dates Visible local calendar dates.
 */
export function getStatisticsGoalChartSeriesValues(metricKey: AllMetricKey, goal: StatisticsGoalOverlay, dates: readonly string[]): Array<number | null> {
	if (goal.metricKey !== metricKey) {
		return Array.from({ length: dates.length }, () => null as number | null)
	}

	return dates.map((date) => goal.effectiveFrom <= date && (goal.effectiveTo === null || date <= goal.effectiveTo)
		? goal.targetValue
		: null)
}

function niceStatisticsScaleStep(value: number): number {
	const magnitude = 10 ** Math.floor(Math.log10(value))
	const normalized = value / magnitude
	const multiplier = normalized <= 1
		? 1
		: normalized <= 2
			? 2
			: normalized <= 5
				? 5
				: 10

	return multiplier * magnitude
}

export function formatStatisticsDate(dateKey: string, full = false): string {
	return new Intl.DateTimeFormat(getCanonicalLocale(), full
		? { weekday: 'long', month: 'long', day: 'numeric' }
		: { month: 'short', day: 'numeric' }).format(new Date(`${dateKey}T12:00:00`))
}

export function getStatisticsPeriodOptions(): StatisticsPeriodOption[] {
	return [
		{ id: 'this_week', label: t('health', 'This week') },
		{ id: 'last_week', label: t('health', 'Last week') },
		{ id: 'last_7_days', label: t('health', 'Last 7 days') },
		{ id: 'last_30_days', label: t('health', 'Last 30 days') },
		{ id: 'this_month', label: t('health', 'This month') },
		{ id: 'last_month', label: t('health', 'Last month') },
		{ id: 'this_year', label: t('health', 'This year') },
		{ id: 'last_year', label: t('health', 'Last year') },
	]
}

export function displayUnitForMetric(configuration: HealthConfiguration | null | undefined, metricKey: AllMetricKey): Unit | null {
	return configuration?.metrics[metricKey]?.displayUnit ?? getMetricUnits(metricKey)[0] ?? null
}

export function getStatisticsGoalLabel(targetKey: string, comparator: 'gte' | 'lte', metricKey: AllMetricKey): string {
	switch (targetKey) {
		case 'hydration.water':
			return t('health', 'Water goal')
		case 'hydration.coffee':
			return comparator === 'lte' ? t('health', 'Coffee limit') : t('health', 'Coffee goal')
		case 'hydration.tea':
			return t('health', 'Tea goal')
		case 'break.all':
			return t('health', 'Break goal')
		case 'break.mindfulness':
			return t('health', 'Mindfulness goal')
		case 'steps':
			return t('health', 'Steps goal')
		case 'job_satisfaction':
			return t('health', 'Job satisfaction goal')
		default:
			return t('health', '{metric} goal', { metric: getMetricLabel(metricKey) })
	}
}
