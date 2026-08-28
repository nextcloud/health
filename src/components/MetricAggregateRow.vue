<script setup lang="ts">
import type { AllMetricKey } from '../metrics.ts'

import { t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import MetricIcon from './MetricIcon.vue'
import { iconPaths } from '../icons.ts'
import { getMetricLabel } from '../metrics.ts'

const props = withDefaults(defineProps<{
	count?: number
	detailsId?: string
	expandable?: boolean
	expanded?: boolean
	metricKey: AllMetricKey
}>(), {
	count: 0,
	detailsId: undefined,
	expandable: false,
	expanded: false,
})

const emit = defineEmits<{
	toggle: []
}>()

function toggle() {
	if (props.expandable) {
		emit('toggle')
	}
}

function onMainKeydown(event: KeyboardEvent) {
	if (!props.expandable || (event.key !== 'Enter' && event.key !== ' ')) {
		return
	}
	event.preventDefault()
	toggle()
}
</script>

<template>
	<div class="metric-aggregate-row" :class="{ 'metric-aggregate-row--expandable': expandable }">
		<div class="metric-aggregate-row__header">
			<div
				:aria-controls="expandable ? detailsId : undefined"
				:aria-expanded="expandable ? expanded : undefined"
				:role="expandable ? 'button' : undefined"
				:tabindex="expandable ? 0 : undefined"
				class="metric-aggregate-row__main"
				@click="toggle"
				@keydown="onMainKeydown">
				<span class="metric-aggregate-row__identity">
					<MetricIcon :metric-key="metricKey" />
					<strong class="metric-aggregate-row__title">{{ getMetricLabel(metricKey) }}</strong>
				</span>
				<span v-if="count > 0" class="metric-aggregate-row__count">{{ t('health', '{count}×', { count }) }}</span>
				<slot name="aggregate" />
			</div>
			<div class="metric-aggregate-row__status" @click.stop @keydown.stop>
				<slot name="status" />
			</div>
			<div class="metric-aggregate-row__actions" @click.stop @keydown.stop>
				<slot name="actions" />
			</div>
			<div class="metric-aggregate-row__disclosure" @click.stop @keydown.stop>
				<NcButton v-if="expandable"
					:aria-controls="detailsId"
					:aria-expanded="expanded"
					:aria-label="expanded ? t('health', 'Collapse {metric} entries', { metric: getMetricLabel(metricKey) }) : t('health', 'Expand {metric} entries', { metric: getMetricLabel(metricKey) })"
					variant="tertiary"
					@click="toggle">
					<template #icon>
						<NcIconSvgWrapper :path="expanded ? iconPaths.chevronUp : iconPaths.chevronDown" />
					</template>
				</NcButton>
			</div>
		</div>
		<div v-if="expandable && expanded" :id="detailsId" class="metric-aggregate-row__details">
			<slot name="details" />
		</div>
	</div>
</template>

<style scoped>
.metric-aggregate-row {
	--health-metric-icon-box-size: 24px;
	--health-metric-icon-svg-size: 20px;
	--health-metric-title-gap: calc(3 * var(--default-grid-baseline));
	--metric-aggregate-content-gap: calc(2 * var(--default-grid-baseline));
	--metric-aggregate-inline-padding: calc(1.5 * var(--default-grid-baseline));
	border-bottom: 1px solid var(--health-journal-separator, var(--color-border-dark));
}

.metric-aggregate-row__header {
	display: grid;
	grid-template-columns: minmax(0, 1fr) max-content max-content var(--default-clickable-area);
	align-items: center;
	min-height: calc(var(--default-clickable-area) + 20px);
	padding-block: calc(2.5 * var(--default-grid-baseline));
	padding-inline: var(--metric-aggregate-inline-padding) 0;
	border-radius: var(--border-radius-element);
	transition: background-color 120ms ease-in-out;
	column-gap: calc(2 * var(--default-grid-baseline));
}

.metric-aggregate-row__header:hover,
.metric-aggregate-row__header:focus-within {
	background-color: var(--color-background-hover);
}

.metric-aggregate-row__main {
	display: flex;
	align-items: center;
	align-self: stretch;
	flex-wrap: wrap;
	min-width: 0;
	padding-block: var(--default-grid-baseline);
	color: var(--color-main-text);
	gap: var(--metric-aggregate-content-gap);
}

.metric-aggregate-row--expandable .metric-aggregate-row__main {
	cursor: pointer;
}

.metric-aggregate-row__main:focus-visible {
	outline: 2px solid var(--color-primary-element);
	outline-offset: 2px;
	border-radius: var(--border-radius-element);
}

.metric-aggregate-row__identity {
	display: grid;
	grid-template-columns: var(--health-metric-icon-box-size) minmax(0, max-content);
	align-items: center;
	min-width: 0;
	gap: var(--health-metric-title-gap);
}

.metric-aggregate-row__title {
	color: var(--color-main-text);
}

.metric-aggregate-row__count {
	color: var(--color-text-maxcontrast);
	font-variant-numeric: tabular-nums;
}

.metric-aggregate-row__status,
.metric-aggregate-row__actions {
	display: inline-flex;
	align-items: center;
	justify-content: flex-end;
	min-width: 0;
	gap: var(--default-grid-baseline);
}

.metric-aggregate-row__disclosure {
	box-sizing: border-box;
	display: grid;
	width: var(--default-clickable-area);
	min-width: var(--default-clickable-area);
	min-height: var(--default-clickable-area);
	padding-inline-end: calc(1.5 * var(--default-grid-baseline));
	place-items: center;
}

.metric-aggregate-row__details {
	margin-inline-start: calc(var(--metric-aggregate-inline-padding) + var(--health-metric-icon-box-size) + var(--health-metric-title-gap));
}

@media (prefers-reduced-motion: reduce) {
	.metric-aggregate-row__header {
		transition: none;
	}
}

@media (max-width: 600px) {
	.metric-aggregate-row__header {
		grid-template-columns: minmax(0, 1fr) max-content var(--default-clickable-area);
	}

	.metric-aggregate-row__status {
		grid-column: 2;
	}

	.metric-aggregate-row__actions {
		grid-column: 1 / 3;
		justify-content: flex-start;
	}

	.metric-aggregate-row__disclosure {
		grid-column: 3;
		grid-row: 1 / span 2;
	}
}
</style>
