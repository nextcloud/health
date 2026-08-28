<script setup lang="ts">
import type { MetricValue, ScaleMetricKey } from '../metrics.ts'

import { t } from '@nextcloud/l10n'
import { ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import MetricEditor from './MetricEditor.vue'
import MetricIcon from './MetricIcon.vue'
import { getMetricLabel, SCALE_METRIC_KEYS } from '../metrics.ts'

const props = defineProps<{
	open: boolean
	saving: boolean
	error: string | null
}>()

const emit = defineEmits<{
	'update:open': [open: boolean]
	save: [metricKey: ScaleMetricKey, value: MetricValue, note: string | null]
}>()

const selectedMetric = ref<ScaleMetricKey>('stress')

watch(() => props.open, (open) => {
	if (open) {
		selectedMetric.value = 'stress'
	}
})
</script>

<template>
	<NcDialog
		:open="open"
		:close-on-click-outside="!saving"
		:name="t('health', 'Add entry')"
		size="normal"
		@update:open="emit('update:open', $event)">
		<div class="add-entry-dialog">
			<div
				:aria-label="t('health', 'Choose metric')"
				class="add-entry-dialog__metrics"
				role="group">
				<NcButton
					v-for="metricKey in SCALE_METRIC_KEYS"
					:key="metricKey"
					:disabled="saving"
					:pressed="selectedMetric === metricKey"
					:text="getMetricLabel(metricKey)"
					@click="selectedMetric = metricKey">
					<template #icon>
						<MetricIcon :metric-key="metricKey" />
					</template>
				</NcButton>
			</div>

			<h3>{{ getMetricLabel(selectedMetric) }}</h3>
			<MetricEditor
				:key="selectedMetric"
				:error="error"
				:metric-key="selectedMetric"
				:saving="saving"
				@cancel="emit('update:open', false)"
				@save="(value, note) => emit('save', selectedMetric, value, note)" />
		</div>
	</NcDialog>
</template>

<style scoped>
.add-entry-dialog {
	display: flex;
	flex-direction: column;
	gap: 16px;
	padding-bottom: calc(2 * var(--default-grid-baseline));
}

.add-entry-dialog__metrics {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
}

.add-entry-dialog__metrics :deep(.button-vue__wrapper) {
	gap: var(--health-metric-title-gap, calc(3 * var(--default-grid-baseline)));
}

.add-entry-dialog h3 {
	margin: 0;
}
</style>
