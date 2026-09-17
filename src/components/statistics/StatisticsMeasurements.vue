<script setup lang="ts">
import type { HealthConfiguration } from '../../api/configuration.ts'
import type { StatisticsSourceRecord } from '../../api/statistics.ts'
import type { AllMetricKey, Unit } from '../../metrics.ts'

import { getCanonicalLocale, t } from '@nextcloud/l10n'
import { computed } from 'vue'
import MetricIcon from '../MetricIcon.vue'
import { fromCanonical, getMetricLabel, getMetricUnits, getOptionLabel, getUnitLabel } from '../../metrics.ts'
import { displayUnitForMetric } from '../../statistics.ts'

const props = defineProps<{
	configuration: HealthConfiguration | null
	records: StatisticsSourceRecord[]
}>()

interface DayGroup {
	date: string
	records: StatisticsSourceRecord[]
}

const dateFormatter = new Intl.DateTimeFormat(getCanonicalLocale(), { dateStyle: 'full' })
const timeFormatter = new Intl.DateTimeFormat(getCanonicalLocale(), { hour: '2-digit', minute: '2-digit' })
const groups = computed<DayGroup[]>(() => {
	const byDate = new Map<string, StatisticsSourceRecord[]>()
	for (const record of props.records) {
		const records = byDate.get(record.date) ?? []
		records.push(record)
		byDate.set(record.date, records)
	}
	return [...byDate.entries()].map(([date, records]) => ({ date, records }))
})

function unit(metricKey: AllMetricKey): Unit | null {
	return displayUnitForMetric(props.configuration, metricKey) ?? getMetricUnits(metricKey)[0] ?? null
}

function formatNumber(value: number): string {
	return value.toLocaleString(getCanonicalLocale(), { maximumFractionDigits: 2 })
}

function formatValue(record: StatisticsSourceRecord): string {
	if (record.optionValue !== null) {
		return getOptionLabel(record.metricKey, record.optionValue)
	}
	if (record.values !== null) {
		const displayUnit = unit(record.metricKey)
		if (displayUnit === null) {
			return `${formatNumber(record.values.systolic)} / ${formatNumber(record.values.diastolic)}`
		}
		return `${formatNumber(fromCanonical(record.metricKey, record.values.systolic, displayUnit))} / ${formatNumber(fromCanonical(record.metricKey, record.values.diastolic, displayUnit))} ${getUnitLabel(displayUnit)}`
	}
	if (record.numericValue === null) {
		return '—'
	}
	const displayUnit = unit(record.metricKey)
	const value = displayUnit === null ? record.numericValue : fromCanonical(record.metricKey, record.numericValue, displayUnit)
	return displayUnit === null ? formatNumber(value) : `${formatNumber(value)} ${getUnitLabel(displayUnit)}`
}
</script>

<template>
	<section aria-labelledby="statistics-measurements-heading" class="statistics-measurements">
		<h2 id="statistics-measurements-heading" class="statistics-measurements__heading">
			{{ t('health', 'Measurements') }}
		</h2>
		<p v-if="groups.length === 0" class="statistics-measurements__empty">
			{{ t('health', 'No measurements in this period.') }}
		</p>
		<div v-else class="statistics-measurements__groups">
			<section v-for="group in groups"
				:key="group.date"
				:aria-labelledby="`statistics-measurements-${group.date}`"
				class="statistics-measurements__group">
				<h3 :id="`statistics-measurements-${group.date}`" class="statistics-measurements__date">
					{{ dateFormatter.format(new Date(`${group.date}T12:00:00`)) }}
				</h3>
				<ul class="statistics-measurements__list">
					<li v-for="record in group.records" :key="`${record.date}-${record.recordedAt ?? 'daily'}-${record.metricKey}-${record.numericValue}-${record.optionValue}`" class="statistics-measurements__item">
						<MetricIcon :metric-key="record.metricKey" />
						<div class="statistics-measurements__content">
							<strong>{{ getMetricLabel(record.metricKey) }}</strong>
							<span class="statistics-measurements__value">{{ formatValue(record) }}</span>
							<time v-if="record.recordedAt !== null" :datetime="record.recordedAt">{{ timeFormatter.format(new Date(record.recordedAt)) }}</time>
						</div>
					</li>
				</ul>
			</section>
		</div>
	</section>
</template>

<style scoped>
.statistics-measurements,
.statistics-measurements__groups,
.statistics-measurements__group { display: grid; gap: 16px; }
.statistics-measurements { margin-top: calc(2 * var(--default-grid-baseline)); }
.statistics-measurements__heading, .statistics-measurements__date { margin: 0; }
.statistics-measurements__heading { font-size: 1.25rem; font-weight: var(--font-weight-bold); }
.statistics-measurements__date { font-size: var(--default-font-size); }
.statistics-measurements__empty { margin: 0; color: var(--color-text-maxcontrast); }
.statistics-measurements__list { margin: 0; padding: 0; list-style: none; }
.statistics-measurements__item { display: grid; grid-template-columns: var(--default-clickable-area) minmax(0, 1fr); align-items: start; min-height: var(--default-clickable-area); padding: 10px 0; border-bottom: 1px solid var(--health-journal-separator, var(--color-border-dark)); gap: 8px; }
.statistics-measurements__item:last-child { border-bottom: 0; }
.statistics-measurements__content { display: grid; grid-template-columns: max-content minmax(0, 1fr) max-content; align-items: baseline; min-width: 0; gap: 12px; }
.statistics-measurements__content strong, .statistics-measurements__value { overflow-wrap: anywhere; }
.statistics-measurements__content time { flex: 0 0 auto; color: var(--color-text-maxcontrast); font-variant-numeric: tabular-nums; }
.statistics-measurements__value { font-variant-numeric: tabular-nums; }
@media (max-width: 360px) {
	.statistics-measurements__content { grid-template-columns: minmax(0, max-content) minmax(0, 1fr); }
	.statistics-measurements__content time { grid-column: 2; }
}
</style>
