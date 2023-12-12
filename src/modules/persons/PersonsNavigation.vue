<!--
	- @copyright Copyright (c) 2020 Florian Steffens <flost-dev@mailbox.org>
	-
	- @author Florian Steffens
	-
	- @license GNU AGPL version 3 or any later version
	-
	- This program is free software: you can redistribute it and/or modify
	- it under the terms of the GNU Affero General Public License as
	- published by the Free Software Foundation, either version 3 of the
	- License, or (at your option) any later version.
	-
	- This program is distributed in the hope that it will be useful,
	- but WITHOUT ANY WARRANTY; without even the implied warranty of
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
	-->

<template>
	<NcAppNavigation>
		<template #list>
			<NcAppNavigationItem v-for="(p, index) in getPersons"
				:key="index"
				:title="p.name"
				:allow-collapse="true"
				:open="index === 0"
				:editable="p.permissions['PERMISSION_MANAGE']"
				:edit-label="t('health', 'Edit name')"
				:class="(index === activePersonId)?'active-person':''"
				@update:menuOpen="menuOpenPersonId = index"
				@update:title="personUpdateName"
				@click="$store.dispatch('setActivePerson', index); $store.dispatch('setActiveModule', 'person')"
			>
				<template #icon>
					<IconPerson :size="20" />
				</template>
				<template v-if="p.permissions['PERMISSION_MANAGE']" #actions>
					<NcActionButton
						:aria-label="t('health', 'Show details')"
						:close-after-click="true"
						icon="icon-details"
						@click="$store.commit('showSidebar', true); $store.dispatch('setActivePerson', index)"
					>
						{{ t('health', 'Show details') }}
					</NcActionButton>
					<NcActionButton
						v-show="personsLength !== 1"
						:close-after-click="true"
						icon="icon-delete"
						@click="$store.dispatch('deletePerson', persons[menuOpenPersonId])"
					>
						{{ t('health', 'Delete') }}
					</NcActionButton>
				</template>
				<NcAppNavigationItem
					v-if="p.enabledModuleWeight"
					:title="t('health', 'Weight')"
					icon="icon-quota"
					:class="(activeModule === 'weight' && index === activePersonId)?'active-module':''"
					@click="$store.dispatch('setActivePerson', index); $store.dispatch('setActiveModule', 'weight')"
				/>
				<NcAppNavigationItem
					v-if="p.enabledModuleFeeling"
					:title="t('health', 'Feeling')"
					icon="icon-category-monitoring"
					:class="(activeModule === 'feeling' && index === activePersonId)?'active-module':''"
					@click="$store.dispatch('setActivePerson', index); $store.dispatch('setActiveModule', 'feeling')"
				/>
				<NcAppNavigationItem
					v-if="p.enabledModuleMeasurement"
					:title="t('health', 'Measurement')"
					icon="icon-home"
					:class="(activeModule === 'measurement' && index === activePersonId)?'active-module':''"
					@click="$store.dispatch('setActivePerson', index); $store.dispatch('setActiveModule', 'measurement')"
				/>
				<NcAppNavigationItem
					v-if="p.enabledModuleSleep"
					:title="t('health', 'Sleep')"
					icon="icon-download"
					:class="(activeModule === 'sleep' && index === activePersonId)?'active-module':''"
					@click="$store.dispatch('setActivePerson', index); $store.dispatch('setActiveModule', 'sleep')"
				/>
				<NcAppNavigationItem
					v-if="p.enabledModuleSmoking"
					:title="t('health', 'Smoking')"
					icon="icon-address"
					:class="(activeModule === 'smoking' && index === activePersonId)?'active-module':''"
					@click="$store.dispatch('setActivePerson', index); $store.dispatch('setActiveModule', 'smoking')"
				/>
				<NcAppNavigationItem
					v-if="p.enabledModuleActivities"
					:title="t('health', 'Activities')"
					icon="icon-upload"
					:class="(activeModule === 'activities' && index === activePersonId)?'active-module':''"
					@click="$store.dispatch('setActivePerson', index); $store.dispatch('setActiveModule', 'activities')"
				/>
				<NcAppNavigationItem
					v-if="p.enabledModuleMedicine"
					:title="t('health', 'Medication')"
					icon="icon-public"
					:class="(activeModule === 'medicine' && index === activePersonId)?'active-module':''"
					@click="$store.dispatch('setActivePerson', index); $store.dispatch('setActiveModule', 'medicine')"
				/>
			</NcAppNavigationItem>
			<NcAppNavigationNewItem
				:title="t('health', 'New person')"
				@new-item="createPerson"
			>
				<template #icon>
					<IconPlus :size="20" />
				</template>
			</NcAppNavigationNewItem>
		</template>
		<template #footer>
			<NcAppNavigationSettings :title="t('health', 'Information')">
				<NcAppNavigationItem
					:title="t('tables', 'Donations')"
					icon="icon-category-workflow"
					@click="openLink('https://github.com/datenangebot/health/wiki/Donations')"
				/>
			</NcAppNavigationSettings>
		</template>
	</NcAppNavigation>
</template>

<script>
import { NcAppNavigation, NcAppNavigationItem, NcActionButton, NcAppNavigationSettings, NcAppNavigationNewItem } from '@nextcloud/vue'
import { mapState, mapGetters } from 'vuex'
import IconPlus from 'vue-material-design-icons/Plus'
import IconPerson from 'vue-material-design-icons/Account.vue'

export default {
	name: 'PersonsNavigation',
	components: {
		NcAppNavigation,
		NcAppNavigationItem,
		NcActionButton,
		NcAppNavigationSettings,
		NcAppNavigationNewItem,
		IconPlus,
		IconPerson,
	},
	data() {
		return {
			menuOpenPersonId: 0,
			showNewPersonForm: false,
		}
	},
	computed: {
		...mapState(['activePersonId', 'activeModule', 'showSidebar', 'persons']),
		...mapGetters(['person', 'personsLength']),
		getPersons() {
			return this.persons && this.persons.length > 0
				// eslint-disable-next-line vue/no-side-effects-in-computed-properties
				? this.persons.sort(function(a, b) { return a.id - b.id })
				: null
		},
	},
	methods: {
		openLink(link) {
			window.open(link, '_blank')
		},
		createPerson(name) {
			this.$store.dispatch('addPerson', name)
			this.$store.commit('showSidebar', true)
		},
		personUpdateName(v) {
			this.$store.dispatch('setValue', { id: this.persons[this.menuOpenPersonId].id, key: 'name', value: v })
		},
		closeNewPersonForm() {
			this.$refs.newPersonName.value = ''
			this.showNewPersonForm = false
		},
	},
}
</script>
