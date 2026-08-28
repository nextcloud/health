import type { AllMetricKey, Unit } from '../metrics.ts'

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

export interface MetricConfiguration {
	enabled: boolean
	checkInEnabled: boolean
	checkOutEnabled: boolean
	displayUnit: Unit | null
}

export interface HealthConfiguration {
	profile: { heightCm: number | null, heightDisplayUnit: 'cm' | 'in', dateOfBirth: string | null, growthReferenceSex: 'female' | 'male' | null }
	metrics: Record<AllMetricKey, MetricConfiguration>
	searchDailyNotes: boolean
}

export function isMetricEnabled(configuration: HealthConfiguration | null | undefined, metricKey: AllMetricKey): boolean {
	return configuration?.metrics[metricKey]?.enabled === true
}

export function getEnabledMetricKeys<Key extends AllMetricKey>(configuration: HealthConfiguration | null | undefined, metricKeys: readonly Key[]): Key[] {
	return metricKeys.filter((metricKey) => isMetricEnabled(configuration, metricKey))
}

export function isMetricEnabledForRoutine(configuration: HealthConfiguration | null | undefined, metricKey: AllMetricKey, context: 'check-in' | 'check-out'): boolean {
	const metric = configuration?.metrics[metricKey]
	return metric?.enabled === true && (context === 'check-in' ? metric.checkInEnabled : metric.checkOutEnabled) === true
}

type OcsResponse<T> = { ocs: { data: T } }
const url = generateOcsUrl('/apps/health/api/v2/configuration')
const headers = { Accept: 'application/json', 'OCS-APIRequest': 'true' }

export async function getConfiguration(): Promise<HealthConfiguration> {
	return (await axios.get<OcsResponse<HealthConfiguration>>(url, { headers })).data.ocs.data
}

export async function updateConfiguration(request: Partial<{ profile: { height: number | null, heightUnit: 'cm' | 'in', dateOfBirth: string | null, growthReferenceSex: 'female' | 'male' | null }, metrics: Partial<Record<AllMetricKey, Partial<MetricConfiguration>>>, searchDailyNotes: boolean }>): Promise<HealthConfiguration> {
	return (await axios.put<OcsResponse<HealthConfiguration>>(url, request, { headers })).data.ocs.data
}
