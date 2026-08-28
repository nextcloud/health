import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

export interface DailyNote {
	date: string
	content: string | null
	createdAt: string | null
	updatedAt: string | null
}

interface OcsResponse<T> {
	ocs: {
		data: T
	}
}

const ocsHeaders = {
	Accept: 'application/json',
	'OCS-APIRequest': 'true',
}

function dailyNoteUrl(date: string): string {
	return generateOcsUrl('/apps/health/api/v2/daily-notes/{date}', { date })
}

export async function getDailyNote(date: string): Promise<DailyNote> {
	const response = await axios.get<OcsResponse<DailyNote>>(dailyNoteUrl(date), { headers: ocsHeaders })
	return response.data.ocs.data
}

export async function saveDailyNote(date: string, content: string): Promise<DailyNote> {
	const response = await axios.put<OcsResponse<DailyNote>>(
		dailyNoteUrl(date),
		{ content },
		{ headers: ocsHeaders },
	)
	return response.data.ocs.data
}
