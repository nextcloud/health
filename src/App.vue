<script setup lang="ts">
import type { HealthConfiguration } from './api/configuration.ts'

import { t } from '@nextcloud/l10n'
import { computed, provide, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcAppSettingsDialog from '@nextcloud/vue/components/NcAppSettingsDialog'
import NcContent from '@nextcloud/vue/components/NcContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import SettingsContent from './components/SettingsContent.vue'
import { healthConfigurationKey } from './configurationContext.ts'
import { iconPaths } from './icons.ts'
import { localDateKey } from './utils/dates.ts'
const route = useRoute()
const router = useRouter()

type ViewKey = 'journal' | 'goals' | 'statistics'
const settingsOpen = ref(false)
const configuration = ref<HealthConfiguration | null>(null)

provide(healthConfigurationKey, configuration)

const activeView = computed<ViewKey>(() => {
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
						:icon="item.icon.startsWith('icon-') ? item.icon : undefined"
						:name="item.name"
						@click.prevent="navigate(item.key)">
						<template v-if="item.icon === 'book' || item.icon === 'flag'" #icon>
							<NcIconSvgWrapper :path="item.icon === 'book' ? iconPaths.book : iconPaths.flag" />
						</template>
					</NcAppNavigationItem>
				</ul>
			</template>
			<template #footer>
				<ul>
					<NcAppNavigationItem
						:name="t('health', 'Settings')"
						icon="icon-settings"
						@click.prevent="settingsOpen = true" />
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
