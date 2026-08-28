<script setup lang="ts">
import type { HealthConfiguration } from '../api/configuration.ts'
import type { StatisticsPeriod, StatisticsResponse } from '../api/statistics.ts'
import type { AllMetricKey } from '../metrics.ts'

import { t } from '@nextcloud/l10n'
import { computed, inject, onMounted, ref, watch } from 'vue'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import MetricIcon from '../components/MetricIcon.vue'
import StatisticsChart from '../components/statistics/StatisticsChart.vue'
import StatisticsSummaryBox from '../components/statistics/StatisticsSummaryBox.vue'
import { getConfiguration, getEnabledMetricKeys } from '../api/configuration.ts'
import { getStatistics } from '../api/statistics.ts'
import { healthConfigurationKey } from '../configurationContext.ts'
import { ALL_METRIC_KEYS, getMetricLabel, METRIC_KEYS } from '../metrics.ts'
import { getStatisticsPeriodOptions } from '../statistics.ts'

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

const configuration = inject(healthConfigurationKey)
const period = ref<StatisticsPeriod>('last_30_days')
const selectedMetrics = ref<MetricOption[]>([])
const response = ref<StatisticsResponse | null>(null)
const loading = ref(false)
const loadError = ref(false)
const defaultsApplied = ref(false)
const currentConfiguration = computed<HealthConfiguration | null>(() => configuration?.value ?? null)
const configurationLoading = ref(currentConfiguration.value === null)
let requestGeneration = 0

const periodOptions = computed(() => getStatisticsPeriodOptions())
const selectedPeriod = computed({
	get: () => periodOptions.value.find((option) => option.id === period.value) ?? periodOptions.value[0]!,
	set: (option: { id: StatisticsPeriod }) => {
		period.value = option.id
	},
})
const selectedMetricKeys = computed(() => selectedMetrics.value.map((option) => option.id))
const metricOptions = computed<StatisticsOption[]>(() => [
	...metricOptionsFor(METRIC_KEYS, t('health', 'Journal Metrics')),
	...metricOptionsFor(['temperature', 'oxygen_saturation', 'blood_glucose', 'pulse', 'blood_pressure'], t('health', 'Measurements')),
	...metricOptionsFor(['weight', 'body_fat', 'waist', 'hip', 'muscle_percentage', 'sins', 'steps', 'job_satisfaction'], t('health', 'Daily Values')),
])
const displayedMetrics = computed(() => response.value?.metrics ?? [])

function metricOptionsFor(metricKeys: readonly AllMetricKey[], heading: string): StatisticsOption[] {
	const enabledMetricKeys = new Set(getEnabledMetricKeys(currentConfiguration.value, ALL_METRIC_KEYS))
	const selectedKeys = new Set(selectedMetricKeys.value)
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

function applyDefaults(configuration: HealthConfiguration): void {
	selectedMetrics.value = getEnabledMetricKeys(configuration, METRIC_KEYS)
		.map((metricKey): MetricOption => ({ kind: 'metric', id: metricKey, label: getMetricLabel(metricKey) }))
	defaultsApplied.value = true
}

async function loadConfiguration(): Promise<void> {
	if (currentConfiguration.value !== null) {
		configurationLoading.value = false
		return
	}

	try {
		if (configuration !== undefined) {
			configuration.value = await getConfiguration()
		}
	} catch {
		loadError.value = true
	} finally {
		configurationLoading.value = false
	}
}

async function loadStatistics(): Promise<void> {
	const metricKeys = selectedMetricKeys.value
	const generation = ++requestGeneration
	if (metricKeys.length === 0) {
		response.value = null
		loading.value = false
		loadError.value = false
		return
	}

	loading.value = true
	loadError.value = false
	try {
		const nextResponse = await getStatistics(period.value, metricKeys)
		if (generation === requestGeneration) {
			response.value = nextResponse
		}
	} catch {
		if (generation === requestGeneration) {
			loadError.value = true
		}
	} finally {
		if (generation === requestGeneration) {
			loading.value = false
		}
	}
}

watch(currentConfiguration, (nextConfiguration) => {
	if (nextConfiguration === null) {
		return
	}

	if (!defaultsApplied.value) {
		applyDefaults(nextConfiguration)
		return
	}

	const availableKeys = new Set(getEnabledMetricKeys(nextConfiguration, ALL_METRIC_KEYS))
	selectedMetrics.value = selectedMetrics.value.filter((option) => availableKeys.has(option.id))
}, { immediate: true })

watch([period, selectedMetricKeys], () => {
	void loadStatistics()
}, { immediate: true })

onMounted(() => {
	void loadConfiguration()
})
</script>

<template>
	<main class="health-statistics">
		<header class="health-statistics__header">
			<h1 class="health-page-title">
				{{ t('health', 'Statistics') }}
			</h1>
			<div class="health-statistics__filters">
				<div class="health-statistics__filter">
					<label class="health-statistics__filter-label" for="statistics-period">{{ t('health', 'Period') }}</label>
					<NcSelect
						v-model="selectedPeriod"
						:clearable="false"
						:options="periodOptions"
						:searchable="false"
						input-id="statistics-period"
						label-outside
						label="label" />
				</div>
				<div class="health-statistics__filter health-statistics__filter--metrics">
					<label class="health-statistics__filter-label" for="statistics-metrics">{{ t('health', 'Metrics') }}</label>
					<NcSelect
						v-model="selectedMetrics"
						:clearable="true"
						:options="metricOptions"
						:searchable="true"
						input-id="statistics-metrics"
						label-outside
						keep-open
						label="label"
						multiple
						no-wrap
						:selectable="isSelectableMetricOption">
						<template #option="option">
							<span v-if="option.kind === 'group'" class="health-statistics__metric-group">
								{{ option.label }}
							</span>
							<span v-else class="health-statistics__metric-option">
								<MetricIcon :metric-key="option.id" />
								{{ option.label }}
							</span>
						</template>
						<template #selected-option="option">
							<span class="health-statistics__metric-option health-statistics__metric-option--selected">
								<MetricIcon :metric-key="option.id" />
								{{ option.label }}
							</span>
						</template>
					</NcSelect>
				</div>
			</div>
		</header>

		<NcNoteCard
			v-if="loadError"
			type="error"
			:text="t('health', 'Statistics could not be loaded.')" />

		<div v-if="configurationLoading || (loading && response === null)" class="health-statistics__initial-loading">
			<NcLoadingIcon :name="t('health', 'Loading statistics')" />
		</div>

		<NcEmptyContent
			v-else-if="selectedMetricKeys.length === 0"
			:description="t('health', 'Choose one or more numeric metrics to display statistics.')"
			:name="t('health', 'No metrics selected.')" />

		<template v-else-if="response !== null">
			<p v-if="loading" class="health-statistics__updating" role="status">
				{{ t('health', 'Updating statistics…') }}
			</p>
			<div class="health-statistics__charts">
				<StatisticsChart
					:configuration="currentConfiguration"
					:metrics="displayedMetrics" />
			</div>

			<section aria-labelledby="statistics-summary-heading" class="health-statistics__summary">
				<h2 id="statistics-summary-heading" class="health-statistics__summary-heading">
					{{ t('health', 'Average for the selected period') }}
				</h2>
				<div class="health-statistics__summary-grid">
					<StatisticsSummaryBox
						v-for="metric in displayedMetrics"
						:key="metric.metricKey"
						:configuration="currentConfiguration"
						:metric="metric" />
				</div>
			</section>
		</template>
	</main>
</template>

<style scoped>
.health-statistics {
	display: flex;
	flex-direction: column;
	width: min(100%, 1200px);
	margin: 0 auto;
	padding: 24px 24px 300px;
	gap: 24px;
}

.health-statistics__header {
	display: grid;
	gap: 16px;
}

.health-statistics__filters {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	min-width: 0;
	gap: 16px;
}

.health-statistics__filter {
	display: grid;
	min-width: 0;
	gap: 4px;
}

.health-statistics__filter-label {
	font-weight: var(--font-weight-bold);
}

.health-statistics__metric-option {
	display: inline-flex;
	align-items: center;
	min-width: 0;
	gap: calc(2 * var(--default-grid-baseline));
}

.health-statistics__metric-option--selected {
	gap: 4px;
}

.health-statistics__metric-option--selected :deep(.metric-icon) {
	--health-metric-icon-box-size: 18px;
	--health-metric-icon-svg-size: 16px;
}

.health-statistics__metric-group {
	display: block;
	padding-block: var(--default-grid-baseline);
	color: var(--color-text-maxcontrast);
	font-size: var(--font-size-small);
	font-weight: var(--font-weight-bold);
}

.health-statistics__initial-loading {
	display: flex;
	justify-content: center;
	padding: 48px;
}

.health-statistics__updating {
	margin: 0;
	color: var(--color-text-maxcontrast);
	font-size: var(--font-size-small);
}

.health-statistics__charts {
	display: grid;
	min-width: 0;
	gap: 24px;
}

.health-statistics__summary {
	display: grid;
	margin-top: calc(2 * var(--default-grid-baseline));
	gap: 16px;
}

.health-statistics__summary-heading {
	margin: 0;
	font-size: 1.25rem;
	font-weight: var(--font-weight-bold);
}

.health-statistics__summary-grid {
	display: flex;
	flex-wrap: wrap;
	gap: calc(3 * var(--default-grid-baseline));
}

@media (max-width: 760px) {
	.health-statistics__filters {
		grid-template-columns: minmax(0, 1fr);
	}
}

@media (max-width: 600px) {
	.health-statistics {
		padding: 16px 16px 300px;
	}
}
</style>
