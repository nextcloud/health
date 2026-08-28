import type { AllMetricKey } from '../metrics.ts'
import type { GoalComparator } from './goals.ts'

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

export const STATISTICS_PERIODS = [
	'this_week',
	'last_week',
	'last_7_days',
	'last_30_days',
	'this_month',
	'last_month',
	'this_year',
	'last_year',
] as const

export type StatisticsPeriod = typeof STATISTICS_PERIODS[number]
export type StatisticsMetricCategory = 'journal' | 'measurement' | 'daily_value'

export interface StatisticsPoint {
	date: string
	value: number | null
	subseries: Record<string, number | null> | null
}

export interface StatisticsGoalOverlay {
	goalId: number
	targetKey: string
	metricKey: AllMetricKey
	kind: 'count' | 'period_value' | 'threshold_occurrence' | 'latest_value'
	seriesKey: string
	comparator: GoalComparator
	targetValue: number
	options: string[] | null
	effectiveFrom: string
	effectiveTo: string | null
}

export interface StatisticsSubseriesSummary {
	average: number | null
	minimum: number | null
	maximum: number | null
	count: number
	activeDays: number
	subseries: null
}

export interface StatisticsSummary {
	average: number | null
	minimum: number | null
	maximum: number | null
	count: number
	activeDays: number
	subseries: Record<string, StatisticsSubseriesSummary> | null
}

export interface StatisticsMetric {
	metricKey: AllMetricKey
	category: StatisticsMetricCategory
	valueType: 'scale' | 'event' | 'numeric' | 'composite'
	canonicalUnit: string | null
	minimum: number | null
	maximum: number | null
	series: StatisticsPoint[]
	summary: StatisticsSummary
	goals: StatisticsGoalOverlay[]
}

export interface StatisticsResponse {
	period: StatisticsPeriod
	from: string
	to: string
	metrics: StatisticsMetric[]
}

interface OcsResponse<T> {
	ocs: {
		data: T
	}
}

const url = generateOcsUrl('/apps/health/api/v2/statistics')
const headers = { Accept: 'application/json', 'OCS-APIRequest': 'true' }

export async function getStatistics(period: StatisticsPeriod, metricKeys: AllMetricKey[]): Promise<StatisticsResponse> {
	const response = await axios.get<OcsResponse<StatisticsResponse>>(url, {
		headers,
		params: {
			period,
			metrics: metricKeys.join(','),
		},
	})

	return response.data.ocs.data
}
