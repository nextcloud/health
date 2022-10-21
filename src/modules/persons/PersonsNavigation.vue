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
				:open="(index === 0)?true:false"
				icon="icon-user"
				:editable="p.permissions['PERMISSION_MANAGE']"
				:edit-label="t('health', 'Edit name')"
				:class="(index === activePersonId)?'active-person':''"
				@update:menuOpen="menuOpenPersonId = index"
				@update:title="personUpdateName"
				@click="$store.dispatch('setActivePerson', index); $store.dispatch('setActiveModule', 'person')"
			>
				<template v-if="p.permissions['PERMISSION_MANAGE']" slot="actions">
					<NcActionButton
						:close-after-click="true"
						icon="icon-details"
						@click="$store.commit('showSidebar', true); $store.dispatch('setActivePerson', index)"
					>
						{{ t('health', 'Show details') }}
					</NcActionButton>
					<NcActionButton
						v-show="personsLength != 1"
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
			<NcAppNavigationItem
				:title="t('health', 'New person')"
				icon="icon-add"
				:pinned="false"
				@click.prevent.stop="showNewPersonForm = true"
			>
				<div v-show="showNewPersonForm" class="person-create">
					<form
						@submit.prevent.stop="createPerson"
					>
						<input
							ref="newPersonName"
							:placeholder="t('health', 'Name')"
							type="text"
							required
						>
						<input type="submit" value="" class="icon-confirm">
						<NcActions>
							<NcActionButton class="ab-integrated" icon="icon-close" @click.stop.prevent="closeNewPersonForm" />
						</NcActions>
					</form>
				</div>
			</NcAppNavigationItem>
		</template>
		<template #footer>
			<AppNavigationSettings :title="t('health', 'Information')">
				<NcAppNavigationItem
					:title="t('tables', 'Donations')"
					icon="icon-category-workflow"
					@click="openLink('https://github.com/datenangebot/health/wiki/Donations')"
				/>
			</AppNavigationSettings>
		</template>
	</NcAppNavigation>
</template>

<script>
import NcAppNavigation from '@nextcloud/vue/dist/Components/NcAppNavigation'
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton'
import NcActions from '@nextcloud/vue/dist/Components/NcActions'
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'PersonsNavigation',
	components: {
		NcAppNavigation,
		NcAppNavigationItem,
		NcActionButton,
		NcActions,
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
		createPerson(e) {
			const name = e.currentTarget.childNodes[0].value
			this.$store.dispatch('addPerson', name)
			this.$store.commit('showSidebar', true)
			this.showNewPersonForm = false
			e.currentTarget.childNodes[0].value = ''
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
<style lang="scss" scoped>

	.person-create {
		order: 1;
		display: flex;
		height: 44px;
		padding-left: 10px;
		form {
			display: flex;
			flex-grow: 1;
			input[type='text'] {
				flex-grow: 1;
			}
		}
	}

	button, .button, input[type='button'], input[type='submit'], input[type='reset'] {
		min-height: auto;
		border-radius: var(--border-radius);
		padding: 4px;
	}

	.app-navigation__list {
		display: none;
	}

	.app-navigation-entry:hover {
		background-color: var(--color-background-darker);
	}

</style>
