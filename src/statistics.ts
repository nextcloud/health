import type { HealthConfiguration } from './api/configuration.ts'
import type { StatisticsPeriod } from './api/statistics.ts'
import type { AllMetricKey, Unit } from './metrics.ts'

import { getCanonicalLocale, t } from '@nextcloud/l10n'
import { getMetricLabel, getMetricUnits } from './metrics.ts'

export interface StatisticsPeriodOption {
	id: StatisticsPeriod
	label: string
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
