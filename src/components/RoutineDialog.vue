<!-- eslint-disable @stylistic/max-statements-per-line -->
<script setup lang="ts">
import type { HealthConfiguration } from '../api/configuration.ts'
import type { RoutineResult } from '../api/routines.ts'
import type { AllMetricKey, Unit } from '../metrics.ts'

import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { imagePath } from '@nextcloud/router'
import { computed, ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcProgressBar from '@nextcloud/vue/components/NcProgressBar'
import NcRadioGroup from '@nextcloud/vue/components/NcRadioGroup'
import NcRadioGroupButton from '@nextcloud/vue/components/NcRadioGroupButton'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import EventSymbol from './EventSymbol.vue'
import MetricIcon from './MetricIcon.vue'
import ModalActions from './ModalActions.vue'
import { isMetricEnabledForRoutine } from '../api/configuration.ts'
import { submitRoutine } from '../api/routines.ts'
import {
	DAILY_VALUE_METRIC_KEYS,
	getEventOptions,
	getMetricLabel,
	getMetricUnits,
	getMetricVisual,
	getOptionLabel,
	getOptionSymbol,
	getUnitLabel,
	hasDisplayUnit,
	isScaleMetric,
	MEASUREMENT_METRIC_KEYS,
	METRIC_KEYS,
} from '../metrics.ts'

const props = defineProps<{ open: boolean, context: 'check-in' | 'check-out', date: string, configuration: HealthConfiguration | null }>()
const emit = defineEmits<{ 'update:open': [boolean], saved: [result: RoutineResult] }>()
const scaleValues = ref<Record<string, string>>({})
const eventValues = ref<Record<string, string>>({})
const numericValues = ref<Record<string, string>>({})
const systolic = ref('')
const diastolic = ref('')
const saving = ref(false)
const journalKeys = computed(() => METRIC_KEYS.filter((key) => isMetricEnabledForRoutine(props.configuration, key, props.context)))
const measurementKeys = computed(() => MEASUREMENT_METRIC_KEYS.filter((key) => isMetricEnabledForRoutine(props.configuration, key, props.context)))
const dailyKeys = computed(() => DAILY_VALUE_METRIC_KEYS.filter((key) => isMetricEnabledForRoutine(props.configuration, key, props.context)))

function hasNumericValue(value: string | undefined): boolean {
	return value !== undefined && value.trim() !== '' && Number.isFinite(Number(value))
}

const hasInput = computed(() => {
	const hasJournalInput = journalKeys.value.some((key) => {
		return isScaleMetric(key) ? Boolean(scaleValues.value[key]) : Boolean(eventValues.value[key])
	})
	const hasMeasurementInput = measurementKeys.value.some((key) => {
		return key === 'blood_pressure'
			? hasNumericValue(systolic.value) && hasNumericValue(diastolic.value)
			: hasNumericValue(numericValues.value[key])
	})
	const hasDailyValueInput = dailyKeys.value.some((key) => hasNumericValue(numericValues.value[key]))

	return hasJournalInput || hasMeasurementInput || hasDailyValueInput
})

function reset() {
	scaleValues.value = {}
	eventValues.value = {}
	numericValues.value = {}
	systolic.value = ''
	diastolic.value = ''
}

function unit(key: AllMetricKey): Unit | null {
	return props.configuration?.metrics[key]?.displayUnit ?? getMetricUnits(key)[0] ?? null
}

function unitId(key: AllMetricKey): string {
	return `routine-${props.context}-${key}-unit`
}

function eventOptions(key: AllMetricKey) {
	return key === 'hydration' || key === 'break' ? getEventOptions(key) : []
}

const illustrationUrl = computed(() => imagePath('health', props.context === 'check-in' ? 'checkin.png' : 'checkout.png'))

async function save() {
	if (!hasInput.value) {
		return
	}

	const journalMetrics: Array<Record<string, unknown>> = []
	for (const key of journalKeys.value) {
		if (isScaleMetric(key) && scaleValues.value[key]) {
			journalMetrics.push({ metricKey: key, numericValue: Number(scaleValues.value[key]), optionValue: null })
		}
		if (!isScaleMetric(key) && eventValues.value[key]) {
			journalMetrics.push({ metricKey: key, numericValue: null, optionValue: eventValues.value[key] })
		}
	}

	const measurements: Array<Record<string, unknown>> = []
	for (const key of measurementKeys.value) {
		if (key === 'blood_pressure') {
			if (hasNumericValue(systolic.value) && hasNumericValue(diastolic.value)) {
				measurements.push({ metricKey: key, values: { systolic: Number(systolic.value), diastolic: Number(diastolic.value) }, unit: unit(key) })
			}
		} else if (hasNumericValue(numericValues.value[key])) {
			measurements.push({ metricKey: key, numericValue: Number(numericValues.value[key]), unit: unit(key) })
		}
	}

	const dailyValues: Array<Record<string, unknown>> = []
	for (const key of dailyKeys.value) {
		if (hasNumericValue(numericValues.value[key])) {
			dailyValues.push({ metricKey: key, numericValue: Number(numericValues.value[key]), unit: unit(key) })
		}
	}

	saving.value = true
	try {
		const result = await submitRoutine(props.context, { date: props.date, recordedAt: new Date().toISOString(), journalMetrics, measurements, dailyValues })
		emit('saved', result)
		emit('update:open', false)
		reset()
	} catch {
		showError(t('health', 'Routine could not be saved.'))
	} finally {
		saving.value = false
	}
}

watch(() => props.open, (open) => {
	if (open) {
		reset()
	}
})
</script>

<template>
	<NcDialog :open="open"
		:name="context === 'check-in' ? t('health', 'Check-in') : t('health', 'Check-out')"
		size="small"
		@update:open="emit('update:open', $event)">
		<img :src="illustrationUrl" alt="" class="routine-dialog__illustration">
		<section v-if="journalKeys.length" class="routine-dialog__section">
			<div v-for="key in journalKeys" :key="key" class="routine-dialog__field">
				<strong class="routine-dialog__metric-heading">
					<MetricIcon :metric-key="key" />
					{{ getMetricLabel(key) }}
				</strong>
				<NcRadioGroup v-if="isScaleMetric(key)"
					v-model="scaleValues[key]"
					:label="getMetricLabel(key)"
					hide-label>
					<NcRadioGroupButton v-for="choice in ['1', '2', '3', '4', '5']"
						:key="choice"
						:label="choice"
						:value="choice" />
				</NcRadioGroup>
				<NcProgressBar v-if="isScaleMetric(key)"
					class="routine-dialog__scale-progress"
					:color="getMetricVisual(key).color"
					:size="8"
					:value="Number(scaleValues[key] ?? 0) * 20" />
				<NcRadioGroup v-else
					v-model="eventValues[key]"
					class="routine-dialog__event-options"
					:label="getMetricLabel(key)"
					hide-label>
					<NcRadioGroupButton v-for="option in eventOptions(key)"
						:key="option"
						:label="getOptionLabel(key, option)"
						:value="option">
						<template #icon>
							<EventSymbol :symbol="getOptionSymbol(key, option)" size="button" />
						</template>
					</NcRadioGroupButton>
				</NcRadioGroup>
			</div>
		</section>
		<section v-if="measurementKeys.length" class="routine-dialog__section">
			<div v-for="key in measurementKeys" :key="key" class="routine-dialog__field">
				<strong class="routine-dialog__metric-heading">
					<MetricIcon :metric-key="key" />
					{{ getMetricLabel(key) }}
				</strong>
				<div v-if="key === 'blood_pressure'" class="routine-dialog__pressure">
					<NcTextField v-model="systolic"
						:aria-describedby="unitId(key)"
						:label="t('health', 'Systolic')"
						inputmode="decimal" />
					<NcTextField v-model="diastolic"
						:aria-describedby="unitId(key)"
						:label="t('health', 'Diastolic')"
						inputmode="decimal" />
					<span v-if="unit(key)" :id="unitId(key)" class="routine-dialog__unit">{{ getUnitLabel(unit(key)!) }}</span>
				</div>
				<div v-else class="routine-dialog__input-with-unit">
					<NcTextField v-model="numericValues[key]"
						:aria-describedby="hasDisplayUnit(key) ? unitId(key) : undefined"
						:label="getMetricLabel(key)"
						inputmode="decimal" />
					<span v-if="hasDisplayUnit(key)" :id="unitId(key)" class="routine-dialog__unit">{{ getUnitLabel(unit(key)!) }}</span>
				</div>
			</div>
		</section>
		<section v-if="dailyKeys.length" class="routine-dialog__section">
			<div v-for="key in dailyKeys" :key="key" class="routine-dialog__field">
				<strong class="routine-dialog__metric-heading">
					<MetricIcon :metric-key="key" />
					{{ getMetricLabel(key) }}
				</strong>
				<NcRadioGroup v-if="key === 'job_satisfaction'"
					v-model="numericValues[key]"
					:label="getMetricLabel(key)"
					hide-label>
					<NcRadioGroupButton v-for="choice in ['1', '2', '3', '4', '5']"
						:key="choice"
						:label="choice"
						:value="choice" />
				</NcRadioGroup>
				<NcProgressBar v-if="key === 'job_satisfaction'"
					class="routine-dialog__scale-progress"
					:size="8"
					:value="Number(numericValues[key] ?? 0) * 20" />
				<div v-else
					class="routine-dialog__input-with-unit"
					:class="{ 'routine-dialog__input-with-unit--without-unit': !hasDisplayUnit(key) }">
					<NcTextField v-model="numericValues[key]"
						:aria-describedby="hasDisplayUnit(key) ? unitId(key) : undefined"
						:label="getMetricLabel(key)"
						inputmode="decimal" />
					<span v-if="hasDisplayUnit(key) && unit(key)" :id="unitId(key)" class="routine-dialog__unit">{{ getUnitLabel(unit(key)!) }}</span>
				</div>
			</div>
		</section>
		<template #actions>
			<ModalActions>
				<NcButton :disabled="saving"
					:text="t('health', 'Cancel')"
					variant="tertiary"
					@click="emit('update:open', false)" />
				<NcButton :disabled="saving || !hasInput"
					:text="context === 'check-in' ? t('health', 'Check in') : t('health', 'Check out')"
					variant="primary"
					@click="save" />
			</ModalActions>
		</template>
	</NcDialog>
</template>

<style scoped>
.routine-dialog__illustration {
	display: block;
	width: 100%;
	height: clamp(11rem, 38vw, 15rem);
	margin-bottom: calc(5 * var(--default-grid-baseline));
	object-fit: cover;
	border-radius: var(--border-radius-element);
}

.routine-dialog__section + .routine-dialog__section { margin-top: 28px; }

.routine-dialog__field {
	display: flex;
	flex-direction: column;
	gap: 8px;
	margin-top: 20px;
}

.routine-dialog__field:first-child { margin-top: 0; }

.routine-dialog__metric-heading {
	display: inline-flex;
	align-items: center;
	gap: var(--health-metric-title-gap, calc(3 * var(--default-grid-baseline)));
}

.routine-dialog__scale-progress { width: 100%; }

.routine-dialog__input-with-unit,
.routine-dialog__pressure {
	display: grid;
	align-items: end;
	gap: 8px;
}

.routine-dialog__input-with-unit {
	grid-template-columns: minmax(0, 1fr) max-content;
}

.routine-dialog__input-with-unit--without-unit { grid-template-columns: minmax(0, 1fr); }

.routine-dialog__pressure {
	grid-template-columns: repeat(2, minmax(0, 1fr)) max-content;
}

.routine-dialog__unit {
	padding-bottom: calc((var(--default-clickable-area) - 1em) / 2);
	white-space: nowrap;
}

.routine-dialog__event-options :deep([class*="ncFormBox_row"]) {
	display: flex;
	flex-wrap: wrap;
}

.routine-dialog__event-options :deep([class*="ncFormBox_row"] > *) {
	flex: 1 1 11rem;
	min-width: min(100%, 11rem);
}

@media (max-width: 480px) {
	.routine-dialog__pressure {
		grid-template-columns: minmax(0, 1fr) max-content;
	}

	.routine-dialog__pressure :deep(.input-field:nth-child(2)) {
		grid-column: 1;
	}

	.routine-dialog__pressure .routine-dialog__unit {
		grid-column: 2;
		grid-row: 1 / span 2;
	}
}
</style>
