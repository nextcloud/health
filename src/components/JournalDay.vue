<script setup lang="ts">
import type { HealthConfiguration } from '../api/configuration.ts'
import type { Entry } from '../api/entries.ts'
import type { GoalProgress, GoalTarget } from '../api/goals.ts'
import type { EventMetricKey, EventOption, MetricValue, ScaleMetricKey } from '../metrics.ts'

import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { inject, ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import BreakAction from './BreakAction.vue'
import CoffeeAction from './CoffeeAction.vue'
import DailyNote from './DailyNote.vue'
import DailyValues from './DailyValues.vue'
import EventSymbol from './EventSymbol.vue'
import JournalEntries from './JournalEntries.vue'
import MeasurementsSection from './MeasurementsSection.vue'
import RoutineDialog from './RoutineDialog.vue'
import { getConfiguration, getEnabledMetricKeys, isMetricEnabled } from '../api/configuration.ts'
import { createEntry, listAllEntries } from '../api/entries.ts'
import { listGoalProgress, listGoals } from '../api/goals.ts'
import { healthConfigurationKey } from '../configurationContext.ts'
import { getMetricLabel, getOptionLabel, getOptionSymbol, METRIC_KEYS } from '../metrics.ts'
import { getLocalDayRange, localDateKey, recordedAtForLocalDay } from '../utils/dates.ts'

const props = defineProps<{
	date: Date
	heading: string
	loadingLabel: string
}>()

const entries = ref<Entry[]>([])
const initialLoading = ref(true)
const loadError = ref<string | null>(null)
const configuration = inject(healthConfigurationKey, ref<HealthConfiguration | null>(null))
const checkInOpen = ref(false)
const checkOutOpen = ref(false)
const routineRefreshKey = ref(0)
const activeCreateRequests = ref(0)
const goalProgresses = ref<GoalProgress[]>([])
const goalTargets = ref<GoalTarget[]>([])
let loadSequence = 0
let activeDayKey = ''
const mutationOverrides = new Map<number, Entry | null>()

function sortEntries(nextEntries: Entry[]): Entry[] {
	return [...nextEntries].sort((left, right) => {
		const timestampDifference = new Date(right.recordedAt).getTime() - new Date(left.recordedAt).getTime()
		return timestampDifference !== 0 ? timestampDifference : right.id - left.id
	})
}

function isEntryForActiveDay(entry: Entry): boolean {
	return localDateKey(new Date(entry.recordedAt)) === activeDayKey
}

function applyOverrides(serverEntries: Entry[]): Entry[] {
	const mergedEntries = new Map(serverEntries.map((entry) => [entry.id, entry]))
	for (const [id, entry] of mutationOverrides) {
		if (entry === null) {
			mergedEntries.delete(id)
		} else {
			mergedEntries.set(id, entry)
		}
	}

	return sortEntries([...mergedEntries.values()])
}

function applyCreatedEntry(entry: Entry) {
	if (!isEntryForActiveDay(entry)) {
		return
	}

	mutationOverrides.set(entry.id, entry)
	entries.value = sortEntries([...entries.value.filter((current) => current.id !== entry.id), entry])
}

function applyUpdatedEntry(entry: Entry) {
	if (!isEntryForActiveDay(entry)) {
		return
	}

	mutationOverrides.set(entry.id, entry)
	entries.value = sortEntries(entries.value.map((current) => current.id === entry.id ? entry : current))
	void loadGoalProgress()
}

function applyDeletedEntry(entry: Entry) {
	mutationOverrides.set(entry.id, null)
	entries.value = entries.value.filter((current) => current.id !== entry.id)
	void loadGoalProgress()
}

async function loadEntries() {
	const dayKey = localDateKey(props.date)
	const sequence = ++loadSequence
	if (activeDayKey !== dayKey) {
		activeDayKey = dayKey
		entries.value = []
		mutationOverrides.clear()
	}
	initialLoading.value = true
	loadError.value = null
	try {
		const loaded = await listAllEntries({ ...getLocalDayRange(props.date), limit: 200 })
		if (sequence === loadSequence && activeDayKey === dayKey) {
			entries.value = applyOverrides(loaded)
		}
	} catch {
		if (sequence === loadSequence && activeDayKey === dayKey) {
			loadError.value = t('health', 'The journal could not be loaded. Please try again.')
		}
	} finally {
		if (sequence === loadSequence && activeDayKey === dayKey) {
			initialLoading.value = false
		}
	}
}

function createActionLabel(metricKey: ScaleMetricKey | EventMetricKey, optionValue: EventOption | null): string {
	if (metricKey === 'hydration' && optionValue === 'small_glass') {
		return t('health', 'Water')
	}
	if (metricKey === 'break' && optionValue === 'fresh_air') {
		return t('health', 'Fresh-air break')
	}
	return metricKey === 'hydration' || metricKey === 'break'
		? getOptionLabel(metricKey, optionValue)
		: getMetricLabel(metricKey)
}

function createSuccessMessage(metricKey: ScaleMetricKey | EventMetricKey, optionValue: EventOption | null): string {
	const label = createActionLabel(metricKey, optionValue)
	if (metricKey === 'hydration' || metricKey === 'break') {
		return t('health', '{symbol} {entry} recorded.', {
			symbol: getOptionSymbol(metricKey, optionValue),
			entry: label,
		})
	}
	return t('health', '{metric} recorded.', { metric: label })
}

function createErrorMessage(metricKey: ScaleMetricKey | EventMetricKey, optionValue: EventOption | null): string {
	const label = createActionLabel(metricKey, optionValue)
	return metricKey === 'hydration' || metricKey === 'break'
		? t('health', 'Could not record {entry}.', { entry: label })
		: t('health', 'Could not save {metric}.', { metric: label })
}

async function createMetric(
	metricKey: ScaleMetricKey | EventMetricKey,
	value: MetricValue,
	note: string | null,
): Promise<string | null> {
	activeCreateRequests.value++
	try {
		const createdEntry = await createEntry({
			metricKey,
			numericValue: value.numericValue,
			optionValue: value.optionValue,
			context: 'manual',
			source: 'web',
			recordedAt: recordedAtForLocalDay(props.date),
			note,
		})
		applyCreatedEntry(createdEntry)
		void loadGoalProgress()
		showSuccess(createSuccessMessage(metricKey, value.optionValue))
		return null
	} catch {
		const message = createErrorMessage(metricKey, value.optionValue)
		showError(message)
		return message
	} finally {
		activeCreateRequests.value--
	}
}

async function createScaleEntry(metricKey: ScaleMetricKey, value: MetricValue, note: string | null): Promise<string | null> {
	return createMetric(metricKey, value, note)
}

async function createEvent(metricKey: EventMetricKey, optionValue: EventOption) {
	await createMetric(metricKey, {
		numericValue: null,
		optionValue,
	}, null)
}

function setStatus(message: string, error = false) {
	if (error) {
		showError(message)
		return
	}
	showSuccess(message)
}

function applyRoutine(result: { createdEntries: Entry[] }) {
	for (const entry of result.createdEntries) {
		applyCreatedEntry(entry)
	}
	routineRefreshKey.value++
	void loadGoalProgress()
	setStatus(checkInOpen.value ? t('health', 'Check-in saved.') : t('health', 'Check-out saved.'))
}

async function loadConfiguration() {
	if (configuration.value !== null) {
		return
	}

	try {
		configuration.value = await getConfiguration()
	} catch {
		showError(t('health', 'Configuration could not be loaded.'))
	}
}

async function loadGoalProgress() {
	const dayKey = localDateKey(props.date)
	try {
		const [definitions, progresses, longTermProgresses] = await Promise.all([
			goalTargets.value.length === 0 ? listGoals() : Promise.resolve(null),
			listGoalProgress('day', dayKey),
			dayKey === localDateKey(new Date()) ? listGoalProgress('long_term') : Promise.resolve([]),
		])
		if (localDateKey(props.date) !== dayKey) {
			return
		}
		if (definitions !== null) {
			goalTargets.value = definitions.targets
		}
		goalProgresses.value = [...progresses, ...longTermProgresses]
	} catch {
		// Goal indicators are supplementary; journal data remains usable if they cannot load.
	}
}

watch(() => localDateKey(props.date), () => {
	void loadEntries()
	void loadGoalProgress()
}, { immediate: true })
void loadConfiguration()
</script>

<template>
	<section :aria-busy="activeCreateRequests > 0"
		:aria-labelledby="`journal-heading-${localDateKey(date)}`"
		class="journal-day">
		<div class="journal-day__heading">
			<h2 :id="`journal-heading-${localDateKey(date)}`">
				{{ heading }}
			</h2>
			<div :aria-label="t('health', 'Journal actions')" class="journal-day__actions" role="group">
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
					:title="t('health', 'Record water')"
					variant="secondary"
					@click="createEvent('hydration', 'small_glass')">
					<template #icon>
						<EventSymbol symbol="🥛" size="button" />
					</template>
				</NcButton>
				<CoffeeAction v-if="isMetricEnabled(configuration, 'hydration')" @select="createEvent('hydration', $event)" />
				<BreakAction v-if="isMetricEnabled(configuration, 'break')" @select="createEvent('break', $event)" />
			</div>
		</div>

		<DailyNote :key="localDateKey(date)" :date="date" />
		<DailyValues :key="`daily-${routineRefreshKey}`"
			:date="localDateKey(date)"
			:configuration="configuration"
			:goal-progresses="goalProgresses"
			:goal-targets="goalTargets"
			@saved="loadGoalProgress" />
		<div class="journal-day__timestamped-list">
			<MeasurementsSection :key="`measurements-${routineRefreshKey}`"
				:date="date"
				:configuration="configuration"
				:goal-progresses="goalProgresses"
				:goal-targets="goalTargets"
				@saved="loadGoalProgress" />

			<JournalEntries
				:create-scale-entry="createScaleEntry"
				:day-key="localDateKey(date)"
				:entries="entries"
				:error="loadError"
				:goal-progresses="goalProgresses"
				:goal-targets="goalTargets"
				:initial-loading="initialLoading"
				:loading-label="loadingLabel"
				:enabled-metric-keys="getEnabledMetricKeys(configuration, METRIC_KEYS)"
				@createEvent="createEvent"
				@deleted="applyDeletedEntry"
				@status="setStatus"
				@updated="applyUpdatedEntry" />
		</div>
		<div aria-hidden="true" class="journal-day__bottom-spacer" />
		<RoutineDialog v-model:open="checkInOpen"
			context="check-in"
			:date="localDateKey(date)"
			:configuration="configuration"
			@saved="applyRoutine" />
		<RoutineDialog v-model:open="checkOutOpen"
			context="check-out"
			:date="localDateKey(date)"
			:configuration="configuration"
			@saved="applyRoutine" />
	</section>
</template>

<style scoped>
.journal-day {
	--health-journal-separator: var(--color-border-dark);
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.journal-day__heading {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
}

.journal-day__heading h2 {
	margin: 0;
}

.journal-day__actions {
	display: flex;
	align-items: center;
	gap: 4px;
}

.journal-day__bottom-spacer { height: 300px; }

@media (max-width: 420px) {
	.journal-day__heading {
		align-items: flex-start;
		flex-direction: column;
	}
}
</style>
