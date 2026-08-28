<script setup lang="ts">
import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { onBeforeUnmount, ref, watch } from 'vue'
import NcRichContenteditable from '@nextcloud/vue/components/NcRichContenteditable'
import { getDailyNote, saveDailyNote } from '../api/dailyNotes.ts'
import { localDateKey } from '../utils/dates.ts'

const props = defineProps<{ date: Date }>()

const MAX_LENGTH = 2000
const AUTOSAVE_DELAY = 800
type AutoCompleteResult = {
	id: string
	label: string
	icon: string
	source: string
	status: string | Record<string, unknown>
	subline: string
}

type AutoCompleteCallback = (results: AutoCompleteResult[]) => void

const content = ref('')
const loading = ref(false)
const activeDate = ref('')
const savedContent = new Map<string, string>()
const userData = ref<Record<string, AutoCompleteResult>>({})
let loadSequence = 0
let pendingSave: { date: string, content: string } | null = null
let saveTimer: ReturnType<typeof setTimeout> | undefined
let saveChain = Promise.resolve()

async function loadNote(date: string) {
	const sequence = ++loadSequence
	loading.value = true
	try {
		const note = await getDailyNote(date)
		if (sequence === loadSequence) {
			const loadedContent = note.content ?? ''
			savedContent.set(date, loadedContent)
			content.value = loadedContent
		}
	} catch {
		if (sequence === loadSequence) {
			savedContent.set(date, '')
			content.value = ''
			showError(t('health', 'Daily note could not be loaded.'))
		}
	} finally {
		if (sequence === loadSequence) {
			loading.value = false
		}
	}
}

async function persist(date: string, nextContent: string) {
	if (savedContent.get(date) === nextContent) {
		return
	}

	try {
		await saveDailyNote(date, nextContent)
		savedContent.set(date, nextContent)
		if (activeDate.value === date) {
			showSuccess(t('health', 'Daily note saved.'))
		}
	} catch {
		if (activeDate.value === date) {
			showError(t('health', 'Daily note could not be saved. Please try again.'))
		}
	}
}

async function flushPendingSave() {
	if (saveTimer !== undefined) {
		clearTimeout(saveTimer)
		saveTimer = undefined
	}
	const request = pendingSave
	pendingSave = null
	if (request === null) {
		return
	}

	saveChain = saveChain.then(() => persist(request.date, request.content))
	await saveChain
}

function scheduleSave(nextContent: string) {
	const date = activeDate.value
	if (date === '') {
		return
	}

	if (savedContent.get(date) === nextContent) {
		if (pendingSave?.date === date) {
			pendingSave = null
		}
		if (saveTimer !== undefined) {
			clearTimeout(saveTimer)
			saveTimer = undefined
		}
		return
	}

	pendingSave = { date, content: nextContent }
	if (saveTimer !== undefined) {
		clearTimeout(saveTimer)
	}
	saveTimer = setTimeout(() => {
		void flushPendingSave()
	}, AUTOSAVE_DELAY)
}

async function autoComplete(search: string, callback: AutoCompleteCallback) {
	try {
		const { data } = await axios.get<{ ocs: { data: AutoCompleteResult[] } }>(
			generateOcsUrl('core/autocomplete/get'),
			{
				params: {
					search,
					itemType: 'health-daily-note',
					itemId: activeDate.value,
					limit: 10,
				},
			},
		)
		for (const user of data.ocs.data) {
			userData.value[user.id] = user
		}
		callback(Object.values(userData.value))
	} catch {
		callback([])
	}
}

watch(content, scheduleSave)

watch(() => localDateKey(props.date), async (date) => {
	await flushPendingSave()
	activeDate.value = date
	await loadNote(date)
}, { immediate: true })

onBeforeUnmount(() => {
	void flushPendingSave()
})
</script>

<template>
	<section :aria-label="t('health', 'Daily note')" class="daily-note">
		<span v-if="loading" class="daily-note__state">{{ t('health', 'Loading…') }}</span>
		<NcRichContenteditable
			v-model="content"
			:auto-complete="autoComplete"
			:disabled="loading"
			:label="t('health', 'Daily note')"
			:maxlength="MAX_LENGTH"
			:placeholder="t('health', 'Write a note about your day')"
			:user-data="userData"
			multiline />
	</section>
</template>

<style scoped>
.daily-note {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.daily-note :deep(.rich-contenteditable) {
	width: 100%;
}

.daily-note :deep(.rich-contenteditable__input) {
	box-sizing: border-box;
	width: 100%;
	border-width: 1px;
	border-color: var(--health-journal-separator, var(--color-border-dark));
}

.daily-note__state {
	color: var(--color-text-maxcontrast);
	font-size: var(--default-font-size);
}
</style>
