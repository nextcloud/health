<script setup lang="ts">
import type { HealthConfiguration } from '../api/configuration.ts'
import type { StatisticsPeriod, StatisticsResponse } from '../api/statistics.ts'
import type { SavedStatisticsView } from '../api/statisticsViews.ts'
import type { AllMetricKey } from '../metrics.ts'

import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed, inject, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import SavedStatisticsViewActions from '../components/statistics/SavedStatisticsViewActions.vue'
import SavedStatisticsViewIcon from '../components/statistics/SavedStatisticsViewIcon.vue'
import StatisticsChart from '../components/statistics/StatisticsChart.vue'
import StatisticsConfigurationFields from '../components/statistics/StatisticsConfigurationFields.vue'
import StatisticsSummaryBox from '../components/statistics/StatisticsSummaryBox.vue'
import { getConfiguration, getEnabledMetricKeys } from '../api/configuration.ts'
import { getStatistics } from '../api/statistics.ts'
import { getSavedStatisticsView } from '../api/statisticsViews.ts'
import { healthConfigurationKey } from '../configurationContext.ts'
import { iconPaths } from '../icons.ts'
import { ALL_METRIC_KEYS, METRIC_KEYS } from '../metrics.ts'
import { savedStatisticsViewsKey } from '../statisticsViewContext.ts'
import { statisticsViewMode } from '../statisticsViews.ts'

const route = useRoute()
const router = useRouter()
const configuration = inject(healthConfigurationKey)
const savedStatisticsViews = inject(savedStatisticsViewsKey)
const period = ref<StatisticsPeriod>('last_30_days')
const selectedMetricKeys = ref<AllMetricKey[]>([])
const response = ref<StatisticsResponse | null>(null)
const savedView = ref<SavedStatisticsView | null>(null)
const savedViewLoading = ref(false)
const loading = ref(false)
const loadError = ref(false)
const defaultsApplied = ref(false)
const currentConfiguration = computed<HealthConfiguration | null>(() => configuration?.value ?? null)
const configurationLoading = ref(currentConfiguration.value === null)
const mode = computed(() => statisticsViewMode(route.name))
const isSavedView = computed(() => mode.value === 'saved')
const savedViewId = computed<number | null>(() => {
	if (!isSavedView.value || typeof route.params.id !== 'string') {
		return null
	}

	const id = Number(route.params.id)
	return Number.isSafeInteger(id) && id > 0 ? id : null
})
const displayedMetrics = computed(() => response.value?.metrics ?? [])
const pageTitle = computed(() => savedView.value?.title ?? t('health', 'Statistics'))
let requestGeneration = 0
let savedViewGeneration = 0

function applyDefaults(configuration: HealthConfiguration): void {
	selectedMetricKeys.value = getEnabledMetricKeys(configuration, METRIC_KEYS)
	defaultsApplied.value = true
}

async function loadConfiguration(): Promise<void> {
	if (currentConfiguration.value !== null) {
		configurationLoading.value = false
		return
	}

	try {
		if (configuration !== undefined) {
			configuration.value = await getConfiguration()
		}
	} catch {
		loadError.value = true
	} finally {
		configurationLoading.value = false
	}
}

async function loadSavedView(): Promise<void> {
	const generation = ++savedViewGeneration
	const id = savedViewId.value
	if (!isSavedView.value) {
		savedView.value = null
		if (!defaultsApplied.value && currentConfiguration.value !== null) {
			applyDefaults(currentConfiguration.value)
		}
		return
	}
	if (id === null) {
		showError(t('health', 'The saved Statistics view could not be found.'))
		void router.replace({ name: 'statistics' })
		return
	}

	savedViewLoading.value = true
	loadError.value = false
	try {
		const view = await getSavedStatisticsView(id)
		if (generation !== savedViewGeneration) {
			return
		}
		savedView.value = view
		savedStatisticsViews?.upsert(view)
		period.value = view.period
		selectedMetricKeys.value = [...view.metricKeys]
		defaultsApplied.value = true
	} catch {
		if (generation === savedViewGeneration) {
			showError(t('health', 'The saved Statistics view could not be found.'))
			void router.replace({ name: 'statistics' })
		}
	} finally {
		if (generation === savedViewGeneration) {
			savedViewLoading.value = false
		}
	}
}

async function loadStatistics(): Promise<void> {
	const metricKeys = selectedMetricKeys.value
	const generation = ++requestGeneration
	if (isSavedView.value && savedView.value === null) {
		return
	}
	if (metricKeys.length === 0) {
		response.value = null
		loading.value = false
		loadError.value = false
		return
	}

	loading.value = true
	loadError.value = false
	try {
		const nextResponse = await getStatistics(period.value, metricKeys)
		if (generation === requestGeneration) {
			response.value = nextResponse
		}
	} catch {
		if (generation === requestGeneration) {
			loadError.value = true
		}
	} finally {
		if (generation === requestGeneration) {
			loading.value = false
		}
	}
}

function saveCurrentView(): void {
	if (selectedMetricKeys.value.length === 0) {
		return
	}

	savedStatisticsViews?.openCreate({
		metricKeys: [...selectedMetricKeys.value],
		period: period.value,
	})
}

watch(currentConfiguration, (nextConfiguration) => {
	if (nextConfiguration === null || isSavedView.value) {
		return
	}

	if (!defaultsApplied.value) {
		applyDefaults(nextConfiguration)
		return
	}

	const availableKeys = new Set(getEnabledMetricKeys(nextConfiguration, ALL_METRIC_KEYS))
	selectedMetricKeys.value = selectedMetricKeys.value.filter((metricKey) => availableKeys.has(metricKey))
}, { immediate: true })

watch([savedViewId, isSavedView], () => {
	void loadSavedView()
}, { immediate: true })

watch(() => savedStatisticsViews?.views.value, (views) => {
	if (savedView.value === null) {
		return
	}

	const updated = views?.find((view) => view.id === savedView.value?.id)
	if (updated === undefined) {
		return
	}

	savedView.value = updated
	period.value = updated.period
	selectedMetricKeys.value = [...updated.metricKeys]
})

watch([period, selectedMetricKeys, savedView], () => {
	void loadStatistics()
}, { immediate: true })

onMounted(() => {
	void loadConfiguration()
})
</script>

<template>
	<main class="health-statistics">
		<header class="health-statistics__header">
			<div class="health-statistics__title-row">
				<div class="health-statistics__title">
					<SavedStatisticsViewIcon v-if="savedView !== null" :icon="savedView.icon" />
					<h1 class="health-page-title">
						{{ pageTitle }}
					</h1>
				</div>
				<NcButton
					v-if="!isSavedView"
					:aria-label="t('health', 'Save the current Statistics configuration as a view')"
					:disabled="selectedMetricKeys.length === 0"
					:text="t('health', 'Save view')"
					variant="primary"
					@click="saveCurrentView">
					<template #icon>
						<NcIconSvgWrapper :path="iconPaths.plus" />
					</template>
				</NcButton>
				<SavedStatisticsViewActions
					v-else-if="savedView !== null"
					variant="secondary"
					@delete="savedStatisticsViews?.openDelete(savedView)"
					@edit="savedStatisticsViews?.openEdit(savedView)" />
			</div>
			<p v-if="isSavedView" class="health-statistics__saved-description">
				{{ t('health', 'Saved Statistics view') }}
			</p>
			<StatisticsConfigurationFields
				v-model:metric-keys="selectedMetricKeys"
				v-model:period="period"
				:configuration="currentConfiguration"
				:readonly="isSavedView" />
		</header>

		<NcNoteCard
			v-if="loadError"
			type="error"
			:text="t('health', 'Statistics could not be loaded.')" />

		<div v-if="configurationLoading || savedViewLoading || (loading && response === null)" class="health-statistics__initial-loading">
			<NcLoadingIcon :name="t('health', 'Loading statistics')" />
		</div>

		<NcEmptyContent
			v-else-if="selectedMetricKeys.length === 0"
			:description="t('health', 'Choose one or more numeric metrics to display statistics.')"
			:name="t('health', 'No metrics selected.')" />

		<template v-else-if="response !== null">
			<p v-if="loading" class="health-statistics__updating" role="status">
				{{ t('health', 'Updating statistics…') }}
			</p>
			<div class="health-statistics__charts">
				<StatisticsChart
					:configuration="currentConfiguration"
					:metrics="displayedMetrics" />
			</div>

			<section aria-labelledby="statistics-summary-heading" class="health-statistics__summary">
				<h2 id="statistics-summary-heading" class="health-statistics__summary-heading">
					{{ t('health', 'Average for the selected period') }}
				</h2>
				<div class="health-statistics__summary-grid">
					<StatisticsSummaryBox
						v-for="metric in displayedMetrics"
						:key="metric.metricKey"
						:configuration="currentConfiguration"
						:metric="metric" />
				</div>
			</section>
		</template>
	</main>
</template>

<style scoped>
.health-statistics {
	display: flex;
	box-sizing: border-box;
	flex-direction: column;
	width: min(100%, 1280px);
	margin: 0 auto;
	padding: 24px clamp(32px, 4vw, 48px) 300px;
	gap: 24px;
}

.health-statistics__header {
	display: grid;
	gap: 16px;
}

.health-statistics__title-row,
.health-statistics__title {
	display: flex;
	align-items: center;
	gap: 12px;
}

.health-statistics__title-row {
	justify-content: space-between;
}

.health-statistics__saved-description,
.health-statistics__updating {
	margin: 0;
	color: var(--color-text-maxcontrast);
	font-size: var(--font-size-small);
}

.health-statistics__initial-loading {
	display: flex;
	justify-content: center;
	padding: 48px;
}

.health-statistics__charts {
	display: grid;
	min-width: 0;
	gap: 24px;
}

.health-statistics__summary {
	display: grid;
	margin-top: calc(2 * var(--default-grid-baseline));
	gap: 16px;
}

.health-statistics__summary-heading {
	margin: 0;
	font-size: 1.25rem;
	font-weight: var(--font-weight-bold);
}

.health-statistics__summary-grid {
	display: flex;
	flex-wrap: wrap;
	gap: calc(3 * var(--default-grid-baseline));
}

@media (max-width: 600px) {
	.health-statistics {
		padding: 16px 24px 300px;
	}

	.health-statistics__title-row {
		align-items: flex-start;
		flex-direction: column;
	}
}
</style>
