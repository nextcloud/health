<script setup lang="ts">
import type { EventMetricKey, EventOption, MetricKey, MetricValue } from '../metrics.ts'

import { t } from '@nextcloud/l10n'
import { computed, nextTick, ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcProgressBar from '@nextcloud/vue/components/NcProgressBar'
import NcRadioGroup from '@nextcloud/vue/components/NcRadioGroup'
import NcRadioGroupButton from '@nextcloud/vue/components/NcRadioGroupButton'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import EventSymbol from './EventSymbol.vue'
import ModalActions from './ModalActions.vue'
import {
	getEventOptions,
	getMetricLabel,
	getMetricVisual,
	getOptionLabel,
	getOptionSymbol,
	getScaleQuestion,
	isScaleMetric,
} from '../metrics.ts'

const props = withDefaults(defineProps<{
	metricKey: MetricKey
	initialNumericValue?: number | null
	initialOptionValue?: string | null
	initialNote?: string | null
	saving: boolean
	error: string | null
}>(), {
	initialNumericValue: null,
	initialOptionValue: null,
	initialNote: null,
})

const emit = defineEmits<{
	cancel: []
	save: [value: MetricValue, note: string | null]
}>()

const scaleChoices = ['1', '2', '3', '4', '5']
const selectedScaleValue = ref('')
const selectedOption = ref('')
const note = ref('')
const editor = ref<HTMLFormElement | null>(null)

const eventMetricKey = computed<EventMetricKey | null>(() => {
	return isScaleMetric(props.metricKey) ? null : props.metricKey
})

const eventOptions = computed<readonly EventOption[]>(() => {
	return eventMetricKey.value === null ? [] : getEventOptions(eventMetricKey.value)
})

const hasValidScaleValue = computed(() => {
	const value = Number(selectedScaleValue.value)
	return Number.isInteger(value) && value >= 1 && value <= 5
})

const canSave = computed(() => {
	return isScaleMetric(props.metricKey)
		? hasValidScaleValue.value
		: eventOptions.value.includes(selectedOption.value as EventOption)
})

const previewValue = computed(() => hasValidScaleValue.value ? Number(selectedScaleValue.value) * 20 : 0)

const previewLabel = computed(() => {
	if (!hasValidScaleValue.value) {
		return t('health', '{metric} preview: no value selected', { metric: getMetricLabel(props.metricKey) })
	}
	return t('health', '{metric}, {value} out of 5', {
		metric: getMetricLabel(props.metricKey),
		value: selectedScaleValue.value,
	})
})

async function focusFirstControl() {
	await nextTick()
	editor.value?.querySelector<HTMLElement>('input, textarea, button')?.focus()
}

defineExpose({ focusFirstControl })

watch(
	() => [props.metricKey, props.initialNumericValue, props.initialOptionValue, props.initialNote] as const,
	() => {
		selectedScaleValue.value = props.initialNumericValue === null ? '' : String(props.initialNumericValue)
		selectedOption.value = props.initialOptionValue ?? ''
		note.value = props.initialNote ?? ''
	},
	{ immediate: true },
)

function save() {
	if (!canSave.value) {
		return
	}

	const trimmedNote = note.value.trim()
	if (isScaleMetric(props.metricKey)) {
		emit('save', {
			numericValue: Number(selectedScaleValue.value),
			optionValue: null,
		}, trimmedNote === '' ? null : trimmedNote)
		return
	}

	emit('save', {
		numericValue: null,
		optionValue: selectedOption.value as EventOption,
	}, trimmedNote === '' ? null : trimmedNote)
}
</script>

<template>
	<form ref="editor" class="metric-editor" @submit.prevent="save">
		<NcRadioGroup
			v-if="isScaleMetric(metricKey)"
			v-model="selectedScaleValue"
			:description="t('health', 'Choose a value from 1 to 5.')"
			:label="getScaleQuestion(metricKey)">
			<NcRadioGroupButton
				v-for="choice in scaleChoices"
				:key="choice"
				:disabled="saving"
				:label="choice"
				:value="choice" />
		</NcRadioGroup>

		<NcRadioGroup
			v-else
			v-model="selectedOption"
			class="metric-editor__event-options"
			:label="t('health', 'Choose {metric} type', { metric: getMetricLabel(metricKey) })">
			<NcRadioGroupButton
				v-for="option in eventOptions"
				:key="option"
				:disabled="saving"
				:label="getOptionLabel(metricKey, option)"
				:value="option">
				<template #icon>
					<EventSymbol :symbol="getOptionSymbol(metricKey, option)" size="button" />
				</template>
			</NcRadioGroupButton>
		</NcRadioGroup>

		<div
			v-if="isScaleMetric(metricKey)"
			:aria-label="previewLabel"
			class="metric-editor__preview"
			role="img">
			<NcProgressBar :aria-hidden="true"
				:color="getMetricVisual(metricKey).color"
				:size="8"
				:value="previewValue" />
		</div>

		<NcTextField
			v-model="note"
			:disabled="saving"
			:label="t('health', 'Optional note')"
			:placeholder="t('health', 'Optional note')"
			:maxlength="1000" />

		<p v-if="error" class="metric-editor__error" role="alert">
			{{ error }}
		</p>

		<ModalActions>
			<NcButton
				:disabled="saving"
				:text="t('health', 'Cancel')"
				variant="tertiary"
				@click="emit('cancel')" />
			<NcButton
				:disabled="saving || !canSave"
				:text="saving ? t('health', 'Saving…') : t('health', 'Save')"
				type="submit"
				variant="primary" />
		</ModalActions>
	</form>
</template>

<style scoped>
.metric-editor {
	display: flex;
	flex-direction: column;
	gap: 16px;
}

.metric-editor__preview {
	width: 100%;
}

.metric-editor__event-options :deep(> div:last-child > div) {
	display: flex;
	flex-wrap: wrap;
}

.metric-editor__event-options :deep(> div:last-child > div > div) {
	flex: 1 1 11rem;
	min-width: min(100%, 11rem);
}

.metric-editor__error {
	margin: 0;
	color: var(--color-error-text);
}

</style>
