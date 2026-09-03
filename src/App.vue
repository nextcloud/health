<script setup lang="ts">
import type { HealthConfiguration } from './api/configuration.ts'
import type { SavedStatisticsView, StatisticsViewConfiguration } from './api/statisticsViews.ts'

import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed, onMounted, provide, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcAppSettingsDialog from '@nextcloud/vue/components/NcAppSettingsDialog'
import NcContent from '@nextcloud/vue/components/NcContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import SettingsContent from './components/SettingsContent.vue'
import SavedStatisticsViewDeleteDialog from './components/statistics/SavedStatisticsViewDeleteDialog.vue'
import SavedStatisticsViewDialog from './components/statistics/SavedStatisticsViewDialog.vue'
import SavedStatisticsViewIcon from './components/statistics/SavedStatisticsViewIcon.vue'
import SupportDialog from './components/SupportDialog.vue'
import { listSavedStatisticsViews } from './api/statisticsViews.ts'
import { healthConfigurationKey } from './configurationContext.ts'
import { iconPaths } from './icons.ts'
import { savedStatisticsViewsKey } from './statisticsViewContext.ts'
import { localDateKey } from './utils/dates.ts'
const route = useRoute()
const router = useRouter()

type ViewKey = 'journal' | 'goals' | 'statistics'
type SavedStatisticsViewDialogMode = 'create' | 'edit' | 'clone'

interface SavedStatisticsViewDialogState {
	mode: SavedStatisticsViewDialogMode
	view: SavedStatisticsView | null
	initialConfiguration: StatisticsViewConfiguration | null
}

const settingsOpen = ref(false)
const supportOpen = ref(false)
const configuration = ref<HealthConfiguration | null>(null)
const savedStatisticsViews = ref<SavedStatisticsView[]>([])
const savedStatisticsViewDialog = ref<SavedStatisticsViewDialogState | null>(null)
const savedStatisticsViewDeleteCandidate = ref<SavedStatisticsView | null>(null)
const statisticsNavigationOpen = ref(true)

provide(healthConfigurationKey, configuration)
provide(savedStatisticsViewsKey, {
	views: savedStatisticsViews,
	upsert: upsertSavedStatisticsView,
	remove: removeSavedStatisticsView,
	openCreate: (initialConfiguration) => {
		savedStatisticsViewDialog.value = { mode: 'create', view: null, initialConfiguration }
	},
	openEdit: (view) => {
		savedStatisticsViewDialog.value = { mode: 'edit', view, initialConfiguration: null }
	},
	openClone: openCloneSavedStatisticsView,
	openDelete: openDeleteSavedStatisticsView,
})

const activeView = computed<ViewKey>(() => {
	if (route.name === 'statistics-view') {
		return 'statistics'
	}
	if (route.name === 'goals' || route.name === 'statistics') {
		return route.name
	}

	return 'journal'
})

const navigationItems: Array<{ key: ViewKey, name: string, icon: string }> = [
	{ key: 'journal', name: t('health', 'Journal'), icon: 'book' },
	{ key: 'goals', name: t('health', 'Goals'), icon: 'flag' },
	{ key: 'statistics', name: t('health', 'Statistics'), icon: 'icon-search' },
]

function navigate(view: ViewKey) {
	if (view === 'journal') {
		void router.push({ name: 'journal', params: { date: localDateKey(new Date()) } })
		return
	}

	void router.push({ name: view })
}

function closeSupport() {
	supportOpen.value = false
	if (route.name === 'donate') {
		void router.replace({ name: 'journal', params: { date: localDateKey(new Date()) } })
	}
}

function upsertSavedStatisticsView(view: SavedStatisticsView): void {
	const existingIndex = savedStatisticsViews.value.findIndex((item) => item.id === view.id)
	if (existingIndex === -1) {
		savedStatisticsViews.value = [...savedStatisticsViews.value, view]
		return
	}

	savedStatisticsViews.value = savedStatisticsViews.value.map((item) => item.id === view.id ? view : item)
}

function removeSavedStatisticsView(id: number): void {
	savedStatisticsViews.value = savedStatisticsViews.value.filter((item) => item.id !== id)
	if (route.name === 'statistics-view' && Number(route.params.id) === id) {
		void router.push({ name: 'statistics' })
	}
}

function openCloneSavedStatisticsView(view: SavedStatisticsView): void {
	savedStatisticsViewDialog.value = { mode: 'clone', view, initialConfiguration: null }
}

function openDeleteSavedStatisticsView(view: SavedStatisticsView): void {
	savedStatisticsViewDeleteCandidate.value = view
}

async function loadSavedStatisticsViews(): Promise<void> {
	try {
		savedStatisticsViews.value = await listSavedStatisticsViews()
	} catch {
		showError(t('health', 'Saved Statistics views could not be loaded.'))
	}
}

function navigateSavedStatisticsView(view: SavedStatisticsView): void {
	void router.push({ name: 'statistics-view', params: { id: view.id } })
}

watch(() => route.name, (name) => {
	if (name === 'donate') {
		supportOpen.value = true
	}
}, { immediate: true })

watch(() => route.name, (name) => {
	if (name === 'statistics' || name === 'statistics-view') {
		statisticsNavigationOpen.value = true
	}
}, { immediate: true })

onMounted(() => {
	void loadSavedStatisticsViews()
})
</script>

<template>
	<NcContent app-name="health">
		<NcAppNavigation :aria-label="t('health', 'Health views')">
			<template #list>
				<ul>
					<NcAppNavigationItem
						v-for="item in navigationItems"
						:key="item.key"
						:active="activeView === item.key"
						:allow-collapse="item.key === 'statistics' && savedStatisticsViews.length > 0"
						:icon="item.icon.startsWith('icon-') ? item.icon : undefined"
						:name="item.name"
						:open="item.key === 'statistics' && savedStatisticsViews.length > 0 ? statisticsNavigationOpen : false"
						@update:open="item.key === 'statistics' ? statisticsNavigationOpen = $event : undefined"
						@click.prevent="navigate(item.key)">
						<template v-if="item.icon === 'book' || item.icon === 'flag'" #icon>
							<NcIconSvgWrapper :path="item.icon === 'book' ? iconPaths.book : iconPaths.flag" />
						</template>
						<template v-if="item.key === 'statistics'" #default>
							<NcAppNavigationItem
								v-for="view in savedStatisticsViews"
								:key="view.id"
								:active="route.name === 'statistics-view' && Number(route.params.id) === view.id"
								force-menu
								:name="view.title"
								@click.prevent="navigateSavedStatisticsView(view)">
								<template #icon>
									<SavedStatisticsViewIcon :icon="view.icon" />
								</template>
								<template #actions>
									<NcActionButton close-after-click @click.stop="openCloneSavedStatisticsView(view)">
										<template #icon>
											<NcIconSvgWrapper :path="iconPaths.copy" />
										</template>
										{{ t('health', 'Clone') }}
									</NcActionButton>
									<NcActionButton close-after-click @click.stop="openDeleteSavedStatisticsView(view)">
										<template #icon>
											<NcIconSvgWrapper :path="iconPaths.delete" />
										</template>
										{{ t('health', 'Delete') }}
									</NcActionButton>
								</template>
							</NcAppNavigationItem>
						</template>
					</NcAppNavigationItem>
				</ul>
			</template>
			<template #footer>
				<ul>
					<NcAppNavigationItem
						:name="t('health', 'Support')"
						@click.prevent="supportOpen = true">
						<template #icon>
							<NcIconSvgWrapper :path="iconPaths.heartOutline" />
						</template>
					</NcAppNavigationItem>
					<NcAppNavigationItem
						:name="t('health', 'Settings')"
						@click.prevent="settingsOpen = true">
						<template #icon>
							<NcIconSvgWrapper :path="iconPaths.cog" />
						</template>
					</NcAppNavigationItem>
				</ul>
			</template>
		</NcAppNavigation>
		<NcAppContent>
			<RouterView />
		</NcAppContent>
		<NcAppSettingsDialog
			v-model:open="settingsOpen"
			:name="t('health', 'Health settings')"
			show-navigation
			no-version>
			<SettingsContent />
		</NcAppSettingsDialog>
		<SupportDialog
			:open="supportOpen"
			@update:open="(open) => open ? supportOpen = true : closeSupport()" />
		<SavedStatisticsViewDialog
			:configuration="configuration"
			:initial-configuration="savedStatisticsViewDialog?.initialConfiguration ?? null"
			:mode="savedStatisticsViewDialog?.mode ?? 'create'"
			:open="savedStatisticsViewDialog !== null"
			:view="savedStatisticsViewDialog?.view ?? null"
			@saved="upsertSavedStatisticsView"
			@update:open="(open) => { if (!open) savedStatisticsViewDialog = null }" />
		<SavedStatisticsViewDeleteDialog
			:open="savedStatisticsViewDeleteCandidate !== null"
			:view="savedStatisticsViewDeleteCandidate"
			@deleted="removeSavedStatisticsView"
			@update:open="(open) => { if (!open) savedStatisticsViewDeleteCandidate = null }" />
	</NcContent>
</template>

<style>
.health-page-title {
	margin: 0;
	font-size: 2rem;
	font-weight: var(--font-weight-bold);
	line-height: 1.2;
}
</style>
