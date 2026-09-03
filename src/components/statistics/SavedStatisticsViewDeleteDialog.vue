<script setup lang="ts">
import type { SavedStatisticsView } from '../../api/statisticsViews.ts'

import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import ModalActions from '../ModalActions.vue'
import { deleteSavedStatisticsView } from '../../api/statisticsViews.ts'

const props = defineProps<{
	open: boolean
	view: SavedStatisticsView | null
}>()

const emit = defineEmits<{
	'update:open': [open: boolean]
	deleted: [id: number]
}>()

const deleting = ref(false)
const error = ref<string | null>(null)

function close(open: boolean): void {
	if (!open && !deleting.value) {
		error.value = null
		emit('update:open', false)
	}
}

async function destroy(): Promise<void> {
	if (props.view === null || deleting.value) {
		return
	}

	deleting.value = true
	error.value = null
	try {
		await deleteSavedStatisticsView(props.view.id)
		emit('deleted', props.view.id)
		showSuccess(t('health', 'Saved Statistics view deleted.'))
		emit('update:open', false)
	} catch {
		error.value = t('health', 'The saved Statistics view could not be deleted.')
		showError(error.value)
	} finally {
		deleting.value = false
	}
}
</script>

<template>
	<NcDialog
		:open="open"
		:name="t('health', 'Delete saved Statistics view')"
		size="small"
		@update:open="close">
		<div class="saved-statistics-view-delete-dialog">
			<NcNoteCard v-if="error !== null" type="error" :text="error" />
			<p v-else>
				{{ t('health', 'Delete the saved Statistics view “{title}”?', { title: view?.title ?? '' }) }}
			</p>
		</div>
		<template #actions>
			<ModalActions>
				<NcButton :disabled="deleting" :text="t('health', 'Cancel')" @click="close(false)" />
				<NcButton
					:disabled="deleting || view === null"
					:text="t('health', 'Delete')"
					variant="error"
					@click="destroy" />
			</ModalActions>
		</template>
	</NcDialog>
</template>

<style scoped>
.saved-statistics-view-delete-dialog p {
	margin: 0;
}
</style>
