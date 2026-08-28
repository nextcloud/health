<script setup lang="ts">
import type { HealthConfiguration } from '../api/configuration.ts'
import type { GoalProgress, GoalTarget } from '../api/goals.ts'
import type { Measurement } from '../api/measurements.ts'
import type { MeasurementMetricKey, Unit } from '../metrics.ts'

import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed, ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcTextArea from '@nextcloud/vue/components/NcTextArea'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import DailyGoalPopover from './DailyGoalPopover.vue'
import DetailInformationPopover from './DetailInformationPopover.vue'
import EntryContextIndicator from './EntryContextIndicator.vue'
import MetricAggregateRow from './MetricAggregateRow.vue'
import ModalActions from './ModalActions.vue'
import { getEnabledMetricKeys } from '../api/configuration.ts'
import { createMeasurement, deleteMeasurement, listMeasurements, updateMeasurement } from '../api/measurements.ts'
import { goalProgressForMetric } from '../goals.ts'
import { iconPaths } from '../icons.ts'
import { fromCanonical, getMetricLabel, getMetricUnits, getUnitLabel, MEASUREMENT_METRIC_KEYS } from '../metrics.ts'
import { getLocalDayRange, recordedAtForLocalDay } from '../utils/dates.ts'

const props = defineProps<{
	date: Date
	configuration: HealthConfiguration | null
	goalProgresses?: GoalProgress[]
	goalTargets?: GoalTarget[]
}>()
const emit = defineEmits<{ saved: [] }>()
const measurements = ref<Measurement[]>([])
const creating = ref<MeasurementMetricKey | null>(null)
const editing = ref<Measurement | null>(null)
const deleteCandidate = ref<Measurement | null>(null)
const value = ref('')
const systolic = ref('')
const diastolic = ref('')
const note = ref('')
const unit = ref<Unit>('celsius')
const saving = ref(false)
const deleting = ref(false)
const expanded = ref<Record<string, boolean>>({})
const enabledKeys = computed(() => getEnabledMetricKeys(props.configuration, MEASUREMENT_METRIC_KEYS))
const activeMetricKey = computed<MeasurementMetricKey | null>(() => editing.value?.metricKey ?? creating.value)
const dateFormatter = new Intl.DateTimeFormat(undefined, { dateStyle: 'full' })

function valuesFor(key: MeasurementMetricKey) {
	return measurements.value.filter((measurement) => measurement.metricKey === key)
}

function progressesFor(key: MeasurementMetricKey): GoalProgress[] {
	return goalProgressForMetric(props.goalProgresses ?? [], key)
}

function displayUnit(metricKey: MeasurementMetricKey): Unit {
	return props.configuration?.metrics[metricKey]?.displayUnit ?? getMetricUnits(metricKey)[0]
}

function unitDescriptionId(metricKey: MeasurementMetricKey): string {
	return `measurement-${metricKey}-unit`
}

function formatNumber(value: number): string {
	return value.toLocaleString(undefined, { maximumFractionDigits: 2 })
}

function display(measurement: Measurement): string {
	const currentUnit = displayUnit(measurement.metricKey)
	if (measurement.values !== null) {
		return `${formatNumber(fromCanonical('blood_pressure', measurement.values.systolic, currentUnit))} / ${formatNumber(fromCanonical('blood_pressure', measurement.values.diastolic, currentUnit))} ${getUnitLabel(currentUnit)}`
	}

	return `${formatNumber(fromCanonical(measurement.metricKey, measurement.numericValue ?? 0, currentUnit))} ${getUnitLabel(currentUnit)}`
}

async function load() {
	try {
		const range = getLocalDayRange(props.date)
		measurements.value = await listMeasurements(range.from, range.to)
	} catch {
		showError(t('health', 'Measurements could not be loaded.'))
	}
}

function resetDialog() {
	creating.value = null
	editing.value = null
	value.value = ''
	systolic.value = ''
	diastolic.value = ''
	note.value = ''
}

function open(metricKey: MeasurementMetricKey) {
	resetDialog()
	creating.value = metricKey
	unit.value = displayUnit(metricKey)
}

function startEditing(measurement: Measurement) {
	resetDialog()
	editing.value = measurement
	unit.value = displayUnit(measurement.metricKey)
	if (measurement.values !== null) {
		systolic.value = String(fromCanonical('blood_pressure', measurement.values.systolic, unit.value))
		diastolic.value = String(fromCanonical('blood_pressure', measurement.values.diastolic, unit.value))
	} else {
		value.value = String(fromCanonical(measurement.metricKey, measurement.numericValue ?? 0, unit.value))
	}
	note.value = measurement.note ?? ''
}

async function save() {
	if (activeMetricKey.value === null) {
		return
	}

	const numeric = Number(value.value)
	const systolicValue = Number(systolic.value)
	const diastolicValue = Number(diastolic.value)
	if ((activeMetricKey.value === 'blood_pressure' && (!Number.isFinite(systolicValue) || !Number.isFinite(diastolicValue))) || (activeMetricKey.value !== 'blood_pressure' && !Number.isFinite(numeric))) {
		return
	}

	saving.value = true
	try {
		if (editing.value === null) {
			const created = await createMeasurement({
				metricKey: activeMetricKey.value,
				numericValue: activeMetricKey.value === 'blood_pressure' ? null : numeric,
				values: activeMetricKey.value === 'blood_pressure' ? { systolic: systolicValue, diastolic: diastolicValue } : null,
				unit: unit.value,
				recordedAt: recordedAtForLocalDay(props.date),
				note: note.value.trim() === '' ? null : note.value.trim(),
				context: 'manual',
				source: 'web',
			})
			measurements.value = [created, ...measurements.value]
			showSuccess(t('health', '{metric} recorded.', { metric: getMetricLabel(created.metricKey) }))
		} else {
			const current = editing.value
			const updated = await updateMeasurement(current.id, {
				numericValue: current.metricKey === 'blood_pressure' ? null : numeric,
				values: current.metricKey === 'blood_pressure' ? { systolic: systolicValue, diastolic: diastolicValue } : null,
				unit: unit.value,
				recordedAt: current.recordedAt,
				note: note.value.trim() === '' ? null : note.value.trim(),
				context: current.context as 'manual' | 'checkin' | 'checkout',
			})
			measurements.value = measurements.value.map((measurement) => measurement.id === updated.id ? updated : measurement)
			showSuccess(t('health', '{metric} updated.', { metric: getMetricLabel(updated.metricKey) }))
		}
		resetDialog()
		emit('saved')
	} catch {
		showError(t('health', 'Measurement could not be saved.'))
	} finally {
		saving.value = false
	}
}

function requestDelete(measurement: Measurement) {
	deleteCandidate.value = measurement
}

async function confirmDelete() {
	if (deleteCandidate.value === null) {
		return
	}

	const measurement = deleteCandidate.value
	deleting.value = true
	try {
		await deleteMeasurement(measurement.id)
		measurements.value = measurements.value.filter((current) => current.id !== measurement.id)
		deleteCandidate.value = null
		emit('saved')
		showSuccess(t('health', '{metric} measurement deleted.', { metric: getMetricLabel(measurement.metricKey) }))
	} catch {
		showError(t('health', 'Measurement could not be deleted.'))
	} finally {
		deleting.value = false
	}
}

watch(() => [props.date, props.configuration] as const, load, { immediate: true })
</script>

<template>
	<section v-if="enabledKeys.length > 0" class="measurements-section">
		<section v-for="metricKey in enabledKeys" :key="metricKey" class="measurements-section__group">
			<MetricAggregateRow
				:count="valuesFor(metricKey).length"
				:details-id="`measurement-${metricKey}-details`"
				:expandable="valuesFor(metricKey).length > 0"
				:expanded="expanded[metricKey] ?? false"
				:metric-key="metricKey"
				@toggle="expanded[metricKey] = !(expanded[metricKey] ?? false)">
				<template #status>
					<DailyGoalPopover v-if="progressesFor(metricKey).length"
						:progresses="progressesFor(metricKey)"
						:targets="goalTargets ?? []" />
				</template>
				<template #actions>
					<NcButton
						:aria-label="t('health', 'Add {metric} measurement', { metric: getMetricLabel(metricKey) })"
						:title="t('health', 'Add {metric} measurement', { metric: getMetricLabel(metricKey) })"
						variant="tertiary"
						@click="open(metricKey)">
						<template #icon>
							<NcIconSvgWrapper :path="iconPaths.plus" />
						</template>
					</NcButton>
				</template>
				<template #details>
					<ul class="measurements-section__list">
						<li v-for="measurement in valuesFor(metricKey)" :key="measurement.id" class="measurements-section__item">
							<article class="measurements-section__detail">
								<time :datetime="measurement.recordedAt">{{ new Date(measurement.recordedAt).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' }) }}</time>
								<span class="measurements-section__detail-value">
									{{ display(measurement) }}
									<EntryContextIndicator :context="measurement.context" />
								</span>
								<div class="measurements-section__detail-actions">
									<NcButton :aria-label="t('health', 'Edit {metric} measurement', { metric: getMetricLabel(measurement.metricKey) })"
										:title="t('health', 'Edit')"
										variant="tertiary"
										@click="startEditing(measurement)">
										<template #icon>
											<NcIconSvgWrapper :path="iconPaths.pencil" />
										</template>
									</NcButton>
									<NcButton :aria-label="t('health', 'Delete {metric} measurement', { metric: getMetricLabel(measurement.metricKey) })"
										:title="t('health', 'Delete')"
										variant="tertiary"
										@click="requestDelete(measurement)">
										<template #icon>
											<NcIconSvgWrapper :path="iconPaths.delete" />
										</template>
									</NcButton>
									<DetailInformationPopover
										:id="measurement.id"
										:context="measurement.context"
										:record-label="getMetricLabel(measurement.metricKey)"
										:source="measurement.source" />
								</div>
							</article>
						</li>
					</ul>
				</template>
			</MetricAggregateRow>
		</section>
	</section>

	<NcDialog v-if="activeMetricKey"
		:close-on-click-outside="!saving"
		:name="editing ? t('health', 'Edit {metric}', { metric: getMetricLabel(activeMetricKey) }) : getMetricLabel(activeMetricKey)"
		size="small"
		@closing="resetDialog">
		<p class="measurements-section__modal-context">
			{{ dateFormatter.format(date) }}<template v-if="editing">
				· {{ new Date(editing.recordedAt).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' }) }}
			</template>
		</p>
		<div v-if="activeMetricKey === 'blood_pressure'" class="measurements-section__pressure">
			<NcTextField v-model="systolic"
				:aria-describedby="unitDescriptionId(activeMetricKey)"
				:label="t('health', 'Systolic')"
				inputmode="decimal" />
			<NcTextField v-model="diastolic"
				:aria-describedby="unitDescriptionId(activeMetricKey)"
				:label="t('health', 'Diastolic')"
				inputmode="decimal" />
			<span :id="unitDescriptionId(activeMetricKey)" class="measurements-section__unit">{{ getUnitLabel(unit) }}</span>
		</div>
		<div v-else class="measurements-section__input-with-unit">
			<NcTextField v-model="value"
				:aria-describedby="unitDescriptionId(activeMetricKey)"
				:label="getMetricLabel(activeMetricKey)"
				inputmode="decimal" />
			<span :id="unitDescriptionId(activeMetricKey)" class="measurements-section__unit">{{ getUnitLabel(unit) }}</span>
		</div>
		<NcTextArea v-model="note"
			:disabled="saving"
			:label="t('health', 'Optional note')"
			:maxlength="1000"
			:placeholder="t('health', 'Optional note')" />
		<template #actions>
			<ModalActions>
				<NcButton :disabled="saving"
					:text="t('health', 'Cancel')"
					variant="tertiary"
					@click="resetDialog" />
				<NcButton :disabled="saving"
					:text="t('health', 'Save')"
					variant="primary"
					@click="save" />
			</ModalActions>
		</template>
	</NcDialog>

	<NcDialog v-if="deleteCandidate"
		:close-on-click-outside="!deleting"
		:name="t('health', 'Delete measurement')"
		size="small"
		@closing="deleteCandidate = null">
		<p>{{ t('health', 'Delete this {metric} measurement? This action cannot be undone.', { metric: getMetricLabel(deleteCandidate.metricKey) }) }}</p>
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
</template>

<style scoped>
.measurements-section { margin: 0; }

.measurements-section__list { margin: 0; padding: 0; list-style: none; }

.measurements-section__item + .measurements-section__item { margin-top: var(--default-grid-baseline); }

.measurements-section__detail { display: grid; grid-template-columns: 4.75rem minmax(0, 1fr) max-content; align-items: center; gap: 10px; min-height: var(--default-clickable-area); padding: 8px 0; }

.measurements-section__detail-actions { display: inline-flex; align-items: center; min-width: calc(3 * var(--default-clickable-area)); opacity: 0; transition: opacity 120ms ease-in-out; }

.measurements-section__detail-value { display: inline-flex; align-items: center; min-width: 0; gap: 6px; }

.measurements-section__detail:hover .measurements-section__detail-actions,
.measurements-section__detail:focus-within .measurements-section__detail-actions { opacity: 1; }

.measurements-section__input-with-unit,
.measurements-section__pressure { display: grid; align-items: end; gap: 8px; }

.measurements-section__input-with-unit { grid-template-columns: minmax(0, 1fr) max-content; }

.measurements-section__pressure { grid-template-columns: repeat(2, minmax(0, 1fr)) max-content; }

.measurements-section__unit { padding-bottom: calc((var(--default-clickable-area) - 1em) / 2); white-space: nowrap; }

.measurements-section__modal-context { margin-top: 0; color: var(--color-text-maxcontrast); }

@media (hover: none), (pointer: coarse) {
	.measurements-section__detail-actions { opacity: 1; }
}

@media (prefers-reduced-motion: reduce) {
	.measurements-section__detail-actions { transition: none; }
}

@media (max-width: 600px) {
	.measurements-section__detail { grid-template-columns: 4.25rem minmax(0, 1fr) max-content; }
	.measurements-section__pressure { grid-template-columns: minmax(0, 1fr) max-content; }
	.measurements-section__pressure :deep(.input-field:nth-child(2)) { grid-column: 1; }
	.measurements-section__pressure .measurements-section__unit { grid-column: 2; grid-row: 1 / span 2; }
}
</style>
