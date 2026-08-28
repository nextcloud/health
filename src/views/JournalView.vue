<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import JournalDay from '../components/JournalDay.vue'
import { iconPaths } from '../icons.ts'
import { addLocalDays, localDateKey, parseLocalDateKey, startOfLocalDay } from '../utils/dates.ts'

const props = defineProps<{ date: string }>()
const router = useRouter()
const dateFormatter = new Intl.DateTimeFormat(undefined, { dateStyle: 'full' })
const today = computed(() => startOfLocalDay(new Date()))
const selectedDay = computed(() => parseLocalDateKey(props.date) ?? today.value)
const isToday = computed(() => localDateKey(selectedDay.value) === localDateKey(today.value))

watch(() => props.date, (date) => {
	const parsedDate = parseLocalDateKey(date)
	if (parsedDate === null || localDateKey(parsedDate) > localDateKey(today.value)) {
		void router.replace({ name: 'journal', params: { date: localDateKey(today.value) } })
	}
}, { immediate: true })

function changeDay(amount: -1 | 1) {
	if (amount > 0 && isToday.value) {
		return
	}
	const candidate = addLocalDays(selectedDay.value, amount)
	if (localDateKey(candidate) > localDateKey(today.value)) {
		return
	}
	void router.push({ name: 'journal', params: { date: localDateKey(candidate) } })
}

function goToToday() {
	void router.push({ name: 'journal', params: { date: localDateKey(today.value) } })
}
</script>

<template>
	<main class="health-journal">
		<header>
			<h1 class="health-page-title">
				{{ t('health', 'Journal') }}
			</h1>
		</header>

		<div class="health-journal__date-header">
			<time :datetime="localDateKey(selectedDay)">{{ dateFormatter.format(selectedDay) }}</time>
			<nav :aria-label="t('health', 'Journal day')" class="health-journal__day-navigation">
				<NcButton :aria-label="t('health', 'Previous day')"
					:title="t('health', 'Previous day')"
					variant="tertiary"
					@click="changeDay(-1)">
					<template #icon>
						<NcIconSvgWrapper :path="iconPaths.chevronLeft" />
					</template>
				</NcButton>
				<NcButton :disabled="isToday"
					:text="t('health', 'Today')"
					variant="tertiary"
					@click="goToToday" />
				<NcButton :aria-label="t('health', 'Next day')"
					:disabled="isToday"
					:title="t('health', 'Next day')"
					variant="tertiary"
					@click="changeDay(1)">
					<template #icon>
						<NcIconSvgWrapper :path="iconPaths.chevronRight" />
					</template>
				</NcButton>
			</nav>
		</div>

		<JournalDay
			:date="selectedDay"
			:heading="t('health', 'Journal entries')"
			:loading-label="t('health', 'Loading journal entries')" />
	</main>
</template>

<style scoped>
.health-journal {
	display: flex;
	flex-direction: column;
	gap: 28px;
	width: min(100%, 760px);
	margin: 0 auto;
	padding: 24px;
}

.health-journal__day-navigation {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	justify-content: flex-start;
	gap: 12px;
}

.health-journal__date-header {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	justify-content: space-between;
	gap: 16px;
}

.health-journal__date-header time {
	font-size: 1.5rem;
	font-weight: var(--font-weight-bold);
}

@media (max-width: 600px) {
	.health-journal {
		padding: 16px;
	}

	.health-journal__date-header {
		align-items: flex-start;
		flex-direction: column;
	}

	.health-journal__date-header time {
		width: 100%;
	}
}
</style>
