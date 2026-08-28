<script setup lang="ts">
import type { AllMetricKey } from '../metrics.ts'

import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import { getMetricVisual } from '../metrics.ts'

defineProps<{
	metricKey: AllMetricKey
}>()
</script>

<template>
	<span aria-hidden="true" class="metric-icon">
		<NcIconSvgWrapper
			:path="getMetricVisual(metricKey).iconPath"
			:style="{ color: getMetricVisual(metricKey).color }" />
	</span>
</template>

<style scoped>
.metric-icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	flex: 0 0 var(--health-metric-icon-box-size, 24px);
	width: var(--health-metric-icon-box-size, 24px);
	height: var(--health-metric-icon-box-size, 24px);
	line-height: 0;
	opacity: 1;
	vertical-align: middle;
}

.metric-icon :deep(.icon-vue) {
	display: flex;
	width: var(--health-metric-icon-svg-size, 20px);
	height: var(--health-metric-icon-svg-size, 20px);
	min-width: 0;
	min-height: 0;
	line-height: 0;
	opacity: 1;
}

.metric-icon :deep(svg) {
	display: block;
	width: 100%;
	height: 100%;
	fill: currentColor;
	opacity: 1;
}
</style>
