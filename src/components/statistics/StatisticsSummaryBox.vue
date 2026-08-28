<script setup lang="ts">
import type { HealthConfiguration } from '../../api/configuration.ts'
import type { StatisticsMetric } from '../../api/statistics.ts'

import { n, t } from '@nextcloud/l10n'
import { computed } from 'vue'
import MetricIcon from '../MetricIcon.vue'
import MetricValueCard from '../MetricValueCard.vue'
import { fromCanonical, getMetricLabel, getUnitLabel } from '../../metrics.ts'
import { displayUnitForMetric } from '../../statistics.ts'

const props = defineProps<{
	metric: StatisticsMetric
	configuration: HealthConfiguration | null
}>()

const isEventMetric = computed(() => props.metric.valueType === 'event')
const isBloodPressure = computed(() => props.metric.metricKey === 'blood_pressure')
const unit = computed(() => displayUnitForMetric(props.configuration, props.metric.metricKey))
const bloodPressureSummaries = computed(() => ({
	systolic: props.metric.summary.subseries?.systolic,
	diastolic: props.metric.summary.subseries?.diastolic,
}))
const hasData = computed(() => isBloodPressure.value
	? (bloodPressureSummaries.value.systolic?.average ?? null) !== null || (bloodPressureSummaries.value.diastolic?.average ?? null) !== null
	: props.metric.summary.average !== null)
const mainValue = computed(() => formatValue(props.metric.summary.average))
const minimum = computed(() => formatValue(props.metric.summary.minimum))
const maximum = computed(() => formatValue(props.metric.summary.maximum))
const bloodPressureValue = computed(() => `${formatValue(bloodPressureSummaries.value.systolic?.average ?? null)} / ${formatValue(bloodPressureSummaries.value.diastolic?.average ?? null)}`)
const recordCount = computed(() => {
	const count = props.metric.summary.count
	if (props.metric.valueType === 'event') {
		return n('health', '{count} event', '{count} events', count, { count })
	}
	if (props.metric.category === 'measurement') {
		return n('health', '{count} measurement', '{count} measurements', count, { count })
	}
	if (props.metric.category === 'daily_value') {
		return n('health', '{count} value', '{count} values', count, { count })
	}
	return n('health', '{count} entry', '{count} entries', count, { count })
})
function formatValue(value: number | null): string {
	if (value === null) {
		return '—'
	}

	const displayValue = unit.value === null
		? value
		: fromCanonical(props.metric.metricKey, value, unit.value)
	return displayValue.toLocaleString(undefined, { maximumFractionDigits: 2 })
}
</script>

<template>
	<MetricValueCard class="statistics-summary-box">
		<span class="statistics-summary-box__heading">
			<MetricIcon :metric-key="metric.metricKey" />
			<strong class="statistics-summary-box__title">{{ getMetricLabel(metric.metricKey) }}</strong>
		</span>
		<template v-if="hasData && isBloodPressure">
			<span class="statistics-summary-box__value">{{ bloodPressureValue }}</span>
			<span v-if="unit" class="statistics-summary-box__unit">{{ getUnitLabel(unit) }}</span>
			<dl class="statistics-summary-box__details statistics-summary-box__details--blood-pressure">
				<div>
					<dt>{{ t('health', 'Systolic') }}</dt>
					<dd>{{ t('health', 'min {minimum} · max {maximum}', { minimum: formatValue(bloodPressureSummaries.systolic?.minimum ?? null), maximum: formatValue(bloodPressureSummaries.systolic?.maximum ?? null) }) }}</dd>
				</div>
				<div>
					<dt>{{ t('health', 'Diastolic') }}</dt>
					<dd>{{ t('health', 'min {minimum} · max {maximum}', { minimum: formatValue(bloodPressureSummaries.diastolic?.minimum ?? null), maximum: formatValue(bloodPressureSummaries.diastolic?.maximum ?? null) }) }}</dd>
				</div>
			</dl>
			<span class="statistics-summary-box__records">{{ recordCount }}</span>
		</template>
		<template v-else-if="hasData">
			<span class="statistics-summary-box__value">{{ mainValue }}</span>
			<span v-if="isEventMetric" class="statistics-summary-box__unit">{{ t('health', 'per day') }}</span>
			<span v-else-if="unit" class="statistics-summary-box__unit">{{ getUnitLabel(unit) }}</span>
			<dl class="statistics-summary-box__details">
				<div>
					<dt>{{ t('health', 'min') }}</dt>
					<dd>{{ minimum }}</dd>
				</div>
				<div>
					<dt>{{ t('health', 'max') }}</dt>
					<dd>{{ maximum }}</dd>
				</div>
			</dl>
			<span class="statistics-summary-box__records">{{ recordCount }}</span>
		</template>
		<span v-else class="statistics-summary-box__empty">
			<span>—</span>
			{{ t('health', 'No data in this period') }}
		</span>
	</MetricValueCard>
</template>

<style scoped>
.statistics-summary-box {
	justify-content: center;
	min-height: 12rem;
	text-align: center;
	gap: var(--default-grid-baseline);
}

.statistics-summary-box__heading {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	max-width: 100%;
	gap: var(--health-metric-title-gap, calc(3 * var(--default-grid-baseline)));
}

.statistics-summary-box__title {
	min-width: 0;
	overflow-wrap: anywhere;
}

.statistics-summary-box__value {
	margin-top: var(--default-grid-baseline);
	font-size: 1.75rem;
	font-weight: var(--font-weight-heading, bold);
	font-variant-numeric: tabular-nums;
	line-height: 1.15;
}

.statistics-summary-box__unit,
.statistics-summary-box__records,
.statistics-summary-box__empty {
	color: var(--color-text-maxcontrast);
}

.statistics-summary-box__details {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	width: 100%;
	margin: 0;
	gap: calc(var(--default-grid-baseline) / 2);
	font-size: var(--font-size-small);
}

.statistics-summary-box__details div {
	display: flex;
	justify-content: flex-start;
	min-width: 0;
	white-space: nowrap;
	gap: 4px;
}

.statistics-summary-box__details div:last-child {
	justify-content: flex-end;
}

.statistics-summary-box__details dt,
.statistics-summary-box__details dd {
	margin: 0;
}

.statistics-summary-box__details dt {
	color: var(--color-text-maxcontrast);
}

.statistics-summary-box__details--blood-pressure {
	display: flex;
	width: auto;
	flex-direction: column;
	align-items: center;
	gap: 2px;
}

.statistics-summary-box__details--blood-pressure div {
	justify-content: center;
	white-space: normal;
}

.statistics-summary-box__empty {
	display: flex;
	flex-direction: column;
	margin-top: var(--default-grid-baseline);
	gap: var(--default-grid-baseline);
}

.statistics-summary-box__empty span {
	color: var(--color-main-text);
	font-size: 1.75rem;
	font-weight: var(--font-weight-heading, bold);
	line-height: 1.15;
}
</style>
