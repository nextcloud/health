<!-- eslint-disable @stylistic/max-statements-per-line -->
<script setup lang="ts">
import type { HealthConfiguration } from '../api/configuration.ts'
import type { DailyValue } from '../api/dailyValues.ts'
import type { GoalProgress, GoalTarget } from '../api/goals.ts'
import type { DailyValueMetricKey, Unit } from '../metrics.ts'

import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed, ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcProgressBar from '@nextcloud/vue/components/NcProgressBar'
import NcRadioGroup from '@nextcloud/vue/components/NcRadioGroup'
import NcRadioGroupButton from '@nextcloud/vue/components/NcRadioGroupButton'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import BmiInformationPopover from './BmiInformationPopover.vue'
import DailyGoalPopover from './DailyGoalPopover.vue'
import MetricIcon from './MetricIcon.vue'
import MetricValueCard from './MetricValueCard.vue'
import ModalActions from './ModalActions.vue'
import { getEnabledMetricKeys } from '../api/configuration.ts'
import { listDailyValues, upsertDailyValue } from '../api/dailyValues.ts'
import { goalProgressForMetric, goalTargetByKey } from '../goals.ts'
import { DAILY_VALUE_METRIC_KEYS, fromCanonical, getMetricLabel, getMetricUnits, getUnitLabel, hasDisplayUnit } from '../metrics.ts'
import { normalizeWeightInput } from '../utils/weightInput.ts'

const props = defineProps<{
	date: string
	configuration: HealthConfiguration | null
	goalProgresses?: GoalProgress[]
	goalTargets?: GoalTarget[]
}>()
const emit = defineEmits<{ saved: [] }>()
const values = ref<DailyValue[]>([])
const editingKey = ref<DailyValueMetricKey | null>(null)
const numericValue = ref('')
const editorUnit = ref<Unit | null>('kg')
const saving = ref(false)
const inputError = ref<string | null>(null)
const enabledKeys = computed(() => getEnabledMetricKeys(props.configuration, DAILY_VALUE_METRIC_KEYS))
const editingUnit = computed(() => editorUnit.value)

function unitDescriptionId(metricKey: DailyValueMetricKey): string {
	return `daily-value-${metricKey}-unit`
}

function displayUnit(metricKey: DailyValueMetricKey): Unit | null { return props.configuration?.metrics[metricKey].displayUnit ?? getMetricUnits(metricKey)[0] ?? null }
function format(value: DailyValue): string {
	const unit = displayUnit(value.metricKey)
	return (unit === null ? value.numericValue : fromCanonical(value.metricKey, value.numericValue, unit)).toLocaleString(undefined, { maximumFractionDigits: 2 })
}
function find(metricKey: DailyValueMetricKey): DailyValue | undefined { return values.value.find((value) => value.metricKey === metricKey) }
async function load() { try { values.value = await listDailyValues(props.date) } catch { showError(t('health', 'Daily values could not be loaded.')) } }
function edit(metricKey: DailyValueMetricKey) {
	editingKey.value = metricKey
	inputError.value = null
	editorUnit.value = displayUnit(metricKey)
	const value = find(metricKey)
	numericValue.value = value === undefined ? '' : String(editorUnit.value === null ? value.numericValue : fromCanonical(metricKey, value.numericValue, editorUnit.value))
}
function progressesFor(metricKey: DailyValueMetricKey): GoalProgress[] { return goalProgressForMetric(props.goalProgresses ?? [], metricKey) }
function targetFor(progress: GoalProgress): GoalTarget | undefined { return goalTargetByKey(props.goalTargets ?? [], progress.targetKey) }
function hasCompactProgress(metricKey: DailyValueMetricKey): boolean {
	return progressesFor(metricKey).some((progress) => progress.comparator === 'gte' && progress.progressRatio !== null && targetFor(progress)?.kind !== 'latest_value')
}
function compactProgress(metricKey: DailyValueMetricKey): GoalProgress | undefined {
	return progressesFor(metricKey).find((progress) => progress.comparator === 'gte' && progress.progressRatio !== null && targetFor(progress)?.kind !== 'latest_value')
}
function compactProgressValue(metricKey: DailyValueMetricKey): number {
	return Math.round(Math.max(0, Math.min(1, compactProgress(metricKey)?.progressRatio ?? 0)) * 100)
}
function compactProgressColor(metricKey: DailyValueMetricKey): string {
	const percentage = compactProgressValue(metricKey)
	if (percentage < 20) {
		return 'var(--color-warning)'
	}
	return percentage === 100 ? 'var(--color-success)' : 'var(--color-primary-element)'
}
async function save() {
	if (editingKey.value === null || numericValue.value.trim() === '') { return }
	const value = editingKey.value === 'weight'
		? normalizeWeightInput(numericValue.value)
		: Number(numericValue.value)
	if (value === null || !Number.isFinite(value)) {
		inputError.value = t('health', 'Enter a valid number.')
		return
	}
	inputError.value = null
	saving.value = true
	try {
		const updated = await upsertDailyValue(editingKey.value, props.date, value, editingUnit.value)
		values.value = [...values.value.filter((current) => current.metricKey !== updated.metricKey), updated]
		editingKey.value = null
		emit('saved')
		showSuccess(t('health', '{metric} saved.', { metric: getMetricLabel(updated.metricKey) }))
	} catch {
		showError(t('health', 'Daily value could not be saved.'))
	} finally {
		saving.value = false
	}
}
watch(() => [props.date, props.configuration] as const, load, { immediate: true })
</script>

<template>
	<section v-if="enabledKeys.length > 0" :aria-label="t('health', 'Daily values')" class="daily-values">
		<div class="daily-values__grid">
			<MetricValueCard v-for="metricKey in enabledKeys"
				:key="metricKey"
				class="daily-values__card">
				<NcButton :aria-label="t('health', 'Edit {metric}', { metric: getMetricLabel(metricKey) })"
					class="daily-values__card-action"
					variant="tertiary"
					@click="edit(metricKey)">
					<span class="daily-values__metric-heading">
						<MetricIcon :metric-key="metricKey" />
						<strong class="daily-values__metric-title">{{ getMetricLabel(metricKey) }}</strong>
					</span>
					<template v-if="metricKey === 'job_satisfaction'">
						<div v-if="find(metricKey)"
							:aria-label="t('health', '{metric}, {value} out of 5', { metric: getMetricLabel(metricKey), value: find(metricKey)!.numericValue })"
							class="daily-values__job-value-progress"
							role="img">
							<NcProgressBar :aria-hidden="true"
								color="var(--color-primary-element)"
								:value="find(metricKey)!.numericValue * 20" />
						</div>
						<span v-else class="daily-values__value">—</span>
					</template>
					<template v-else>
						<span class="daily-values__value">{{ find(metricKey) ? format(find(metricKey)!) : '—' }}</span>
						<span v-if="hasDisplayUnit(metricKey)" class="daily-values__display-unit">{{ getUnitLabel(displayUnit(metricKey)!) }}</span>
					</template>
				</NcButton>
				<div v-if="hasCompactProgress(metricKey)" class="daily-values__goal-progress">
					<DailyGoalPopover :progresses="progressesFor(metricKey)"
						:targets="goalTargets ?? []" />
					<div :aria-label="t('health', '{percent} percent of the goal reached', { percent: compactProgressValue(metricKey) })" role="img">
						<NcProgressBar :aria-hidden="true"
							:color="compactProgressColor(metricKey)"
							:value="compactProgressValue(metricKey)" />
					</div>
				</div>
				<DailyGoalPopover v-else-if="progressesFor(metricKey).length"
					class="daily-values__goal-popover"
					:progresses="progressesFor(metricKey)"
					:targets="goalTargets ?? []" />
				<div v-if="metricKey === 'weight' && find(metricKey)?.bmi != null" class="daily-values__bmi-row">
					<span class="daily-values__bmi">{{ t('health', 'BMI {value}', { value: find(metricKey)!.bmi!.toFixed(1) }) }}</span>
					<BmiInformationPopover :bmi="find(metricKey)!.bmi!" />
				</div>
			</MetricValueCard>
		</div>
	</section>
	<NcDialog v-if="editingKey"
		:name="getMetricLabel(editingKey)"
		size="small"
		@closing="editingKey = null">
		<NcNoteCard v-if="inputError !== null" type="error" :text="inputError" />
		<template v-if="editingKey === 'job_satisfaction'">
			<NcRadioGroup v-model="numericValue"
				:label="getMetricLabel(editingKey)"
				hide-label>
				<NcRadioGroupButton v-for="choice in ['1', '2', '3', '4', '5']"
					:key="choice"
					:label="choice"
					:value="choice" />
			</NcRadioGroup>
			<div :aria-label="t('health', '{percent} percent selected', { percent: Number(numericValue || 0) * 20 })" class="daily-values__job-progress" role="img">
				<NcProgressBar :aria-hidden="true" :value="Number(numericValue || 0) * 20" />
			</div>
		</template>
		<template v-else>
			<div class="daily-values__input-with-unit"
				:class="{ 'daily-values__input-with-unit--without-unit': !hasDisplayUnit(editingKey) }">
				<NcTextField v-model="numericValue"
					:aria-describedby="hasDisplayUnit(editingKey) ? unitDescriptionId(editingKey) : undefined"
					:label="getMetricLabel(editingKey)"
					inputmode="decimal" />
				<span v-if="hasDisplayUnit(editingKey) && editingUnit" :id="unitDescriptionId(editingKey)" class="daily-values__unit">{{ getUnitLabel(editingUnit) }}</span>
			</div>
		</template>
		<div v-if="editingKey === 'weight' && find(editingKey)?.bmi != null" class="daily-values__editor-bmi">
			<span>{{ t('health', 'BMI {value}', { value: find(editingKey)!.bmi!.toFixed(1) }) }}</span>
			<BmiInformationPopover :bmi="find(editingKey)!.bmi!" />
		</div>
		<template #actions>
			<ModalActions>
				<NcButton :disabled="saving"
					:text="t('health', 'Cancel')"
					variant="tertiary"
					@click="editingKey = null" />
				<NcButton :disabled="saving || numericValue.trim() === '' || (editingKey === 'job_satisfaction' && !['1', '2', '3', '4', '5'].includes(numericValue))"
					:text="t('health', 'Save')"
					variant="primary"
					@click="save" />
			</ModalActions>
		</template>
	</NcDialog>
</template>

<style scoped>
.daily-values {
	margin-block: calc(4 * var(--default-grid-baseline));
}

.daily-values__grid {
	display: flex;
	flex-wrap: wrap;
	gap: calc(3 * var(--default-grid-baseline));
}

.daily-values__card:hover,
.daily-values__card:focus-within {
	background-color: var(--color-primary-element-light-hover);
}

.daily-values__card:focus-within {
	outline: 2px solid var(--color-main-text);
	outline-offset: 2px;
}

.daily-values__card-action {
	display: flex;
	align-items: stretch;
	justify-content: center;
	width: 100%;
	flex: 1 1 auto;
	padding: 0;
	color: var(--color-main-text);
	text-align: center;
}

.daily-values__card-action,
.daily-values__card-action:hover:not(:disabled),
.daily-values__card-action:active:not(:disabled) {
	background-color: transparent !important;
	border-color: transparent !important;
	border-radius: 0;
	box-shadow: none !important;
}

.daily-values__card-action:active:not(:disabled) {
	transform: none !important;
}

.daily-values__card-action:focus-visible {
	outline: none !important;
	box-shadow: none !important;
}

.daily-values__card-action :deep(.button-vue__wrapper) {
	align-items: center;
	width: 100%;
	height: 100%;
}

.daily-values__card-action :deep(.button-vue__text) {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	width: 100%;
	margin: 0;
	padding: 0;
	text-align: center;
	white-space: normal;
	overflow: visible;
	gap: var(--default-grid-baseline);
}

.daily-values__metric-heading {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	max-width: 100%;
	gap: var(--health-metric-title-gap, calc(3 * var(--default-grid-baseline)));
}

.daily-values__metric-title {
	min-width: 0;
	text-align: center;
	overflow-wrap: anywhere;
}

.daily-values__value {
	margin-top: var(--default-grid-baseline);
	font-size: 1.75rem;
	font-weight: var(--font-weight-heading, bold);
	font-variant-numeric: tabular-nums;
	line-height: 1.15;
}

.daily-values__display-unit {
	color: var(--color-text-maxcontrast);
}

.daily-values__bmi-row {
	display: flex;
	align-items: center;
	justify-content: center;
	margin-top: var(--default-grid-baseline);
	gap: var(--default-grid-baseline);
}

.daily-values__goal-progress { display: grid; grid-template-columns: var(--default-clickable-area) minmax(0, 1fr); align-items: center; width: min(100%, 12rem); margin: 8px auto 0; gap: 4px; }

.daily-values__job-value-progress { width: 100%; margin-top: var(--default-grid-baseline); }

.daily-values__goal-popover { align-self: center; margin-top: var(--default-grid-baseline); }

.daily-values__job-progress { margin-top: 12px; }

.daily-values__bmi {
	color: var(--color-text-maxcontrast);
	font-size: var(--font-size-small);
}

.daily-values__editor-bmi { display: flex; align-items: center; gap: 4px; color: var(--color-text-maxcontrast); }

.daily-values__input-with-unit { display: grid; grid-template-columns: minmax(0, 1fr) max-content; align-items: end; gap: 8px; }

.daily-values__input-with-unit--without-unit { grid-template-columns: minmax(0, 1fr); }

.daily-values__unit { padding-bottom: calc((var(--default-clickable-area) - 1em) / 2); white-space: nowrap; }

</style>
