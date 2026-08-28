<script setup lang="ts">
import type { Entry } from '../api/entries.ts'
import type { GoalProgress, GoalTarget } from '../api/goals.ts'
import type { EventMetricKey, EventOption, MetricKey, MetricValue, ScaleMetricKey } from '../metrics.ts'

import { t } from '@nextcloud/l10n'
import { computed, ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcProgressBar from '@nextcloud/vue/components/NcProgressBar'
import BreakAction from './BreakAction.vue'
import CoffeeAction from './CoffeeAction.vue'
import DailyGoalPopover from './DailyGoalPopover.vue'
import DetailInformationPopover from './DetailInformationPopover.vue'
import EntryContextIndicator from './EntryContextIndicator.vue'
import EventSymbol from './EventSymbol.vue'
import MetricAggregateRow from './MetricAggregateRow.vue'
import MetricEditor from './MetricEditor.vue'
import { deleteEntry, updateEntry } from '../api/entries.ts'
import { goalProgressForMetric } from '../goals.ts'
import { iconPaths } from '../icons.ts'
import {
	getMetricLabel,
	getMetricVisual,
	getOptionLabel,
	getOptionSymbol,
	isCoffeeOption,
	isScaleMetric,
	isTeaOption,
	WATER_OPTIONS,
} from '../metrics.ts'

type JournalGroup = { metricKey: MetricKey, entries: Entry[] }

const props = defineProps<{
	dayKey: string
	entries: Entry[]
	initialLoading: boolean
	error: string | null
	loadingLabel: string
	goalProgresses: GoalProgress[]
	goalTargets: GoalTarget[]
	enabledMetricKeys: MetricKey[]
	createScaleEntry: (metricKey: ScaleMetricKey, value: MetricValue, note: string | null) => Promise<string | null>
}>()

const emit = defineEmits<{
	createEvent: [metricKey: EventMetricKey, optionValue: EventOption]
	deleted: [entry: Entry]
	status: [message: string, error?: boolean]
	updated: [entry: Entry]
}>()

function collapsedGroups(): Record<MetricKey, boolean> {
	return { stress: false, energy: false, mood: false, hydration: false, break: false }
}

const expandedGroups = ref<Record<MetricKey, boolean>>(collapsedGroups())
const editingId = ref<number | null>(null)
const savingEdit = ref(false)
const editError = ref<string | null>(null)
const creatingMetricKey = ref<ScaleMetricKey | null>(null)
const savingCreate = ref(false)
const createError = ref<string | null>(null)
const deleteCandidate = ref<Entry | null>(null)
const deleting = ref(false)
const deleteError = ref<string | null>(null)

const journalGroups = computed<JournalGroup[]>(() => props.enabledMetricKeys.map((metricKey) => ({
	metricKey,
	entries: props.entries.filter((entry) => entry.metricKey === metricKey),
})))
const editingEntry = computed(() => props.entries.find((entry) => entry.id === editingId.value) ?? null)

const timeFormatter = new Intl.DateTimeFormat(undefined, { hour: '2-digit', minute: '2-digit' })

watch(() => props.dayKey, () => {
	expandedGroups.value = collapsedGroups()
	editingId.value = null
	editError.value = null
	creatingMetricKey.value = null
	createError.value = null
})

function formatTime(timestamp: string): string {
	return timeFormatter.format(new Date(timestamp))
}

function calculateAverage(entries: Entry[]): number | null {
	const values = entries.flatMap((entry) => entry.numericValue === null ? [] : [entry.numericValue])
	if (values.length === 0) {
		return null
	}
	return values.reduce((sum, value) => sum + value, 0) / values.length
}

function averagePercentage(entries: Entry[]): number {
	const average = calculateAverage(entries)
	return average === null ? 0 : (average / 5) * 100
}

function averageLabel(metricKey: MetricKey, entries: Entry[]): string {
	const average = calculateAverage(entries)
	if (average === null) {
		return t('health', 'No {metric} entries', { metric: getMetricLabel(metricKey) })
	}
	return t('health', 'Average {metric}: {value} out of 5', {
		metric: getMetricLabel(metricKey),
		value: average.toFixed(1),
	})
}

function waterCount(entries: Entry[]): number {
	return entries.filter((entry) => WATER_OPTIONS.includes(entry.optionValue as typeof WATER_OPTIONS[number])).length
}

function coffeeCount(entries: Entry[]): number {
	return entries.filter((entry) => isCoffeeOption(entry.optionValue)).length
}

function teaCount(entries: Entry[]): number {
	return entries.filter((entry) => isTeaOption(entry.optionValue)).length
}

function startEditing(entry: Entry) {
	editingId.value = entry.id
	editError.value = null
}

function cancelEditing() {
	editingId.value = null
	editError.value = null
}

function openScaleCreate(metricKey: ScaleMetricKey) {
	creatingMetricKey.value = metricKey
	createError.value = null
}

function cancelScaleCreate() {
	if (!savingCreate.value) {
		creatingMetricKey.value = null
		createError.value = null
	}
}

async function saveScaleCreate(metricKey: ScaleMetricKey, value: MetricValue, note: string | null) {
	savingCreate.value = true
	createError.value = null
	try {
		const error = await props.createScaleEntry(metricKey, value, note)
		if (error === null) {
			creatingMetricKey.value = null
			return
		}
		createError.value = error
	} finally {
		savingCreate.value = false
	}
}

async function saveEdit(entry: Entry, value: MetricValue, note: string | null) {
	savingEdit.value = true
	editError.value = null
	try {
		const updatedEntry = await updateEntry(entry.id, {
			numericValue: value.numericValue,
			optionValue: value.optionValue,
			context: entry.context,
			recordedAt: entry.recordedAt,
			note,
		})
		editingId.value = null
		emit('updated', updatedEntry)
		emit('status', t('health', '{metric} updated.', { metric: getMetricLabel(entry.metricKey) }))
	} catch {
		editError.value = t('health', 'Could not save {metric}.', { metric: getMetricLabel(entry.metricKey) })
		emit('status', editError.value, true)
	} finally {
		savingEdit.value = false
	}
}

function requestDelete(entry: Entry) {
	deleteCandidate.value = entry
	deleteError.value = null
}

function cancelDelete() {
	if (!deleting.value) {
		deleteCandidate.value = null
		deleteError.value = null
	}
}

async function confirmDelete() {
	if (deleteCandidate.value === null) {
		return
	}
	const entry = deleteCandidate.value
	deleting.value = true
	deleteError.value = null
	try {
		await deleteEntry(entry.id)
		deleteCandidate.value = null
		if (editingId.value === entry.id) {
			editingId.value = null
		}
		if (props.entries.filter((current) => current.metricKey === entry.metricKey && current.id !== entry.id).length === 0) {
			expandedGroups.value[entry.metricKey as MetricKey] = false
		}
		emit('deleted', entry)
		emit('status', t('health', '{metric} entry deleted.', { metric: getMetricLabel(entry.metricKey) }))
	} catch {
		deleteError.value = t('health', 'Could not delete {metric} entry.', { metric: getMetricLabel(entry.metricKey) })
		emit('status', deleteError.value, true)
	} finally {
		deleting.value = false
	}
}

function toggleGroup(metricKey: MetricKey) {
	expandedGroups.value[metricKey] = !expandedGroups.value[metricKey]
}

function createEvent(metricKey: EventMetricKey, optionValue: EventOption) {
	emit('createEvent', metricKey, optionValue)
}

function progressesFor(metricKey: MetricKey): GoalProgress[] {
	return goalProgressForMetric(props.goalProgresses, metricKey)
}
</script>

<template>
	<div class="journal-entries">
		<div v-if="initialLoading" class="journal-entries__loading">
			<NcLoadingIcon :name="loadingLabel" :size="32" />
		</div>
		<p v-if="error" class="journal-entries__error" role="alert">
			{{ error }}
		</p>
		<ul class="journal-entries__list">
			<li v-for="group in journalGroups" :key="group.metricKey" class="journal-entries__item">
				<section class="journal-entries__group">
					<MetricAggregateRow
						:count="group.metricKey === 'hydration' ? 0 : group.entries.length"
						:details-id="`journal-${group.metricKey}-entries`"
						:expandable="group.entries.length > 0"
						:expanded="expandedGroups[group.metricKey]"
						:metric-key="group.metricKey"
						@toggle="toggleGroup(group.metricKey)">
						<template #aggregate>
							<span v-if="group.metricKey === 'hydration'" class="journal-entries__hydration-counts">
								<span v-if="waterCount(group.entries) > 0" :aria-label="t('health', 'Water: {count}', { count: waterCount(group.entries) })" class="journal-entries__category-count">
									<span aria-hidden="true">🥛</span> {{ t('health', '{count}×', { count: waterCount(group.entries) }) }}
								</span>
								<span v-if="coffeeCount(group.entries) > 0" :aria-label="t('health', 'Coffee: {count}', { count: coffeeCount(group.entries) })" class="journal-entries__category-count">
									<span aria-hidden="true">☕️</span> {{ t('health', '{count}×', { count: coffeeCount(group.entries) }) }}
								</span>
								<span v-if="teaCount(group.entries) > 0" :aria-label="t('health', 'Tea: {count}', { count: teaCount(group.entries) })" class="journal-entries__category-count">
									<span aria-hidden="true">🫖</span> {{ t('health', '{count}×', { count: teaCount(group.entries) }) }}
								</span>
							</span>
							<span v-if="isScaleMetric(group.metricKey) && group.entries.length > 0"
								:aria-label="averageLabel(group.metricKey, group.entries)"
								class="journal-entries__aggregate-progress"
								role="img">
								<NcProgressBar :aria-hidden="true"
									:color="getMetricVisual(group.metricKey).color"
									:size="8"
									:value="averagePercentage(group.entries)" />
							</span>
						</template>
						<template #status>
							<DailyGoalPopover v-if="progressesFor(group.metricKey).length"
								:progresses="progressesFor(group.metricKey)"
								:targets="goalTargets" />
						</template>
						<template #actions>
							<div class="journal-entries__quick-actions">
								<NcButton v-if="isScaleMetric(group.metricKey)"
									:aria-label="t('health', 'Add {metric} entry', { metric: getMetricLabel(group.metricKey) })"
									:title="t('health', 'Add {metric} entry', { metric: getMetricLabel(group.metricKey) })"
									variant="tertiary"
									@click="openScaleCreate(group.metricKey as ScaleMetricKey)">
									<template #icon>
										<NcIconSvgWrapper :path="iconPaths.plus" />
									</template>
								</NcButton>
								<template v-else-if="group.metricKey === 'hydration'">
									<NcButton :aria-label="t('health', 'Record small glass of water')"
										:title="t('health', 'Record water')"
										variant="tertiary"
										@click="createEvent('hydration', 'small_glass')">
										<template #icon>
											<EventSymbol symbol="🥛" size="button" />
										</template>
									</NcButton>
									<CoffeeAction variant="tertiary" @select="createEvent('hydration', $event)" />
								</template>
								<BreakAction v-else-if="group.metricKey === 'break'" variant="tertiary" @select="createEvent('break', $event)" />
							</div>
						</template>
						<template #details>
							<ul class="journal-entries__group-list">
								<li v-for="entry in group.entries" :key="entry.id" class="journal-entries__entry-item">
									<article class="journal-entries__entry">
										<time :datetime="entry.recordedAt">{{ formatTime(entry.recordedAt) }}</time>
										<div v-if="isScaleMetric(group.metricKey) && entry.numericValue !== null" class="journal-entries__detail-value">
											<div :aria-label="t('health', '{metric}, {value} out of 5', { metric: getMetricLabel(entry.metricKey), value: entry.numericValue })"
												class="journal-entries__detail-progress"
												role="img">
												<NcProgressBar :aria-hidden="true"
													:color="getMetricVisual(group.metricKey).color"
													:size="8"
													:value="entry.numericValue * 20" />
											</div>
											<EntryContextIndicator :context="entry.context" />
										</div>
										<span v-else class="journal-entries__option">
											<EventSymbol :symbol="getOptionSymbol(entry.metricKey, entry.optionValue)" />
											{{ getOptionLabel(entry.metricKey, entry.optionValue) }}
											<EntryContextIndicator :context="entry.context" />
										</span>
										<div class="journal-entries__detail-actions">
											<NcButton
												:aria-label="t('health', 'Edit {metric} entry', { metric: getMetricLabel(entry.metricKey) })"
												:title="t('health', 'Edit')"
												variant="tertiary"
												@click="startEditing(entry)">
												<template #icon>
													<NcIconSvgWrapper :path="iconPaths.pencil" />
												</template>
											</NcButton>
											<NcButton
												:aria-label="t('health', 'Delete {metric} entry', { metric: getMetricLabel(entry.metricKey) })"
												:title="t('health', 'Delete')"
												variant="tertiary"
												@click="requestDelete(entry)">
												<template #icon>
													<NcIconSvgWrapper :path="iconPaths.delete" />
												</template>
											</NcButton>
											<DetailInformationPopover
												:id="entry.id"
												:context="entry.context"
												:record-label="getMetricLabel(entry.metricKey)"
												:source="entry.source" />
										</div>
										<p v-if="entry.note" class="journal-entries__note">
											{{ entry.note }}
										</p>
									</article>
								</li>
							</ul>
						</template>
					</MetricAggregateRow>
				</section>
			</li>
		</ul>
	</div>
	<NcDialog v-if="creatingMetricKey"
		:close-on-click-outside="!savingCreate"
		:name="t('health', 'Add {metric}', { metric: getMetricLabel(creatingMetricKey) })"
		size="small"
		@closing="cancelScaleCreate">
		<p class="journal-entries__modal-context">
			{{ new Intl.DateTimeFormat(undefined, { dateStyle: 'full' }).format(new Date(`${dayKey}T00:00:00`)) }}
		</p>
		<MetricEditor :error="createError"
			:metric-key="creatingMetricKey"
			:saving="savingCreate"
			@cancel="cancelScaleCreate"
			@save="(value, note) => saveScaleCreate(creatingMetricKey!, value, note)" />
	</NcDialog>
	<NcDialog v-if="editingEntry"
		:close-on-click-outside="!savingEdit"
		:name="t('health', 'Edit {metric}', { metric: getMetricLabel(editingEntry.metricKey) })"
		size="small"
		@closing="cancelEditing">
		<p class="journal-entries__modal-context">
			{{ new Intl.DateTimeFormat(undefined, { dateStyle: 'full' }).format(new Date(`${dayKey}T00:00:00`)) }} · {{ formatTime(editingEntry.recordedAt) }}
		</p>
		<MetricEditor :error="editError"
			:initial-note="editingEntry.note"
			:initial-numeric-value="editingEntry.numericValue"
			:initial-option-value="editingEntry.optionValue"
			:metric-key="editingEntry.metricKey as MetricKey"
			:saving="savingEdit"
			@cancel="cancelEditing"
			@save="(value, note) => saveEdit(editingEntry!, value, note)" />
	</NcDialog>
	<NcDialog v-if="deleteCandidate"
		:close-on-click-outside="!deleting"
		:name="t('health', 'Delete entry')"
		size="small"
		@closing="cancelDelete">
		<p>
			{{ t('health', 'Delete this {metric} entry? This action cannot be undone.', { metric: getMetricLabel(deleteCandidate.metricKey) }) }}
		</p>
		<p v-if="deleteError" class="journal-entries__error" role="alert">
			{{ deleteError }}
		</p>
		<template #actions>
			<NcButton :disabled="deleting"
				:text="t('health', 'Cancel')"
				variant="tertiary"
				@click="cancelDelete" />
			<NcButton :disabled="deleting"
				:text="deleting ? t('health', 'Deleting…') : t('health', 'Delete')"
				variant="error"
				@click="confirmDelete" />
		</template>
	</NcDialog>
</template>

<style scoped>
.journal-entries__loading {
	display: flex;
	justify-content: center;
	padding: 32px;
}

.journal-entries__error { color: var(--color-error-text); }

.journal-entries__modal-context { margin-top: 0; color: var(--color-text-maxcontrast); }

.journal-entries__list,
.journal-entries__group-list {
	margin: 0;
	padding: 0;
	list-style: none;
}

.journal-entries__hydration-counts,
.journal-entries__category-count,
.journal-entries__quick-actions {
	display: inline-flex;
	align-items: center;
	gap: 6px;
}

.journal-entries__hydration-counts { flex-wrap: wrap; }

.journal-entries__category-count {
	color: var(--color-text-maxcontrast);
	font-variant-numeric: tabular-nums;
}

.journal-entries__category-count > span { font-size: 1rem; line-height: 1; }

.journal-entries__aggregate-progress {
	flex: 0 1 180px;
	width: 180px;
	min-width: 120px;
	margin-inline-start: 4px;
}

.journal-entries__quick-actions {
	justify-content: flex-end;
	min-width: 0;
}

.journal-entries__group-list {
	margin: 0;
}

.journal-entries__entry-item + .journal-entries__entry-item { border-top: 1px solid var(--health-journal-separator, var(--color-border-dark)); }

.journal-entries__entry {
	display: grid;
	grid-template-columns: 4.75rem minmax(0, 1fr) max-content;
	align-items: center;
	gap: 10px;
	padding: 10px 0;
	min-height: var(--default-clickable-area);
}

.journal-entries__detail-actions {
	display: inline-flex;
	align-items: center;
	min-width: calc(3 * var(--default-clickable-area));
	opacity: 0;
	transition: opacity 120ms ease-in-out;
}

.journal-entries__entry:hover .journal-entries__detail-actions,
.journal-entries__entry:focus-within .journal-entries__detail-actions {
	opacity: 1;
}

.journal-entries__detail-value {
	display: inline-flex;
	align-items: center;
	min-width: 0;
	gap: 6px;
}

.journal-entries__detail-progress {
	width: 180px;
	max-width: 100%;
}

.journal-entries__option {
	display: inline-flex;
	align-items: center;
	min-width: 0;
	overflow-wrap: anywhere;
	gap: 6px;
}

.journal-entries__note {
	grid-column: 2 / 4;
	margin: -2px 0 0;
	color: var(--color-text-maxcontrast);
	font-size: var(--font-size-small);
	font-style: italic;
	white-space: pre-wrap;
}

.journal-entries__editor {
	margin: 8px 0;
	padding: 16px;
	border: 1px solid var(--color-border-dark);
	border-radius: var(--border-radius-large);
	background: var(--color-background-hover);
}

@media (hover: none), (pointer: coarse) {
	.journal-entries__detail-actions { opacity: 1; }
}

@media (prefers-reduced-motion: reduce) {
	.journal-entries__detail-actions { transition: none; }
}

.journal-entries__editing-heading {
	display: flex;
	justify-content: space-between;
	gap: 12px;
	margin-bottom: 16px;
}

@media (max-width: 600px) {
	.journal-entries__aggregate-progress,
	.journal-entries__detail-progress { width: min(34vw, 180px); }

	.journal-entries__entry { grid-template-columns: 4.25rem minmax(0, 1fr) max-content; }

	.journal-entries__note { grid-column: 2 / 4; }
}
</style>
