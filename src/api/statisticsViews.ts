import type { AllMetricKey } from '../metrics.ts'
import type { StatisticsPeriod } from './statistics.ts'

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

export interface StatisticsViewConfiguration {
	metricKeys: AllMetricKey[]
	period: StatisticsPeriod
}

export interface SavedStatisticsView extends StatisticsViewConfiguration {
	id: number
	title: string
	icon: string
	createdAt: string
	updatedAt: string
}

export interface SavedStatisticsViewInput extends StatisticsViewConfiguration {
	title: string
	icon: string
}

interface OcsResponse<T> {
	ocs: {
		data: T
	}
}

const url = generateOcsUrl('/apps/health/api/v2/statistics/views')
const headers = { Accept: 'application/json', 'OCS-APIRequest': 'true' }

export async function listSavedStatisticsViews(): Promise<SavedStatisticsView[]> {
	return (await axios.get<OcsResponse<{ views: SavedStatisticsView[] }>>(url, { headers })).data.ocs.data.views
}

export async function getSavedStatisticsView(id: number): Promise<SavedStatisticsView> {
	return (await axios.get<OcsResponse<SavedStatisticsView>>(`${url}/${id}`, { headers })).data.ocs.data
}

export async function createSavedStatisticsView(input: SavedStatisticsViewInput): Promise<SavedStatisticsView> {
	return (await axios.post<OcsResponse<SavedStatisticsView>>(url, input, { headers })).data.ocs.data
}

export async function updateSavedStatisticsView(id: number, input: SavedStatisticsViewInput): Promise<SavedStatisticsView> {
	return (await axios.put<OcsResponse<SavedStatisticsView>>(`${url}/${id}`, input, { headers })).data.ocs.data
}

export async function deleteSavedStatisticsView(id: number): Promise<void> {
	await axios.delete(`${url}/${id}`, { headers })
}
