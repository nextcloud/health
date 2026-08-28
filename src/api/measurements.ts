import type { MeasurementMetricKey, Unit } from '../metrics.ts'

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

export interface Measurement { id: number, metricKey: MeasurementMetricKey, numericValue: number | null, values: { systolic: number, diastolic: number } | null, context: string, source: string, recordedAt: string, createdAt: string, updatedAt: string, note: string | null }
export interface MeasurementRequest { metricKey: MeasurementMetricKey, numericValue: number | null, values: { systolic: number, diastolic: number } | null, unit: Unit, recordedAt: string, note: string | null, context: 'manual' | 'checkin' | 'checkout', source: 'web' }
type OcsResponse<T> = { ocs: { data: T } }
const headers = { Accept: 'application/json', 'OCS-APIRequest': 'true' }
const url = generateOcsUrl('/apps/health/api/v2/measurements')

export async function listMeasurements(from: string, to: string): Promise<Measurement[]> {
	const response = await axios.get<OcsResponse<{ measurements: Measurement[] }>>(url, { headers, params: { from, to } })
	return response.data.ocs.data.measurements
}
export async function createMeasurement(request: MeasurementRequest): Promise<Measurement> {
	const response = await axios.post<OcsResponse<Measurement>>(url, request, { headers })
	return response.data.ocs.data
}
export async function updateMeasurement(id: number, request: Omit<MeasurementRequest, 'metricKey' | 'source'>): Promise<Measurement> {
	const response = await axios.put<OcsResponse<Measurement>>(generateOcsUrl('/apps/health/api/v2/measurements/{id}', { id }), request, { headers })
	return response.data.ocs.data
}
export async function deleteMeasurement(id: number): Promise<void> {
	await axios.delete(generateOcsUrl('/apps/health/api/v2/measurements/{id}', { id }), { headers })
}
