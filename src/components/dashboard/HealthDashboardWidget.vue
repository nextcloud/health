<script setup lang="ts">
import type { DailyValue } from '../../api/dailyValues.ts'
import type { Entry } from '../../api/entries.ts'
import type { Measurement } from '../../api/measurements.ts'
import type { RoutineResult } from '../../api/routines.ts'
import type { AllMetricKey, DailyValueMetricKey, EventOption, Unit } from '../../metrics.ts'

import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import NcProgressBar from '@nextcloud/vue/components/NcProgressBar'
import BreakAction from '../BreakAction.vue'
import CoffeeAction from '../CoffeeAction.vue'
import EventSymbol from '../EventSymbol.vue'
import MetricIcon from '../MetricIcon.vue'
import RoutineDialog from '../RoutineDialog.vue'
import { getConfiguration, getEnabledMetricKeys, isMetricEnabled } from '../../api/configuration.ts'
import { listDailyValues } from '../../api/dailyValues.ts'
import { createEntry, listAllEntries } from '../../api/entries.ts'
import { listMeasurements } from '../../api/measurements.ts'
import { iconPaths } from '../../icons.ts'
import {
	DAILY_VALUE_METRIC_KEYS,
	fromCanonical,
	getMetricLabel,
	getMetricUnits,
	getMetricVisual,
	getOptionLabel,
	getUnitLabel,
	hasDisplayUnit,
	isScaleMetric,
	MEASUREMENT_METRIC_KEYS,
	METRIC_KEYS,
} from '../../metrics.ts'
import { getLocalDayRange, localDateKey } from '../../utils/dates.ts'

const todayDate = new Date()
const today = localDateKey(todayDate)
const configuration = ref<Awaited<ReturnType<typeof getConfiguration>> | null>(null)
const entries = ref<Entry[]>([])
const measurements = ref<Measurement[]>([])
const dailyValues = ref<DailyValue[]>([])
const initialLoading = ref(true)
const loadFailed = ref(false)
const savingActions = ref(0)
const checkInOpen = ref(false)
const checkOutOpen = ref(false)

const journalSummary = computed(() => getEnabledMetricKeys(configuration.value, METRIC_KEYS).flatMap((metricKey) => {
	const values = entries.value.filter((entry) => entry.metricKey === metricKey)
	if (values.length === 0) {
		return []
	}

	const average = isScaleMetric(metricKey)
		? values.reduce((sum, entry) => sum + (entry.numericValue ?? 0), 0) / values.length
		: null
	return [{ metricKey, count: values.length, average }]
}))

const measurementSummary = computed(() => getEnabledMetricKeys(configuration.value, MEASUREMENT_METRIC_KEYS).flatMap((metricKey) => {
	const latest = measurements.value
		.filter((measurement) => measurement.metricKey === metricKey)
		.sort((left, right) => new Date(right.recordedAt).getTime() - new Date(left.recordedAt).getTime())[0]
	return latest === undefined ? [] : [{ metricKey, measurement: latest }]
}))

const dailyValueSummary = computed(() => getEnabledMetricKeys(configuration.value, DAILY_VALUE_METRIC_KEYS).flatMap((metricKey) => {
	const value = dailyValues.value.find((current) => current.metricKey === metricKey)
	return value === undefined ? [] : [{ metricKey, value }]
}))

function unit(metricKey: AllMetricKey): Unit {
	return configuration.value?.metrics[metricKey]?.displayUnit ?? getMetricUnits(metricKey)[0]
}

function formatNumber(value: number): string {
	return value.toLocaleString(undefined, { maximumFractionDigits: 2 })
}

function displayMeasurement(measurement: Measurement): string {
	const displayUnit = unit(measurement.metricKey)
	if (measurement.values !== null) {
		return `${formatNumber(fromCanonical('blood_pressure', measurement.values.systolic, displayUnit))} / ${formatNumber(fromCanonical('blood_pressure', measurement.values.diastolic, displayUnit))} ${getUnitLabel(displayUnit)}`
	}
	return `${formatNumber(fromCanonical(measurement.metricKey, measurement.numericValue ?? 0, displayUnit))} ${getUnitLabel(displayUnit)}`
}

function displayDailyValue(metricKey: DailyValueMetricKey, value: DailyValue): string {
	const displayUnit = unit(metricKey)
	const formattedValue = formatNumber(fromCanonical(metricKey, value.numericValue, displayUnit))
	return hasDisplayUnit(metricKey) ? `${formattedValue} ${getUnitLabel(displayUnit)}` : formattedValue
}

function mergeRoutine(result: RoutineResult) {
	entries.value = [...result.createdEntries, ...entries.value]
	measurements.value = [...result.createdMeasurements, ...measurements.value]
	for (const updated of result.updatedDailyValues) {
		dailyValues.value = [...dailyValues.value.filter((value) => value.metricKey !== updated.metricKey), updated]
	}
}

function routineSaved(result: RoutineResult, context: 'check-in' | 'check-out') {
	mergeRoutine(result)
	showSuccess(context === 'check-in' ? t('health', 'Check-in saved.') : t('health', 'Check-out saved.'))
}

async function recordEvent(metricKey: 'hydration' | 'break', optionValue: EventOption) {
	savingActions.value++
	try {
		const entry = await createEntry({
			metricKey,
			numericValue: null,
			optionValue,
			context: 'manual',
			source: 'web',
			recordedAt: new Date().toISOString(),
			note: null,
		})
		entries.value = [entry, ...entries.value]
		showSuccess(t('health', '{entry} recorded.', { entry: getOptionLabel(metricKey, optionValue) }))
	} catch {
		showError(t('health', 'Entry could not be recorded.'))
	} finally {
		savingActions.value--
	}
}

async function load() {
	initialLoading.value = true
	loadFailed.value = false
	try {
		const range = getLocalDayRange(todayDate)
		const [loadedConfiguration, loadedEntries, loadedDailyValues, loadedMeasurements] = await Promise.all([
			getConfiguration(),
			listAllEntries({ ...range, limit: 200 }),
			listDailyValues(today),
			listMeasurements(range.from, range.to),
		])
		configuration.value = loadedConfiguration
		entries.value = loadedEntries
		dailyValues.value = loadedDailyValues
		measurements.value = loadedMeasurements
	} catch {
		loadFailed.value = true
		showError(t('health', 'Health summary could not be loaded.'))
	} finally {
		initialLoading.value = false
	}
}

void load()
</script>

<template>
	<section :aria-busy="savingActions > 0" class="health-dashboard">
		<div :aria-label="t('health', 'Health actions')" class="health-dashboard__actions" role="group">
			<NcButton :aria-label="t('health', 'Check-in')"
				:title="t('health', 'Check-in')"
				variant="primary"
				@click="checkInOpen = true">
				<template #icon>
					<EventSymbol symbol="🌅" size="button" />
				</template>
			</NcButton>
			<NcButton :aria-label="t('health', 'Check-out')"
				:title="t('health', 'Check-out')"
				variant="primary"
				@click="checkOutOpen = true">
				<template #icon>
					<EventSymbol symbol="🌃" size="button" />
				</template>
			</NcButton>
			<NcButton v-if="isMetricEnabled(configuration, 'hydration')"
				:aria-label="t('health', 'Record small glass of water')"
				:disabled="savingActions > 0"
				:title="t('health', 'Record water')"
				variant="secondary"
				@click="recordEvent('hydration', 'small_glass')">
				<template #icon>
					<EventSymbol symbol="🥛" size="button" />
				</template>
			</NcButton>
			<CoffeeAction v-if="isMetricEnabled(configuration, 'hydration')" :disabled="savingActions > 0" @select="recordEvent('hydration', $event)" />
			<BreakAction v-if="isMetricEnabled(configuration, 'break')" :disabled="savingActions > 0" @select="recordEvent('break', $event)" />
		</div>

		<NcLoadingIcon v-if="initialLoading" :name="t('health', 'Loading Health summary')" :size="28" />
		<p v-else-if="loadFailed" class="health-dashboard__message">
			{{ t('health', 'The summary is temporarily unavailable.') }}
		</p>
		<div v-else class="health-dashboard__summary">
			<div v-for="item in journalSummary" :key="item.metricKey" class="health-dashboard__row">
				<MetricIcon :metric-key="item.metricKey" />
				<span>{{ getMetricLabel(item.metricKey) }}</span>
				<NcProgressBar v-if="item.average !== null"
					:aria-label="t('health', '{metric} average: {value} out of 5', { metric: getMetricLabel(item.metricKey), value: formatNumber(item.average) })"
					:color="getMetricVisual(item.metricKey).color"
					:size="8"
					:value="item.average * 20" />
				<span v-else class="health-dashboard__value">{{ t('health', '{count}×', { count: item.count }) }}</span>
			</div>
			<div v-for="item in measurementSummary" :key="item.metricKey" class="health-dashboard__row">
				<MetricIcon :metric-key="item.metricKey" />
				<span>{{ getMetricLabel(item.metricKey) }}</span>
				<span class="health-dashboard__value">{{ displayMeasurement(item.measurement) }}</span>
			</div>
			<div v-for="item in dailyValueSummary" :key="item.metricKey" class="health-dashboard__row">
				<MetricIcon :metric-key="item.metricKey" />
				<span>{{ getMetricLabel(item.metricKey) }}</span>
				<span class="health-dashboard__value">{{ displayDailyValue(item.metricKey, item.value) }}</span>
			</div>
			<div v-for="item in dailyValueSummary.filter((item) => item.value.bmi !== null)" :key="`${item.metricKey}-bmi`" class="health-dashboard__row">
				<MetricIcon metric-key="weight" />
				<span>{{ t('health', 'BMI') }}</span>
				<span class="health-dashboard__bmi-value health-dashboard__value">
					<span>{{ formatNumber(item.value.bmi ?? 0) }}</span>
					<NcPopover popup-role="dialog">
						<template #trigger>
							<NcButton :aria-label="t('health', 'About the BMI calculation')"
								:title="t('health', 'About the BMI calculation')"
								variant="tertiary">
								<template #icon>
									<NcIconSvgWrapper :path="iconPaths.information" />
								</template>
							</NcButton>
						</template>
						<p>{{ t('health', 'Adult BMI is calculated as weight in kilograms divided by height in metres squared. It is shown as a descriptive value only.') }}</p>
					</NcPopover>
				</span>
			</div>
			<p v-if="journalSummary.length + measurementSummary.length + dailyValueSummary.length === 0" class="health-dashboard__message">
				{{ t('health', 'Record a value to see today’s Health summary here.') }}
			</p>
		</div>

		<RoutineDialog v-model:open="checkInOpen"
			context="check-in"
			:configuration="configuration"
			:date="today"
			@saved="routineSaved($event, 'check-in')" />
		<RoutineDialog v-model:open="checkOutOpen"
			context="check-out"
			:configuration="configuration"
			:date="today"
			@saved="routineSaved($event, 'check-out')" />
	</section>
</template>

<style scoped>
.health-dashboard {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.health-dashboard__actions {
	display: flex;
	align-items: center;
	flex-wrap: nowrap;
	gap: 4px;
	min-width: 0;
}

.health-dashboard__actions > * {
	flex: 0 0 auto;
}

.health-dashboard__summary {
	display: flex;
	flex-direction: column;
	gap: 6px;
}

.health-dashboard__row {
	display: grid;
	grid-template-columns: 24px minmax(0, 1fr) minmax(7rem, 40%);
	align-items: center;
	min-height: var(--default-clickable-area);
	column-gap: var(--health-metric-title-gap, calc(3 * var(--default-grid-baseline)));
}

.health-dashboard__value {
	justify-self: end;
	font-variant-numeric: tabular-nums;
	text-align: end;
}

.health-dashboard__bmi-value {
	display: inline-flex;
	align-items: center;
	gap: 2px;
}

.health-dashboard__message {
	margin: 0;
	color: var(--color-text-maxcontrast);
}

@media (max-width: 320px) {
	.health-dashboard__actions { flex-wrap: wrap; }
	.health-dashboard__row { grid-template-columns: 24px minmax(0, 1fr); }
	.health-dashboard__value,
	.health-dashboard__row :deep(.progress-bar) { grid-column: 2; justify-self: stretch; }
}

:global(.icon-health) {
	background-image: url('../../../img/app-dark.svg');
	filter: var(--background-invert-if-dark);
}
</style>
