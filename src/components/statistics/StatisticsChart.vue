<script setup lang="ts">
import type { Chart, ChartConfiguration, TooltipItem } from 'chart.js'
import type { HealthConfiguration } from '../../api/configuration.ts'
import type { StatisticsGoalOverlay, StatisticsMetric } from '../../api/statistics.ts'
import type { AllMetricKey, EventMetricKey } from '../../metrics.ts'

import { getLocale, t } from '@nextcloud/l10n'
import { BarController, BarElement, CategoryScale, Chart as ChartJs, LinearScale, LineController, LineElement, PointElement, Tooltip } from 'chart.js'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { colorWithAlpha, fromCanonical, getChartableMetricDefinition, getEventChartSeries, getEventChartSeriesLabel, getMetricLabel, getMetricVisual, getUnitLabel } from '../../metrics.ts'
import { displayUnitForMetric, getStatisticsGoalLabel } from '../../statistics.ts'

const props = defineProps<{
	metrics: StatisticsMetric[]
	configuration: HealthConfiguration | null
}>()

ChartJs.register(BarController, BarElement, CategoryScale, LineController, LineElement, LinearScale, PointElement, Tooltip)

interface PlotDataset {
	label: string
	metricKey: AllMetricKey
	data: Array<number | null>
	color: string
	type: 'line' | 'bar'
	goal: boolean
	bar: boolean
	displayValues?: boolean
	pointStyle?: 'circle' | 'rectRot'
	stack?: string
	axisId: string
}

interface ChartAxis {
	id: string
	label: string
	stacked: boolean
	beginAtZero: boolean
	minimum?: number
	maximum?: number
}

interface LegendItem {
	key: string
	label: string
	color: string
	dashed: boolean
	bar: boolean
	pointStyle?: 'circle' | 'rectRot'
}

const canvas = ref<HTMLCanvasElement | null>(null)
const labels = computed(() => props.metrics[0]?.series.map((point) => point.date) ?? [])
const title = computed(() => t('health', 'Statistics'))
const chartDatasets = computed(() => buildDatasets())
const chartAxes = computed(() => buildAxes())
const legendItems = computed<LegendItem[]>(() => chartDatasets.value.map((dataset, index) => ({
	key: `${index}-${dataset.label}`,
	label: dataset.label,
	color: dataset.color,
	dashed: dataset.goal,
	bar: dataset.bar,
	pointStyle: dataset.pointStyle,
})))
const hasData = computed(() => props.metrics.some((metric) => metric.valueType === 'event' || metric.summary.count > 0))
const canvasLabel = computed(() => t('health', '{title} chart. Use the arrow keys to hear values by date. The visible legend identifies each series.', { title: title.value }))

let chart: Chart | null = null
let themeObserver: MutationObserver | null = null
let focusedIndex = -1
const focusedDescription = ref('')

function formatDate(dateKey: string, full = false): string {
	return new Intl.DateTimeFormat(getLocale(), full
		? { weekday: 'long', month: 'long', day: 'numeric' }
		: { month: 'short', day: 'numeric' }).format(new Date(`${dateKey}T12:00:00`))
}

function axisIdForMetric(metricKey: AllMetricKey): string {
	const definition = getChartableMetricDefinition(metricKey)
	if (definition.chartType === 'stacked-bar') {
		return 'event-count'
	}

	const unit = displayUnitForMetric(props.configuration, metricKey)
	if (definition.compatibilityGroup === 'scale_1_5') {
		return 'scale-1-5'
	}
	if (definition.compatibilityGroup === 'length') {
		return `length-${unit ?? 'canonical'}`
	}
	if (definition.compatibilityGroup === 'blood_pressure') {
		return `blood-pressure-${unit ?? 'canonical'}`
	}
	return `metric-${metricKey}`
}

function buildAxes(): ChartAxis[] {
	const axes = new Map<string, ChartAxis>()
	for (const metric of props.metrics) {
		const id = axisIdForMetric(metric.metricKey)
		if (axes.has(id)) {
			continue
		}

		const unit = displayUnitForMetric(props.configuration, metric.metricKey)
		if (id === 'event-count') {
			axes.set(id, { id, label: t('health', 'Events per day'), stacked: true, beginAtZero: true })
		} else if (id === 'scale-1-5') {
			axes.set(id, { id, label: t('health', 'Scale 1–5'), stacked: false, beginAtZero: false, minimum: 1, maximum: 5 })
		} else if (id.startsWith('length-')) {
			axes.set(id, { id, label: unit === null ? t('health', 'Length') : getUnitLabel(unit), stacked: false, beginAtZero: false })
		} else if (id.startsWith('blood-pressure-')) {
			axes.set(id, { id, label: unit === null ? getMetricLabel(metric.metricKey) : getUnitLabel(unit), stacked: false, beginAtZero: false })
		} else {
			const label = unit === null ? getMetricLabel(metric.metricKey) : `${getMetricLabel(metric.metricKey)} (${getUnitLabel(unit)})`
			axes.set(id, { id, label, stacked: false, beginAtZero: false })
		}
	}

	return [...axes.values()]
}

function displayValue(metricKey: AllMetricKey, value: number): number {
	const unit = displayUnitForMetric(props.configuration, metricKey)
	return unit === null ? value : fromCanonical(metricKey, value, unit)
}

function valueLabel(metricKey: AllMetricKey, value: number, event = false, displayValues = false): string {
	const formatted = (displayValues ? value : displayValue(metricKey, value)).toLocaleString(undefined, { maximumFractionDigits: 2 })
	if (event) {
		return formatted
	}

	const unit = displayUnitForMetric(props.configuration, metricKey)
	return unit === null ? formatted : `${formatted} ${getUnitLabel(unit)}`
}

function goalColor(metricKey: AllMetricKey, seriesKey: string): string {
	if (metricKey === 'hydration' || metricKey === 'break') {
		const eventSeries = getEventChartSeries(metricKey).find((item) => item.key === seriesKey)
		if (eventSeries !== undefined) {
			return eventSeries.color
		}
	}

	return getMetricVisual(metricKey).color
}

function overlayValue(goals: StatisticsGoalOverlay[], date: string): number | null {
	const matchingGoal = goals
		.filter((goal) => goal.effectiveFrom <= date && (goal.effectiveTo === null || date <= goal.effectiveTo))
		.sort((left, right) => right.effectiveFrom.localeCompare(left.effectiveFrom))[0]
	return matchingGoal?.targetValue ?? null
}

function appendGoalDatasets(metric: StatisticsMetric, datasets: PlotDataset[]): void {
	const groupedGoals = new Map<string, StatisticsGoalOverlay[]>()
	for (const goal of metric.goals) {
		const key = `${goal.goalId}:${goal.seriesKey}`
		const segments = groupedGoals.get(key) ?? []
		segments.push(goal)
		groupedGoals.set(key, segments)
	}

	for (const segments of groupedGoals.values()) {
		const goal = segments[0]
		if (goal === undefined) {
			continue
		}

		const eventGoal = metric.valueType === 'event'
		datasets.push({
			label: getStatisticsGoalLabel(goal.targetKey, goal.comparator, metric.metricKey),
			metricKey: metric.metricKey,
			data: metric.series.map((point) => {
				const value = overlayValue(segments, point.date)
				return value === null || eventGoal ? value : displayValue(metric.metricKey, value)
			}),
			color: goalColor(metric.metricKey, goal.seriesKey),
			type: 'line',
			goal: true,
			bar: false,
			displayValues: !eventGoal,
			stack: `goal:${goal.goalId}:${goal.seriesKey}`,
			axisId: axisIdForMetric(metric.metricKey),
		})
	}
}

function buildDatasets(): PlotDataset[] {
	const datasets: PlotDataset[] = []

	for (const metric of props.metrics) {
		if (metric.valueType === 'event') {
			const eventMetricKey = metric.metricKey as EventMetricKey
			for (const series of getEventChartSeries(eventMetricKey)) {
				datasets.push({
					label: getEventChartSeriesLabel(eventMetricKey, series.key),
					metricKey: metric.metricKey,
					data: metric.series.map((point) => point.subseries?.[series.key] ?? 0),
					color: series.color,
					type: 'bar',
					goal: false,
					bar: true,
					stack: `event:${metric.metricKey}`,
					axisId: axisIdForMetric(metric.metricKey),
				})
			}
			appendGoalDatasets(metric, datasets)
			continue
		}

		if (metric.metricKey === 'blood_pressure') {
			const metricLabel = getMetricLabel(metric.metricKey)
			const color = getMetricVisual(metric.metricKey).color
			datasets.push({
				label: t('health', '{metric} – systolic', { metric: metricLabel }),
				metricKey: metric.metricKey,
				data: metric.series.map((point) => point.subseries?.systolic === null || point.subseries?.systolic === undefined ? null : displayValue(metric.metricKey, point.subseries.systolic)),
				color,
				type: 'line',
				goal: false,
				bar: false,
				pointStyle: 'circle',
				axisId: axisIdForMetric(metric.metricKey),
			})
			datasets.push({
				label: t('health', '{metric} – diastolic', { metric: metricLabel }),
				metricKey: metric.metricKey,
				data: metric.series.map((point) => point.subseries?.diastolic === null || point.subseries?.diastolic === undefined ? null : displayValue(metric.metricKey, point.subseries.diastolic)),
				color: colorWithAlpha(color, 0.64),
				type: 'line',
				goal: false,
				bar: false,
				pointStyle: 'rectRot',
				axisId: axisIdForMetric(metric.metricKey),
			})
			continue
		}

		datasets.push({
			label: getMetricLabel(metric.metricKey),
			metricKey: metric.metricKey,
			data: metric.series.map((point) => point.value === null ? null : displayValue(metric.metricKey, point.value)),
			color: getMetricVisual(metric.metricKey).color,
			type: 'line',
			goal: false,
			bar: false,
			pointStyle: 'circle',
			axisId: axisIdForMetric(metric.metricKey),
		})
		appendGoalDatasets(metric, datasets)
	}

	return datasets
}

function cssColor(variable: string, fallback: string): string {
	const value = getComputedStyle(document.documentElement).getPropertyValue(variable).trim()
	return value || fallback
}

function renderChart(): void {
	if (canvas.value === null) {
		return
	}

	chart?.destroy()
	const textColor = cssColor('--color-main-text', '#222')
	const gridColor = cssColor('--color-border-dark', '#d9d9d9')
	const tooltipBackground = cssColor('--color-main-background', '#fff')
	const hasBarSeries = chartDatasets.value.some((dataset) => dataset.bar)
	const datasets = chartDatasets.value.map((dataset) => ({
		label: dataset.label,
		data: dataset.data,
		type: dataset.type,
		backgroundColor: dataset.bar ? dataset.color : 'transparent',
		borderColor: dataset.color,
		borderWidth: dataset.goal ? 2 : 2,
		fill: false,
		borderDash: dataset.goal ? [6, 4] : [],
		borderRadius: dataset.bar ? 2 : 0,
		pointRadius: dataset.goal ? 0 : 3,
		pointHoverRadius: dataset.goal ? 0 : 5,
		pointStyle: dataset.pointStyle ?? 'circle',
		stepped: dataset.goal ? 'after' : false,
		spanGaps: false,
		stack: dataset.stack,
		yAxisID: dataset.axisId,
		order: dataset.bar ? 2 : dataset.goal ? 1 : 0,
	}))
	const scales = {
		x: {
			stacked: hasBarSeries,
			ticks: {
				color: textColor,
				autoSkip: true,
				maxTicksLimit: labels.value.length > 180 ? 8 : labels.value.length > 30 ? 10 : 14,
				callback: (_value: number | string, index: number) => labels.value[index] === undefined ? '' : formatDate(labels.value[index]),
			},
			grid: { color: gridColor },
		},
		...Object.fromEntries(chartAxes.value.map((axis, index) => [axis.id, {
			type: 'linear' as const,
			position: index % 2 === 0 ? 'left' as const : 'right' as const,
			stacked: axis.stacked,
			beginAtZero: axis.beginAtZero,
			min: axis.minimum,
			max: axis.maximum,
			display: index < 3,
			title: { display: index < 3, text: axis.label, color: textColor },
			ticks: { display: index < 3, color: textColor },
			grid: { display: index === 0, color: gridColor },
		}])) as Record<string, unknown>,
	}

	const configuration = {
		type: 'line' as const,
		data: {
			labels: labels.value,
			datasets,
		},
		options: {
			animation: false,
			responsive: true,
			maintainAspectRatio: false,
			interaction: { mode: 'index', intersect: false },
			plugins: {
				legend: { display: false },
				tooltip: {
					backgroundColor: tooltipBackground,
					titleColor: textColor,
					bodyColor: textColor,
					borderColor: gridColor,
					borderWidth: 1,
					displayColors: false,
					callbacks: {
						title: (items: TooltipItem<'line' | 'bar'>[]) => {
							const date = labels.value[items[0]?.dataIndex ?? -1]
							return date === undefined ? '' : formatDate(date, true)
						},
						label: (item: TooltipItem<'line' | 'bar'>) => {
							const dataset = chartDatasets.value[item.datasetIndex]
							const value = item.parsed.y
							if (dataset === undefined || value === null) {
								return ''
							}
							return `${dataset.label}: ${valueLabel(dataset.metricKey, value, dataset.bar, dataset.displayValues)}`
						},
						footer: (items: TooltipItem<'line' | 'bar'>[]) => {
							if (!hasBarSeries) {
								return ''
							}
							const total = items
								.filter((item) => chartDatasets.value[item.datasetIndex]?.bar === true)
								.reduce((sum, item) => sum + (item.parsed.y ?? 0), 0)
							return t('health', 'Total {count}', { count: total })
						},
					},
				},
			},
			scales,
		},
	}

	chart = new ChartJs(canvas.value, configuration as ChartConfiguration)
}

function focusDataPoint(event: KeyboardEvent): void {
	if (chart === null || labels.value.length === 0 || (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight')) {
		return
	}

	event.preventDefault()
	focusedIndex = event.key === 'ArrowRight'
		? Math.min(labels.value.length - 1, focusedIndex + 1)
		: Math.max(0, focusedIndex - 1)
	const active = chartDatasets.value
		.map((dataset, datasetIndex) => dataset.data[focusedIndex] === null ? null : ({ datasetIndex, index: focusedIndex }))
		.filter((item): item is { datasetIndex: number, index: number } => item !== null)
	const date = labels.value[focusedIndex]
	if (date !== undefined) {
		const values = active.map(({ datasetIndex }) => {
			const dataset = chartDatasets.value[datasetIndex]!
			return `${dataset.label}: ${valueLabel(dataset.metricKey, dataset.data[focusedIndex]!, dataset.bar, dataset.displayValues)}`
		})
		if (chartDatasets.value.some((dataset) => dataset.bar)) {
			const total = active
				.filter(({ datasetIndex }) => chartDatasets.value[datasetIndex]?.bar === true)
				.reduce((sum, { datasetIndex }) => sum + (chartDatasets.value[datasetIndex]?.data[focusedIndex] ?? 0), 0)
			values.push(t('health', 'Total {count}', { count: total }))
		}
		focusedDescription.value = values.length === 0
			? t('health', '{date}. No data in this period.', { date: formatDate(date, true) })
			: `${formatDate(date, true)}. ${title.value}. ${values.join('. ')}`
	}
	const point = active[0] === undefined ? undefined : chart.getDatasetMeta(active[0].datasetIndex).data[focusedIndex]
	if (point === undefined) {
		return
	}

	chart.setActiveElements(active)
	chart.tooltip?.setActiveElements(active, { x: point.x, y: point.y })
	chart.update()
}

watch([chartDatasets, chartAxes, labels, title], () => {
	void nextTick(renderChart)
}, { deep: true })

onMounted(() => {
	renderChart()
	themeObserver = new MutationObserver(() => renderChart())
	themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'style', 'data-theme'] })
	themeObserver.observe(document.body, { attributes: true, attributeFilter: ['class', 'style', 'data-theme'] })
})

onBeforeUnmount(() => {
	themeObserver?.disconnect()
	chart?.destroy()
})
</script>

<template>
	<section aria-labelledby="statistics-chart" class="statistics-chart">
		<h2 id="statistics-chart" class="statistics-chart__title">
			{{ title }}
		</h2>
		<div class="statistics-chart__canvas-wrap">
			<canvas
				ref="canvas"
				:aria-label="canvasLabel"
				aria-describedby="statistics-chart-details"
				class="statistics-chart__canvas"
				role="img"
				tabindex="0"
				@keydown="focusDataPoint" />
			<p id="statistics-chart-details" aria-live="polite" class="statistics-chart__screen-reader-details">
				{{ focusedDescription }}
			</p>
			<p v-if="!hasData" class="statistics-chart__empty">
				{{ t('health', 'No data in this period') }}
			</p>
		</div>
		<ul :aria-label="t('health', 'Chart legend')" class="statistics-chart__legend">
			<li v-for="item in legendItems" :key="item.key" class="statistics-chart__legend-item">
				<span
					:class="{
						'statistics-chart__legend-swatch--bar': item.bar,
						'statistics-chart__legend-swatch--dashed': item.dashed,
						'statistics-chart__legend-swatch--diamond': item.pointStyle === 'rectRot',
					}"
					:style="{ '--statistics-series-color': item.color }"
					aria-hidden="true"
					class="statistics-chart__legend-swatch" />
				<span>{{ item.label }}</span>
			</li>
		</ul>
	</section>
</template>

<style scoped>
.statistics-chart {
	display: flex;
	flex-direction: column;
	min-width: 0;
	padding: calc(3 * var(--default-grid-baseline));
	background-color: var(--color-main-background);
	border: 1px solid var(--color-border-dark);
	border-radius: var(--border-radius-element);
	gap: calc(2 * var(--default-grid-baseline));
}

.statistics-chart__title {
	margin: 0;
	font-size: 1.15rem;
	font-weight: var(--font-weight-bold);
}

.statistics-chart__canvas-wrap {
	position: relative;
	min-height: 20rem;
}

.statistics-chart__canvas {
	width: 100% !important;
	height: 100% !important;
	outline-offset: 4px;
}

.statistics-chart__canvas:focus-visible {
	outline: 2px solid var(--color-primary-element);
}

.statistics-chart__empty {
	position: absolute;
	top: 50%;
	left: 50%;
	margin: 0;
	padding: calc(2 * var(--default-grid-baseline));
	background-color: var(--color-main-background);
	border-radius: var(--border-radius-element);
	color: var(--color-text-maxcontrast);
	transform: translate(-50%, -50%);
}

.statistics-chart__screen-reader-details {
	position: absolute;
	width: 1px;
	height: 1px;
	overflow: hidden;
	clip: rect(0 0 0 0);
	clip-path: inset(50%);
	white-space: nowrap;
}

.statistics-chart__legend {
	display: flex;
	flex-wrap: wrap;
	margin: 0;
	padding: 0;
	list-style: none;
	gap: var(--default-grid-baseline) calc(3 * var(--default-grid-baseline));
}

.statistics-chart__legend-item {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	font-size: var(--font-size-small);
}

.statistics-chart__legend-swatch {
	display: inline-block;
	width: 1.5rem;
	height: 0;
	border-top: 3px solid var(--statistics-series-color);
}

.statistics-chart__legend-swatch--dashed {
	border-top-style: dashed;
}

.statistics-chart__legend-swatch--bar {
	width: 0.75rem;
	height: 0.75rem;
	border: 0;
	border-radius: 2px;
	background-color: var(--statistics-series-color);
}

.statistics-chart__legend-swatch--diamond {
	width: 0.7rem;
	height: 0.7rem;
	border: 0;
	background-color: var(--statistics-series-color);
	transform: rotate(45deg);
}

@media (max-width: 600px) {
	.statistics-chart {
		padding: calc(2 * var(--default-grid-baseline));
	}

	.statistics-chart__canvas-wrap {
		min-height: 17rem;
	}
}
</style>
