import type { EventOption, MetricKey } from '../metrics.ts'

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

export interface Entry {
	id: number
	metricKey: string
	numericValue: number | null
	optionValue: string | null
	context: string
	source: EntrySource
	recordedAt: string
	createdAt: string
	updatedAt: string
	note: string | null
}

export type EntrySource = 'web' | 'api' | 'mobile' | 'notification'

export interface CreateEntryRequest {
	metricKey: MetricKey
	numericValue: number | null
	optionValue: EventOption | null
	context: 'manual'
	source: 'web'
	recordedAt: string
	note: string | null
}

export interface UpdateEntryRequest {
	numericValue: number | null
	optionValue: EventOption | null
	context: string
	recordedAt: string
	note: string | null
}

export interface ListEntriesRequest {
	metricKey?: MetricKey
	from?: string
	to?: string
	limit?: number
	cursor?: string
}

export interface EntriesPage {
	entries: Entry[]
	nextCursor: string | null
}

interface OcsResponse<T> {
	ocs: {
		data: T
	}
}

const entriesUrl = generateOcsUrl('/apps/health/api/v2/entries')
const ocsHeaders = {
	Accept: 'application/json',
	'OCS-APIRequest': 'true',
}

export async function createEntry(request: CreateEntryRequest): Promise<Entry> {
	const response = await axios.post<OcsResponse<Entry>>(entriesUrl, request, {
		headers: ocsHeaders,
	})
	return response.data.ocs.data
}

export async function listEntries(request: ListEntriesRequest): Promise<EntriesPage> {
	const response = await axios.get<OcsResponse<EntriesPage>>(entriesUrl, {
		headers: ocsHeaders,
		params: request,
	})
	return response.data.ocs.data
}

export async function listAllEntries(request: Omit<ListEntriesRequest, 'cursor'>): Promise<Entry[]> {
	const entries: Entry[] = []
	let cursor: string | undefined

	do {
		const page = await listEntries({
			...request,
			cursor,
		})
		entries.push(...page.entries)
		cursor = page.nextCursor ?? undefined
	} while (cursor !== undefined)

	return entries
}

export async function updateEntry(id: number, request: UpdateEntryRequest): Promise<Entry> {
	const response = await axios.put<OcsResponse<Entry>>(
		generateOcsUrl('/apps/health/api/v2/entries/{id}', { id }),
		request,
		{ headers: ocsHeaders },
	)
	return response.data.ocs.data
}

export async function deleteEntry(id: number): Promise<void> {
	await axios.delete(
		generateOcsUrl('/apps/health/api/v2/entries/{id}', { id }),
		{ headers: ocsHeaders },
	)
}
