import type { DailyValueMetricKey, Unit } from '../metrics.ts'

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

export interface DailyValue { id: number, metricKey: DailyValueMetricKey, numericValue: number, localDate: string, createdAt: string, updatedAt: string, bmi: number | null }
type OcsResponse<T> = { ocs: { data: T } }
const headers = { Accept: 'application/json', 'OCS-APIRequest': 'true' }
const url = generateOcsUrl('/apps/health/api/v2/daily-values')

export async function listDailyValues(date: string): Promise<DailyValue[]> {
	const response = await axios.get<OcsResponse<{ values: DailyValue[] }>>(url, { headers, params: { date } })
	return response.data.ocs.data.values
}
export async function upsertDailyValue(metricKey: DailyValueMetricKey, date: string, numericValue: number, unit: Unit | null): Promise<DailyValue> {
	const response = await axios.put<OcsResponse<DailyValue>>(generateOcsUrl('/apps/health/api/v2/daily-values/{metricKey}/{date}', { metricKey, date }), { numericValue, unit }, { headers })
	return response.data.ocs.data
}
export async function deleteDailyValue(metricKey: DailyValueMetricKey, date: string): Promise<void> {
	await axios.delete(generateOcsUrl('/apps/health/api/v2/daily-values/{metricKey}/{date}', { metricKey, date }), { headers })
}
