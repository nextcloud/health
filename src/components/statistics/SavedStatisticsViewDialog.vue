<script setup lang="ts">
import type { HealthConfiguration } from '../../api/configuration.ts'
import type { SavedStatisticsView, SavedStatisticsViewInput, StatisticsViewConfiguration } from '../../api/statisticsViews.ts'

import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed, ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcEmojiPicker from '@nextcloud/vue/components/NcEmojiPicker'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import ModalActions from '../ModalActions.vue'
import StatisticsConfigurationFields from './StatisticsConfigurationFields.vue'
import { createSavedStatisticsView, updateSavedStatisticsView } from '../../api/statisticsViews.ts'
import { DEFAULT_SAVED_STATISTICS_VIEW_ICON, safeSavedStatisticsViewIcon, savedStatisticsViewConfiguration } from '../../statisticsViews.ts'

type DialogMode = 'create' | 'edit' | 'clone'

interface Draft extends SavedStatisticsViewInput {
	id: number | null
}

const props = defineProps<{
	open: boolean
	mode: DialogMode
	view: SavedStatisticsView | null
	initialConfiguration: StatisticsViewConfiguration | null
	configuration: HealthConfiguration | null
}>()

const emit = defineEmits<{
	'update:open': [open: boolean]
	saved: [view: SavedStatisticsView]
}>()

const draft = ref<Draft | null>(null)
const saving = ref(false)
const error = ref<string | null>(null)
const dialogTitle = computed(() => ({
	create: t('health', 'Save Statistics view'),
	edit: t('health', 'Edit saved Statistics view'),
	clone: t('health', 'Clone saved Statistics view'),
})[props.mode])
const saveLabel = computed(() => ({
	create: t('health', 'Save view'),
	edit: t('health', 'Save changes'),
	clone: t('health', 'Create copy'),
})[props.mode])
watch(() => [props.open, props.mode, props.view, props.initialConfiguration] as const, () => {
	if (!props.open) {
		return
	}

	const sourceConfiguration = props.view === null
		? props.initialConfiguration
		: savedStatisticsViewConfiguration(props.view)
	if (sourceConfiguration === null) {
		draft.value = null
		return
	}

	draft.value = {
		id: props.mode === 'edit' ? props.view?.id ?? null : null,
		title: props.mode === 'clone' ? '' : props.view?.title ?? '',
		icon: safeSavedStatisticsViewIcon(props.view?.icon ?? DEFAULT_SAVED_STATISTICS_VIEW_ICON),
		metricKeys: [...sourceConfiguration.metricKeys],
		period: sourceConfiguration.period,
	}
	error.value = null
}, { immediate: true })

function close(open: boolean): void {
	if (!open && !saving.value) {
		emit('update:open', false)
	}
}

function setIcon(icon: string): void {
	if (draft.value !== null) {
		draft.value.icon = icon
	}
}

function input(): SavedStatisticsViewInput | null {
	if (draft.value === null) {
		return null
	}

	return {
		title: draft.value.title,
		icon: draft.value.icon,
		metricKeys: [...draft.value.metricKeys],
		period: draft.value.period,
	}
}

async function save(): Promise<void> {
	const request = input()
	if (request === null || saving.value) {
		return
	}

	saving.value = true
	error.value = null
	try {
		const view = props.mode === 'edit' && draft.value !== null && draft.value.id !== null
			? await updateSavedStatisticsView(draft.value.id, request)
			: await createSavedStatisticsView(request)
		emit('saved', view)
		showSuccess(props.mode === 'edit'
			? t('health', 'Saved Statistics view updated.')
			: props.mode === 'clone'
				? t('health', 'Saved Statistics view cloned.')
				: t('health', 'Statistics view saved.'))
		emit('update:open', false)
	} catch {
		error.value = t('health', 'The saved Statistics view could not be saved. Check the title and selected metrics.')
		showError(error.value)
	} finally {
		saving.value = false
	}
}
</script>

<template>
	<NcDialog
		:open="open"
		:name="dialogTitle"
		size="normal"
		@update:open="close">
		<form v-if="draft !== null" class="saved-statistics-view-dialog" @submit.prevent="save">
			<NcNoteCard v-if="error !== null" type="error" :text="error" />
			<div class="saved-statistics-view-dialog__identity">
				<div class="saved-statistics-view-dialog__icon-picker">
					<NcEmojiPicker
						:selected-emoji="draft.icon"
						:show-preview="true"
						:container="false"
						@select="setIcon">
						<NcButton
							:aria-label="t('health', 'Choose view icon')"
							class="saved-statistics-view-dialog__icon-button"
							variant="secondary">
							<span aria-hidden="true" class="saved-statistics-view-dialog__selected-icon">{{ safeSavedStatisticsViewIcon(draft.icon) }}</span>
						</NcButton>
					</NcEmojiPicker>
				</div>
				<NcTextField
					v-model="draft.title"
					:label="t('health', 'View title')"
					:maxlength="120"
					required />
			</div>
			<StatisticsConfigurationFields
				v-model:metric-keys="draft.metricKeys"
				v-model:period="draft.period"
				:configuration="configuration"
				id-prefix="saved-statistics-view"
				layout="saved-view" />
		</form>
		<template #actions>
			<ModalActions>
				<NcButton :disabled="saving" :text="t('health', 'Cancel')" @click="close(false)" />
				<NcButton
					:disabled="saving || draft === null || draft.title.trim() === '' || draft.metricKeys.length === 0"
					:text="saveLabel"
					variant="primary"
					@click="save" />
			</ModalActions>
		</template>
	</NcDialog>
</template>

<style scoped>
.saved-statistics-view-dialog {
	display: grid;
	gap: calc(3 * var(--default-grid-baseline));
	min-width: 0;
	padding-bottom: calc(2 * var(--default-grid-baseline));
}

.saved-statistics-view-dialog__icon-picker {
	display: flex;
	flex: 0 0 auto;
}

.saved-statistics-view-dialog__identity {
	display: flex;
	align-items: flex-end;
	gap: calc(2 * var(--default-grid-baseline));
}

.saved-statistics-view-dialog__identity :deep(.input-field) {
	min-width: 0;
	flex: 1 1 auto;
}

.saved-statistics-view-dialog__selected-icon {
	font-size: 1.25rem;
	line-height: 1;
}

.saved-statistics-view-dialog__icon-button {
	min-width: var(--default-clickable-area);
	padding-inline: var(--border-radius-element);
}

@media (max-width: 480px) {
	.saved-statistics-view-dialog__identity {
		align-items: stretch;
		flex-direction: column;
	}
}
</style>
