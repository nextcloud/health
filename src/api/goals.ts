import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

export type GoalPeriod = 'day' | 'week' | 'month' | 'long_term'
export type GoalComparator = 'gte' | 'lte'
export type GoalStatus = 'in_progress' | 'reached' | 'within_limit' | 'exceeded' | 'not_reached' | 'paused'
export type GoalTargetKind = 'count' | 'period_value' | 'threshold_occurrence' | 'latest_value'

export interface GoalRevision {
	id: number
	comparator: GoalComparator
	targetValue: number
	secondaryTargetValue: number | null
	effectiveFrom: string
	effectiveTo: string | null
}

export interface Goal {
	id: number
	targetKey: string
	period: GoalPeriod
	active: boolean
	remindersEnabled: boolean
	reminderPolicy: 'gentle'
	retiredAt: string | null
	createdAt: string
	updatedAt: string
	currentRevision: GoalRevision
}

export interface GoalTarget {
	targetKey: string
	metricKey: string
	category: 'journal' | 'measurement' | 'daily_value'
	periods: GoalPeriod[]
	comparators: GoalComparator[]
	kind: GoalTargetKind
	unit: string | null
	minimum?: number
	maximum?: number
}

export interface GoalProgress {
	goalId: number
	targetKey: string
	metricKey: string
	period: GoalPeriod
	periodStart: string
	periodEnd: string | null
	periodKey: string
	active: boolean
	remindersEnabled: boolean
	comparator: GoalComparator
	targetValue: number
	currentValue: number | null
	observedValue: number | null
	progressRatio: number | null
	remaining: number | null
	status: GoalStatus
	effectiveFrom: string
}

export interface GoalMutation {
	targetKey?: string
	period?: GoalPeriod
	comparator?: GoalComparator
	targetValue?: number
	active?: boolean
	remindersEnabled?: boolean
}

type OcsResponse<T> = { ocs: { data: T } }
const headers = { Accept: 'application/json', 'OCS-APIRequest': 'true' }
const goalsUrl = generateOcsUrl('/apps/health/api/v2/goals')

export async function listGoals(): Promise<{ goals: Goal[], targets: GoalTarget[] }> {
	return (await axios.get<OcsResponse<{ goals: Goal[], targets: GoalTarget[] }>>(goalsUrl, { headers })).data.ocs.data
}

export async function createGoal(request: Required<Pick<GoalMutation, 'targetKey' | 'period' | 'comparator' | 'targetValue'>> & { remindersEnabled: boolean }): Promise<Goal> {
	return (await axios.post<OcsResponse<Goal>>(goalsUrl, request, { headers })).data.ocs.data
}

export async function updateGoal(id: number, request: GoalMutation): Promise<Goal> {
	return (await axios.put<OcsResponse<Goal>>(generateOcsUrl('/apps/health/api/v2/goals/{id}', { id }), request, { headers })).data.ocs.data
}

export async function deleteGoal(id: number): Promise<Goal> {
	return (await axios.delete<OcsResponse<Goal>>(generateOcsUrl('/apps/health/api/v2/goals/{id}', { id }), { headers })).data.ocs.data
}

export async function listGoalProgress(period: GoalPeriod, date?: string): Promise<GoalProgress[]> {
	const response = await axios.get<OcsResponse<{ goals: GoalProgress[] }>>(generateOcsUrl('/apps/health/api/v2/goals/progress'), {
		headers,
		params: date === undefined ? { period } : { period, date },
	})
	return response.data.ocs.data.goals
}
