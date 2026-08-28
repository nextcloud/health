<script setup lang="ts">
import type { Goal, GoalComparator, GoalPeriod, GoalProgress, GoalTarget } from '../api/goals.ts'
import type { AllMetricKey } from '../metrics.ts'

import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed, inject, nextTick, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcProgressBar from '@nextcloud/vue/components/NcProgressBar'
import NcRadioGroup from '@nextcloud/vue/components/NcRadioGroup'
import NcRadioGroupButton from '@nextcloud/vue/components/NcRadioGroupButton'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import MetricIcon from '../components/MetricIcon.vue'
import { getConfiguration, isMetricEnabled } from '../api/configuration.ts'
import { createGoal, deleteGoal, listGoalProgress, listGoals, updateGoal } from '../api/goals.ts'
import { healthConfigurationKey } from '../configurationContext.ts'
import { comparatorLabel, getGoalStatusLabel, getGoalTargetLabel, getGoalTargetMetricKey, getProgressLabel, goalDescription, goalTargetByKey, periodLabel } from '../goals.ts'
import { iconPaths } from '../icons.ts'
import { localDateKey, parseLocalDateKey, startOfLocalDay } from '../utils/dates.ts'

type ProgressByPeriod = Record<GoalPeriod, GoalProgress[]>
type EditorState = { goal: Goal | null, targetKey: string, period: GoalPeriod, comparator: GoalComparator, targetValue: string, remindersEnabled: boolean }

const route = useRoute()
const router = useRouter()
const configuration = inject(healthConfigurationKey)
const loading = ref(true)
const goals = ref<Goal[]>([])
const targets = ref<GoalTarget[]>([])
const progress = ref<ProgressByPeriod>({ day: [], week: [], month: [], long_term: [] })
const editor = ref<EditorState | null>(null)
const saving = ref(false)
const editorError = ref<string | null>(null)
const editorElement = ref<HTMLElement | null>(null)
const deleteCandidate = ref<Goal | null>(null)
const deleting = ref(false)
const periods: GoalPeriod[] = ['day', 'week', 'month', 'long_term']

function monday(date: Date): Date {
	const result = startOfLocalDay(date)
	const offset = (result.getDay() + 6) % 7
	result.setDate(result.getDate() - offset)
	return result
}

function monthStart(date: Date): Date {
	return new Date(date.getFullYear(), date.getMonth(), 1)
}

function queryDate(key: 'day' | 'week' | 'month', normalise: (value: Date) => Date): Date {
	const value = typeof route.query[key] === 'string' ? parseLocalDateKey(route.query[key]) : null
	const candidate = normalise(value ?? new Date())
	return candidate > normalise(new Date()) ? normalise(new Date()) : candidate
}

const selectedDay = computed(() => queryDate('day', startOfLocalDay))
const selectedWeek = computed(() => queryDate('week', monday))
const selectedMonth = computed(() => queryDate('month', monthStart))
const availableTargets = computed(() => {
	const currentConfiguration = configuration?.value
	if (currentConfiguration === null || currentConfiguration === undefined) {
		return []
	}

	return targets.value.filter((target) => isMetricEnabled(currentConfiguration, target.metricKey as AllMetricKey))
})
const editorTarget = computed(() => editor.value === null ? undefined : goalTargetByKey(targets.value, editor.value.targetKey))
const allowedPeriods = computed(() => editorTarget.value?.periods ?? [])
const allowedComparators = computed(() => editorTarget.value?.comparators ?? [])

function targetCategories(): Array<{ category: GoalTarget['category'], label: string, targets: GoalTarget[] }> {
	return [
		{ category: 'journal', label: t('health', 'Journal'), targets: availableTargets.value.filter((target) => target.category === 'journal') },
		{ category: 'daily_value', label: t('health', 'Daily values'), targets: availableTargets.value.filter((target) => target.category === 'daily_value') },
		{ category: 'measurement', label: t('health', 'Measurements'), targets: availableTargets.value.filter((target) => target.category === 'measurement') },
	]
}

function targetFor(goal: Goal | GoalProgress): GoalTarget | undefined {
	return goalTargetByKey(targets.value, goal.targetKey)
}

function goalsFor(period: GoalPeriod): Goal[] {
	return goals.value.filter((goal) => goal.period === period)
}

function progressFor(goal: Goal): GoalProgress | undefined {
	return progress.value[goal.period].find((item) => item.goalId === goal.id)
}

function progressPercent(item: GoalProgress): number {
	return Math.round(Math.max(0, Math.min(1, item.progressRatio ?? 0)) * 100)
}

function periodDate(period: 'day' | 'week' | 'month'): string {
	return localDateKey(period === 'day' ? selectedDay.value : period === 'week' ? selectedWeek.value : selectedMonth.value)
}

function periodHeading(period: GoalPeriod): string {
	return ({ day: t('health', 'Daily goals'), week: t('health', 'Weekly goals'), month: t('health', 'Monthly goals'), long_term: t('health', 'Long-term goals') })[period]
}

function selectedHeading(period: 'day' | 'week' | 'month'): string {
	const date = period === 'day' ? selectedDay.value : period === 'week' ? selectedWeek.value : selectedMonth.value
	if (period === 'month') {
		return new Intl.DateTimeFormat(undefined, { month: 'long', year: 'numeric' }).format(date)
	}
	if (period === 'week') {
		return t('health', 'Week of {date}', { date: new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric' }).format(date) })
	}
	return new Intl.DateTimeFormat(undefined, { weekday: 'long', month: 'long', day: 'numeric' }).format(date)
}

function isCurrent(period: 'day' | 'week' | 'month'): boolean {
	return periodDate(period) === localDateKey(period === 'day' ? startOfLocalDay(new Date()) : period === 'week' ? monday(new Date()) : monthStart(new Date()))
}

async function changeDate(period: 'day' | 'week' | 'month', amount: number) {
	const date = period === 'day' ? selectedDay.value : period === 'week' ? selectedWeek.value : selectedMonth.value
	const next = new Date(date)
	if (period === 'day') {
		next.setDate(next.getDate() + amount)
	}
	if (period === 'week') {
		next.setDate(next.getDate() + 7 * amount)
	}
	if (period === 'month') {
		next.setMonth(next.getMonth() + amount)
	}
	const normalise = period === 'day' ? startOfLocalDay : period === 'week' ? monday : monthStart
	const normalised = normalise(next)
	if (normalised > normalise(new Date())) {
		return
	}
	await router.replace({ query: { ...route.query, [period]: localDateKey(normalised) } })
}

async function loadConfiguration() {
	if (configuration?.value !== null && configuration?.value !== undefined) {
		return
	}
	try {
		if (configuration !== undefined) {
			configuration.value = await getConfiguration()
		}
	} catch {
		showError(t('health', 'Configuration could not be loaded.'))
	}
}

async function loadProgress() {
	const [day, week, month, longTerm] = await Promise.all([
		listGoalProgress('day', periodDate('day')),
		listGoalProgress('week', periodDate('week')),
		listGoalProgress('month', periodDate('month')),
		listGoalProgress('long_term'),
	])
	progress.value = { day, week, month, long_term: longTerm }
}

async function load() {
	loading.value = true
	try {
		const definitions = await listGoals()
		goals.value = definitions.goals
		targets.value = definitions.targets
		await loadProgress()
	} catch {
		showError(t('health', 'Goals could not be loaded.'))
	} finally {
		loading.value = false
	}
}

async function refreshProgress() {
	try {
		await loadProgress()
	} catch {
		showError(t('health', 'Goal progress could not be refreshed.'))
	}
}

function defaultTarget(): GoalTarget | undefined {
	return availableTargets.value[0]
}

async function openNew() {
	const target = defaultTarget()
	if (target === undefined) {
		return
	}
	editorError.value = null
	editor.value = {
		goal: null,
		targetKey: target.targetKey,
		period: target.periods[0],
		comparator: target.comparators[0],
		targetValue: target.minimum === undefined ? '1' : String(target.minimum),
		remindersEnabled: false,
	}
	await focusEditor()
}

async function openEdit(goal: Goal) {
	editorError.value = null
	editor.value = {
		goal,
		targetKey: goal.targetKey,
		period: goal.period,
		comparator: goal.currentRevision.comparator,
		targetValue: String(goal.currentRevision.targetValue),
		remindersEnabled: goal.remindersEnabled,
	}
	await focusEditor()
}

async function focusEditor() {
	await nextTick()
	const element = editorElement.value
	if (element === null) {
		return
	}
	element.querySelector<HTMLElement>('input, button')?.focus()
}

function closeEditor() {
	if (!saving.value) {
		resetEditor()
	}
}

function resetEditor() {
	editor.value = null
	editorError.value = null
}

function editorTitle(): string {
	return editor.value?.goal === null
		? t('health', 'New goal')
		: t('health', '{goal} goal', { goal: getGoalTargetLabel(editor.value?.targetKey ?? '') })
}

function editorContext(): string {
	if (editor.value === null) {
		return ''
	}
	if (editor.value.period === 'long_term') {
		return t('health', 'Long-term')
	}
	const date = editor.value.period === 'day' ? selectedHeading('day') : editor.value.period === 'week' ? selectedHeading('week') : selectedHeading('month')
	const period = editor.value.period === 'day' ? t('health', 'Daily') : editor.value.period === 'week' ? t('health', 'Weekly') : t('health', 'Monthly')
	return t('health', '{period} · {date}', { period, date })
}

function onTargetChange() {
	if (editor.value === null || editorTarget.value === undefined) {
		return
	}
	if (!editorTarget.value.periods.includes(editor.value.period)) {
		editor.value.period = editorTarget.value.periods[0]
	}
	if (!editorTarget.value.comparators.includes(editor.value.comparator)) {
		editor.value.comparator = editorTarget.value.comparators[0]
	}
	if (editorTarget.value.minimum !== undefined && Number(editor.value.targetValue) < editorTarget.value.minimum) {
		editor.value.targetValue = String(editorTarget.value.minimum)
	}
}

async function saveEditor() {
	if (saving.value || editor.value === null || editorTarget.value === undefined) {
		return
	}
	const targetValue = Number(editor.value.targetValue)
	if (!Number.isFinite(targetValue)) {
		editorError.value = t('health', 'Enter a valid target value.')
		return
	}
	saving.value = true
	editorError.value = null
	try {
		if (editor.value.goal === null) {
			const goal = await createGoal({ targetKey: editor.value.targetKey, period: editor.value.period, comparator: editor.value.comparator, targetValue, remindersEnabled: editor.value.remindersEnabled })
			goals.value = [...goals.value, goal]
			showSuccess(t('health', 'Goal created.'))
		} else {
			const original = editor.value.goal
			const goal = await updateGoal(original.id, { targetKey: editor.value.targetKey, period: editor.value.period, comparator: editor.value.comparator, targetValue, remindersEnabled: editor.value.remindersEnabled })
			goals.value = goal.id === original.id
				? goals.value.map((item) => item.id === goal.id ? goal : item)
				: [...goals.value.filter((item) => item.id !== original.id), goal]
			showSuccess(t('health', 'Goal updated.'))
		}
		resetEditor()
		await refreshProgress()
	} catch {
		editorError.value = t('health', 'Goal could not be saved. Check that this target and period are not already in use.')
		showError(editorError.value)
	} finally {
		saving.value = false
	}
}

async function setPaused(goal: Goal, active: boolean) {
	try {
		const updated = await updateGoal(goal.id, { active })
		goals.value = goals.value.map((item) => item.id === updated.id ? updated : item)
		await refreshProgress()
		showSuccess(active ? t('health', 'Goal resumed.') : t('health', 'Goal paused.'))
	} catch {
		showError(t('health', 'Goal could not be updated.'))
	}
}

function requestDelete(goal: Goal) {
	deleteCandidate.value = goal
}

async function confirmDelete() {
	if (deleteCandidate.value === null) {
		return
	}
	const goal = deleteCandidate.value
	deleting.value = true
	try {
		await deleteGoal(goal.id)
		goals.value = goals.value.filter((item) => item.id !== goal.id)
		await refreshProgress()
		deleteCandidate.value = null
		showSuccess(t('health', 'Goal deleted.'))
	} catch {
		showError(t('health', 'Goal could not be deleted.'))
	} finally {
		deleting.value = false
	}
}

watch(() => [route.query.day, route.query.week, route.query.month], () => {
	void refreshProgress()
})
void loadConfiguration()
void load()
</script>

<template>
	<main class="goals-view">
		<header class="goals-view__header">
			<div>
				<h1 class="health-page-title">
					{{ t('health', 'Goals') }}
				</h1>
				<p>{{ t('health', 'Set personal targets and review your progress. Goals are private to your account.') }}</p>
			</div>
			<NcButton :aria-label="t('health', 'New goal')"
				:disabled="availableTargets.length === 0"
				:title="t('health', 'New goal')"
				variant="primary"
				@click="openNew">
				<template #icon>
					<NcIconSvgWrapper :path="iconPaths.plus" />
				</template>
			</NcButton>
		</header>

		<NcDialog v-if="editor"
			:close-on-click-outside="!saving"
			class="goals-view__dialog"
			:name="editorTitle()"
			size="normal"
			@closing="closeEditor">
			<div ref="editorElement" class="goals-view__editor">
				<p class="goals-view__editor-context">
					{{ editorContext() }}
				</p>
				<section v-for="category in targetCategories()"
					v-show="category.targets.length"
					:key="category.category"
					class="goals-view__target-category">
					<h3>{{ category.label }}</h3>
					<NcRadioGroup v-model="editor.targetKey"
						:label="t('health', 'Goal target')"
						hide-label
						@update:modelValue="onTargetChange">
						<NcRadioGroupButton v-for="target in category.targets"
							:key="target.targetKey"
							:label="getGoalTargetLabel(target.targetKey)"
							:value="target.targetKey">
							<template #icon>
								<MetricIcon :metric-key="target.metricKey as AllMetricKey" />
							</template>
						</NcRadioGroupButton>
					</NcRadioGroup>
				</section>
				<div v-if="editorTarget" class="goals-view__editor-grid">
					<NcRadioGroup v-model="editor.period" :label="t('health', 'Period')">
						<NcRadioGroupButton v-for="period in allowedPeriods"
							:key="period"
							:label="periodLabel(period)"
							:value="period" />
					</NcRadioGroup>
					<NcRadioGroup v-model="editor.comparator" :label="t('health', 'Target direction')">
						<NcRadioGroupButton v-for="comparator in allowedComparators"
							:key="comparator"
							:label="comparatorLabel(comparator, editorTarget)"
							:value="comparator" />
					</NcRadioGroup>
					<NcTextField v-model="editor.targetValue" :label="t('health', 'Target value')" inputmode="decimal" />
					<NcCheckboxRadioSwitch v-model="editor.remindersEnabled" type="switch">
						{{ t('health', 'Gentle reminders') }}
						<template #description>
							{{ t('health', 'Health checks your progress and only reminds you when useful.') }}
						</template>
					</NcCheckboxRadioSwitch>
				</div>
				<p v-if="editorError" class="goals-view__error" role="alert">
					{{ editorError }}
				</p>
			</div>
			<template #actions>
				<NcButton :disabled="saving"
					:text="t('health', 'Cancel')"
					variant="tertiary"
					@click="closeEditor" />
				<NcButton :disabled="saving"
					:text="editor.goal ? t('health', 'Save goal') : t('health', 'Create goal')"
					variant="primary"
					@click="saveEditor" />
			</template>
		</NcDialog>

		<div v-if="loading" class="goals-view__loading">
			<NcLoadingIcon :name="t('health', 'Loading goals')" :size="32" />
		</div>
		<section v-for="period in periods" :key="period" class="goals-view__period">
			<div class="goals-view__period-heading">
				<div>
					<h2>{{ periodHeading(period) }}</h2><p v-if="period !== 'long_term'">
						{{ selectedHeading(period) }}
					</p>
				</div>
				<div v-if="period !== 'long_term'" class="goals-view__period-controls" :aria-label="t('health', 'Change period')">
					<NcButton :aria-label="t('health', 'Previous period')" variant="tertiary" @click="changeDate(period, -1)">
						‹
					</NcButton>
					<NcButton :aria-label="t('health', 'Current period')"
						:disabled="isCurrent(period)"
						variant="tertiary"
						@click="changeDate(period, 0)">
						{{ period === 'day' ? t('health', 'Today') : period === 'week' ? t('health', 'This week') : t('health', 'This month') }}
					</NcButton>
					<NcButton :aria-label="t('health', 'Next period')"
						:disabled="isCurrent(period)"
						variant="tertiary"
						@click="changeDate(period, 1)">
						›
					</NcButton>
				</div>
			</div>
			<p v-if="goalsFor(period).length === 0" class="goals-view__empty">
				{{ t('health', 'No goals in this period yet.') }}
			</p>
			<ul v-else class="goals-view__list">
				<li v-for="goal in goalsFor(period)" :key="goal.id" class="goals-view__card">
					<div class="goals-view__card-main">
						<strong class="goals-view__card-title"><MetricIcon :metric-key="getGoalTargetMetricKey(goal.targetKey)" /> {{ getGoalTargetLabel(goal.targetKey) }}</strong>
						<p v-if="targetFor(goal)">
							{{ goalDescription(goal, targetFor(goal)!) }}
						</p>
						<template v-if="progressFor(goal) && targetFor(goal)">
							<p>{{ getProgressLabel(progressFor(goal)!, targetFor(goal)!) }}</p>
							<div v-if="progressFor(goal)!.progressRatio !== null" :aria-label="t('health', '{percent} percent of goal reached', { percent: progressPercent(progressFor(goal)!) })" role="img">
								<NcProgressBar :aria-hidden="true" :value="progressPercent(progressFor(goal)!)" />
							</div>
							<span :class="`goals-view__status--${progressFor(goal)!.status}`" class="goals-view__status">{{ getGoalStatusLabel(progressFor(goal)!) }}</span>
						</template>
					</div>
					<div class="goals-view__card-actions">
						<NcButton
							:aria-label="t('health', 'Edit {goal} goal', { goal: getGoalTargetLabel(goal.targetKey) })"
							:title="t('health', 'Edit')"
							variant="tertiary"
							@click="openEdit(goal)">
							<template #icon>
								<NcIconSvgWrapper :path="iconPaths.pencil" />
							</template>
						</NcButton>
						<NcButton
							:aria-label="goal.active ? t('health', 'Pause {goal} goal', { goal: getGoalTargetLabel(goal.targetKey) }) : t('health', 'Resume {goal} goal', { goal: getGoalTargetLabel(goal.targetKey) })"
							:title="goal.active ? t('health', 'Pause') : t('health', 'Resume')"
							variant="tertiary"
							@click="setPaused(goal, !goal.active)">
							<template #icon>
								<NcIconSvgWrapper :path="goal.active ? iconPaths.pause : iconPaths.play" />
							</template>
						</NcButton>
						<NcButton
							:aria-label="t('health', 'Delete {goal} goal', { goal: getGoalTargetLabel(goal.targetKey) })"
							:title="t('health', 'Delete')"
							variant="tertiary"
							@click="requestDelete(goal)">
							<template #icon>
								<NcIconSvgWrapper :path="iconPaths.delete" />
							</template>
						</NcButton>
					</div>
				</li>
			</ul>
		</section>
		<NcDialog v-if="deleteCandidate"
			:close-on-click-outside="!deleting"
			:name="t('health', 'Delete {goal} goal?', { goal: getGoalTargetLabel(deleteCandidate.targetKey) })"
			size="small"
			@closing="deleteCandidate = null">
			<p>{{ t('health', 'Historical Health entries are not deleted.') }}</p>
			<template #actions>
				<NcButton :disabled="deleting"
					:text="t('health', 'Cancel')"
					variant="tertiary"
					@click="deleteCandidate = null" />
				<NcButton :disabled="deleting"
					:text="deleting ? t('health', 'Deleting…') : t('health', 'Delete')"
					variant="error"
					@click="confirmDelete" />
			</template>
		</NcDialog>
	</main>
</template>

<style scoped>
.goals-view { max-width: 900px; margin: 0 auto; padding: 24px; }

.goals-view__header, .goals-view__period-heading { display: flex; align-items: center; justify-content: space-between; gap: 12px; }

.goals-view__header { align-items: flex-start; margin-bottom: 24px; }

.goals-view h1, .goals-view h2, .goals-view h3, .goals-view p { margin-top: 0; }

.goals-view__header p, .goals-view__period-heading p, .goals-view__empty, .goals-view__card p { color: var(--color-text-maxcontrast); }

.goals-view__editor { display: grid; gap: 16px; }

.goals-view__dialog :deep(.dialog__name) {
	padding-block-start: calc(2 * var(--default-grid-baseline));
	margin-block-end: calc(4 * var(--default-grid-baseline));
}

.goals-view__editor-context { margin: 0; color: var(--color-text-maxcontrast); }

.goals-view__target-category + .goals-view__target-category { margin-top: 16px; }

.goals-view__target-category h3 { margin-bottom: 8px; font-size: var(--default-font-size); }

.goals-view__editor-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-top: 16px; }

.goals-view__error { color: var(--color-error-text); margin-top: 12px; }

.goals-view__loading { display: flex; justify-content: center; padding: 32px; }

.goals-view__period + .goals-view__period { margin-top: 36px; }

.goals-view__period-heading h2 { margin-bottom: 4px; }

.goals-view__period-heading p { margin-bottom: 0; }

.goals-view__period-controls, .goals-view__card-actions { display: inline-flex; align-items: center; gap: 4px; }

.goals-view__card-actions { opacity: 0; transition: opacity 120ms ease-in-out; }

.goals-view__card:hover .goals-view__card-actions, .goals-view__card:focus-within .goals-view__card-actions { opacity: 1; }

.goals-view__list { display: flex; flex-wrap: wrap; margin: calc(3 * var(--default-grid-baseline)) 0 0; padding: 0; list-style: none; gap: 12px; }

.goals-view__period-heading + .goals-view__empty { margin-top: calc(3 * var(--default-grid-baseline)); }

.goals-view__card {
	position: relative;
	box-sizing: border-box;
	flex: 0 1 18rem;
	width: 18rem;
	min-width: min(100%, 16rem);
	min-height: 17rem;
	padding: 16px;
	border: 1px solid var(--color-border-dark);
	border-radius: var(--border-radius-element);
}

.goals-view__card:hover, .goals-view__card:focus-within { background: var(--color-background-hover); }

.goals-view__card-main { display: grid; min-width: 0; padding-bottom: calc(var(--default-clickable-area) + 12px); gap: 6px; }

.goals-view__card-title { display: flex; align-items: center; gap: var(--health-metric-title-gap, calc(3 * var(--default-grid-baseline))); }

.goals-view__card-main p { margin-bottom: 0; }

.goals-view__card-actions {
	position: absolute;
	inset-inline-end: 8px;
	bottom: 8px;
	z-index: 1;
}

.goals-view__status { font-size: var(--font-size-small); font-weight: var(--font-weight-bold); }

.goals-view__status--reached { color: var(--color-success-text); }

.goals-view__status--exceeded, .goals-view__status--not_reached { color: var(--color-error-text); }
@media (hover: none), (pointer: coarse) { .goals-view__card-actions { opacity: 1; } }
@media (prefers-reduced-motion: reduce) { .goals-view__card-actions { transition: none; } }
@media (max-width: 600px) { .goals-view { padding: 16px; } .goals-view__header, .goals-view__period-heading { align-items: stretch; flex-direction: column; } .goals-view__editor-grid { grid-template-columns: 1fr; } .goals-view__card { width: 100%; min-width: 0; } }
</style>
