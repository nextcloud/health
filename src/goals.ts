import type { Goal, GoalComparator, GoalProgress, GoalTarget } from './api/goals.ts'
import type { AllMetricKey } from './metrics.ts'

import { t } from '@nextcloud/l10n'

export function getGoalTargetLabel(targetKey: string): string {
	switch (targetKey) {
		case 'hydration.water': return t('health', 'Water')
		case 'hydration.coffee': return t('health', 'Coffee')
		case 'hydration.tea': return t('health', 'Tea')
		case 'break.all': return t('health', 'Break')
		case 'break.mindfulness': return t('health', 'Mindfulness')
		case 'steps': return t('health', 'Steps')
		case 'kilocalories': return t('health', 'Kilocalories')
		case 'fruit': return t('health', 'Fruit')
		case 'job_satisfaction': return t('health', 'Job Satisfaction')
		case 'pulse': return t('health', 'Pulse')
		case 'blood_pressure': return t('health', 'Blood pressure')
		case 'weight': return t('health', 'Weight')
		default: return t('health', 'Goal')
	}
}

export function getGoalTargetMetricKey(targetKey: string): AllMetricKey {
	if (targetKey.startsWith('hydration.')) {
		return 'hydration'
	}
	if (targetKey.startsWith('break.')) {
		return 'break'
	}
	if (targetKey === 'steps' || targetKey === 'kilocalories' || targetKey === 'fruit' || targetKey === 'job_satisfaction' || targetKey === 'pulse' || targetKey === 'blood_pressure' || targetKey === 'weight') {
		return targetKey
	}
	return 'stress'
}

export function comparatorLabel(comparator: GoalComparator, target?: GoalTarget): string {
	if (target?.kind === 'latest_value') {
		return comparator === 'gte' ? t('health', 'At or above') : t('health', 'At or below')
	}
	return comparator === 'gte' ? t('health', 'At least') : t('health', 'At most')
}

export function periodLabel(period: Goal['period']): string {
	return ({ day: t('health', 'per day'), week: t('health', 'per week'), month: t('health', 'per month'), long_term: t('health', 'Long-term') })[period]
}

export function formatGoalValue(value: number, target: GoalTarget): string {
	if (target.targetKey === 'steps') {
		return value.toLocaleString()
	}
	if (target.targetKey === 'weight') {
		return `${value.toLocaleString(undefined, { maximumFractionDigits: 2 })} kg`
	}
	if (target.targetKey === 'kilocalories') {
		return t('health', '{value} kcal', { value: value.toLocaleString(undefined, { maximumFractionDigits: 2 }) })
	}
	if (target.targetKey === 'fruit') {
		return t('health', '{count} pieces', { count: value })
	}
	if (target.targetKey === 'pulse') {
		return `${value.toLocaleString()} bpm`
	}
	if (target.targetKey === 'hydration.water') {
		return t('health', '{count} glasses', { count: value })
	}
	if (target.kind === 'count') {
		return t('health', '{count}', { count: value })
	}
	return value.toLocaleString(undefined, { maximumFractionDigits: 2 })
}

export function goalDescription(goal: Goal, target: GoalTarget): string {
	const direction = comparatorLabel(goal.currentRevision.comparator, target)
	if (target.kind === 'threshold_occurrence') {
		return t('health', '{direction} {value} at least once {period}', { direction, value: formatGoalValue(goal.currentRevision.targetValue, target), period: periodLabel(goal.period) })
	}
	return goal.period === 'long_term'
		? t('health', '{direction} {value}', { direction, value: formatGoalValue(goal.currentRevision.targetValue, target) })
		: t('health', '{direction} {value} {period}', { direction, value: formatGoalValue(goal.currentRevision.targetValue, target), period: periodLabel(goal.period) })
}

export function getProgressLabel(progress: GoalProgress, target: GoalTarget): string {
	if (target.kind === 'latest_value') {
		return progress.baselineValue === null
			? t('health', 'Current {current}, target {target}', { current: progress.currentValue === null ? '—' : formatGoalValue(progress.currentValue, target), target: formatGoalValue(progress.targetValue, target) })
			: t('health', 'Started at {start}, current {current}, target {target}', { start: formatGoalValue(progress.baselineValue, target), current: progress.currentValue === null ? '—' : formatGoalValue(progress.currentValue, target), target: formatGoalValue(progress.targetValue, target) })
	}
	if (target.kind === 'threshold_occurrence') {
		return progress.observedValue === null
			? t('health', 'No measurement recorded')
			: t('health', 'Observed {value}', { value: formatGoalValue(progress.observedValue, target) })
	}
	if (progress.comparator === 'lte') {
		return t('health', '{current} of max. {target}', { current: progress.currentValue ?? 0, target: progress.targetValue })
	}
	return t('health', '{current} of {target}', { current: progress.currentValue ?? 0, target: progress.targetValue })
}

export function getGoalStatusLabel(progress: GoalProgress): string {
	return ({ in_progress: t('health', 'In progress'), reached: t('health', 'Reached'), within_limit: t('health', 'Within limit'), exceeded: t('health', 'Daily limit exceeded'), not_reached: t('health', 'Not reached'), paused: t('health', 'Paused') })[progress.status]
}

/**
 * The server defines goal semantics; the client only converts its safe ratio for display.
 *
 * @param progress Server-derived progress data
 */
export function goalProgressPercentage(progress: GoalProgress): number {
	return Math.round(Math.max(0, Math.min(1, progress.progressRatio ?? 0)) * 100)
}

export function goalProgressForMetric(progress: GoalProgress[], metricKey: string): GoalProgress[] {
	return progress.filter((item) => item.metricKey === metricKey && item.active)
}

export function goalTargetByKey(targets: GoalTarget[], key: string): GoalTarget | undefined {
	return targets.find((target) => target.targetKey === key)
}
