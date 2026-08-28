import type { DailyValue } from './dailyValues.ts'
import type { Entry } from './entries.ts'
import type { Measurement } from './measurements.ts'

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

export interface RoutineRequest { date: string, recordedAt: string, journalMetrics: Array<Record<string, unknown>>, measurements: Array<Record<string, unknown>>, dailyValues: Array<Record<string, unknown>> }
export interface RoutineResult { createdEntries: Entry[], createdMeasurements: Measurement[], updatedDailyValues: DailyValue[] }
type OcsResponse<T> = { ocs: { data: T } }
const headers = { Accept: 'application/json', 'OCS-APIRequest': 'true' }
export async function submitRoutine(context: 'check-in' | 'check-out', request: RoutineRequest): Promise<RoutineResult> {
	const response = await axios.post<OcsResponse<RoutineResult>>(generateOcsUrl('/apps/health/api/v2/routines/{context}', { context }), request, { headers })
	return response.data.ocs.data
}
