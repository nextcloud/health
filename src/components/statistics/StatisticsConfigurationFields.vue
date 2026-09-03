<script setup lang="ts">
import type { HealthConfiguration } from '../../api/configuration.ts'
import type { StatisticsPeriod } from '../../api/statistics.ts'
import type { AllMetricKey } from '../../metrics.ts'

import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import MetricIcon from '../MetricIcon.vue'
import { getEnabledMetricKeys } from '../../api/configuration.ts'
import { ALL_METRIC_KEYS, getMetricLabel, METRIC_KEYS } from '../../metrics.ts'
import { getStatisticsPeriodOptions } from '../../statistics.ts'

interface MetricOption {
	kind: 'metric'
	id: AllMetricKey
	label: string
}

interface MetricGroupOption {
	kind: 'group'
	id: string
	label: string
}

type StatisticsOption = MetricOption | MetricGroupOption

const props = withDefaults(defineProps<{
	configuration: HealthConfiguration | null
	period: StatisticsPeriod
	metricKeys: AllMetricKey[]
	idPrefix?: string
	readonly?: boolean
	layout?: 'balanced' | 'saved-view'
}>(), {
	idPrefix: 'statistics',
	readonly: false,
	layout: 'balanced',
})

const emit = defineEmits<{
	'update:period': [period: StatisticsPeriod]
	'update:metricKeys': [metricKeys: AllMetricKey[]]
}>()

const periodOptions = computed(() => getStatisticsPeriodOptions())
const selectedPeriod = computed({
	get: () => periodOptions.value.find((option) => option.id === props.period) ?? periodOptions.value[0]!,
	set: (option: { id: StatisticsPeriod }) => emit('update:period', option.id),
})
const selectedMetrics = computed({
	get: (): MetricOption[] => props.metricKeys.map((metricKey): MetricOption => ({ kind: 'metric', id: metricKey, label: getMetricLabel(metricKey) })),
	set: (options: MetricOption[]) => emit('update:metricKeys', options.map((option) => option.id)),
})
const metricOptions = computed<StatisticsOption[]>(() => [
	...metricOptionsFor(METRIC_KEYS, t('health', 'Journal Metrics')),
	...metricOptionsFor(['temperature', 'oxygen_saturation', 'blood_glucose', 'pulse', 'blood_pressure'], t('health', 'Measurements')),
	...metricOptionsFor(['weight', 'body_fat', 'waist', 'hip', 'muscle_percentage', 'sins', 'steps', 'job_satisfaction'], t('health', 'Daily Values')),
])
const selectedPeriodLabel = computed(() => selectedPeriod.value.label)

function metricOptionsFor(metricKeys: readonly AllMetricKey[], heading: string): StatisticsOption[] {
	const enabledMetricKeys = new Set(getEnabledMetricKeys(props.configuration, ALL_METRIC_KEYS))
	const selectedKeys = new Set(props.metricKeys)
	const options = metricKeys
		.filter((metricKey) => enabledMetricKeys.has(metricKey) && !selectedKeys.has(metricKey))
		.map((metricKey): MetricOption => ({ kind: 'metric', id: metricKey, label: getMetricLabel(metricKey) }))

	return options.length === 0
		? []
		: [{ kind: 'group', id: `group-${heading}`, label: heading }, ...options]
}

function isSelectableMetricOption(option: StatisticsOption): boolean {
	return option.kind === 'metric'
}
</script>

<template>
	<div v-if="readonly" class="statistics-configuration-fields statistics-configuration-fields--readonly" :class="`statistics-configuration-fields--${layout}`">
		<div class="statistics-configuration-fields__read-value">
			<strong class="statistics-configuration-fields__label">{{ t('health', 'Period') }}</strong>
			<span>{{ selectedPeriodLabel }}</span>
		</div>
		<div class="statistics-configuration-fields__read-value">
			<strong class="statistics-configuration-fields__label">{{ t('health', 'Metrics') }}</strong>
			<span class="statistics-configuration-fields__metric-list">
				<span v-for="metricKey in metricKeys" :key="metricKey" class="statistics-configuration-fields__metric-option">
					<MetricIcon :metric-key="metricKey" />
					{{ getMetricLabel(metricKey) }}
				</span>
			</span>
		</div>
	</div>
	<div v-else class="statistics-configuration-fields" :class="`statistics-configuration-fields--${layout}`">
		<div class="statistics-configuration-fields__filter">
			<label class="statistics-configuration-fields__label" :for="`${idPrefix}-period`">{{ t('health', 'Period') }}</label>
			<NcSelect
				v-model="selectedPeriod"
				:clearable="false"
				:options="periodOptions"
				:searchable="false"
				:input-id="`${idPrefix}-period`"
				label-outside
				label="label" />
		</div>
		<div class="statistics-configuration-fields__filter statistics-configuration-fields__filter--metrics">
			<label class="statistics-configuration-fields__label" :for="`${idPrefix}-metrics`">{{ t('health', 'Metrics') }}</label>
			<NcSelect
				v-model="selectedMetrics"
				:clearable="true"
				:options="metricOptions"
				:searchable="true"
				:input-id="`${idPrefix}-metrics`"
				label-outside
				keep-open
				label="label"
				multiple
				:no-wrap="layout !== 'saved-view'"
				:selectable="isSelectableMetricOption">
				<template #option="option">
					<span v-if="option.kind === 'group'" class="statistics-configuration-fields__metric-group">
						{{ option.label }}
					</span>
					<span v-else class="statistics-configuration-fields__metric-option">
						<MetricIcon :metric-key="option.id" />
						{{ option.label }}
					</span>
				</template>
				<template #selected-option="option">
					<span class="statistics-configuration-fields__metric-option statistics-configuration-fields__metric-option--selected">
						<MetricIcon :metric-key="option.id" />
						{{ option.label }}
					</span>
				</template>
			</NcSelect>
		</div>
	</div>
</template>

<style scoped>
.statistics-configuration-fields {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	min-width: 0;
	gap: 16px;
}

.statistics-configuration-fields--readonly {
	grid-template-columns: repeat(2, minmax(0, 1fr));
}

.statistics-configuration-fields--saved-view {
	grid-template-columns: minmax(0, 1fr);
}

.statistics-configuration-fields--saved-view :deep(.v-select.select .vs__dropdown-toggle) {
	max-height: none;
	overflow-y: visible;
}

.statistics-configuration-fields__filter,
.statistics-configuration-fields__read-value {
	display: grid;
	min-width: 0;
	gap: 4px;
}

.statistics-configuration-fields__label {
	font-weight: var(--font-weight-bold);
}

.statistics-configuration-fields__metric-list {
	display: flex;
	flex-wrap: wrap;
	gap: 4px 12px;
}

.statistics-configuration-fields__metric-option {
	display: inline-flex;
	align-items: center;
	min-width: 0;
	gap: calc(2 * var(--default-grid-baseline));
}

.statistics-configuration-fields__metric-option--selected {
	gap: 4px;
}

.statistics-configuration-fields__metric-option--selected :deep(.metric-icon) {
	--health-metric-icon-box-size: 18px;
	--health-metric-icon-svg-size: 16px;
}

.statistics-configuration-fields__metric-group {
	display: block;
	padding-block: var(--default-grid-baseline);
	color: var(--color-text-maxcontrast);
	font-size: var(--font-size-small);
	font-weight: var(--font-weight-bold);
}

@media (max-width: 760px) {
	.statistics-configuration-fields,
	.statistics-configuration-fields--readonly {
		grid-template-columns: minmax(0, 1fr);
	}
}
</style>
