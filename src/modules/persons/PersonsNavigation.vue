<!--
	- @copyright Copyright (c) 2019 Florian Steffens
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
	<AppNavigation>
		<template #list>
			<AppNavigationItem v-for="(p, index) in persons"
				:key="index"
				:title="p.name"
				:allow-collapse="true"
				:open="(index === 0)?true:false"
				icon="icon-user"
				:editable="true"
				:edit-label="t('health', 'edit name')"
				:class="(index === activePersonId)?'active-person':''"
				@update:menuOpen="menuOpenPersonId = index"
				@update:title="personUpdateName"
				@click="$store.dispatch('setActivePerson', index); $store.dispatch('setActiveModule', 'person')">
				<template slot="actions">
					<ActionButton
						:close-after-click="true"
						icon="icon-details"
						@click="$store.commit('showSidebar', true); $store.dispatch('setActivePerson', index)">
						{{ t('health', 'Show details') }}
					</ActionButton>
					<ActionButton
						v-show="personsLength != 1"
						:close-after-click="true"
						icon="icon-delete"
						@click="$store.dispatch('deletePerson', menuOpenPersonId)">
						{{ t('health', 'Delete') }}
					</ActionButton>
				</template>
				<AppNavigationItem
					v-if="p.enabledModuleWeight"
					:title="t('health', 'Weight')"
					icon="icon-quota"
					:class="(activeModule === 'weight' && index === activePersonId)?'active-module':''"
					@click="$store.dispatch('setActivePerson', index); $store.dispatch('setActiveModule', 'weight')" />
				<AppNavigationItem
					v-if="p.enabledModuleBreaks"
					:title="t('health', 'Breaks')"
					icon="icon-pause"
					:class="(activeModule === 'breaks' && index === activePersonId)?'active-module':''"
					@click="$store.dispatch('setActivePerson', index); $store.dispatch('setActiveModule', 'breaks')" />
				<AppNavigationItem
					v-if="p.enabledModuleFeeling"
					:title="t('health', 'Feeling')"
					icon="icon-category-monitoring"
					:class="(activeModule === 'feeling' && index === activePersonId)?'active-module':''"
					@click="$store.dispatch('setActivePerson', index); $store.dispatch('setActiveModule', 'feeling')" />
				<AppNavigationItem
					v-if="p.enabledModuleMedicin"
					:title="t('health', 'Medicin')"
					icon="icon-toggle-filelist"
					:class="(activeModule === 'medicin' && index === activePersonId)?'active-module':''"
					@click="$store.dispatch('setActivePerson', index); $store.dispatch('setActiveModule', 'medicin')" />
				<AppNavigationItem
					v-if="p.enabledModuleActivities"
					:title="t('health', 'Activities')"
					icon="icon-user-admin"
					:class="(activeModule === 'activities' && index === activePersonId)?'active-module':''"
					@click="$store.dispatch('setActivePerson', index); $store.dispatch('setActiveModule', 'activities')" />
			</AppNavigationItem>
			<AppNavigationItem
				:title="t('health', 'New person')"
				icon="icon-add"
				:pinned="false"
				@click.prevent.stop="showNewPersonForm = true">
				<div v-show="showNewPersonForm" class="person-create">
					<form
						@submit.prevent.stop="createPerson">
						<input
							ref="newPersonName"
							:placeholder="t('health', 'Name')"
							type="text"
							required>
						<input type="submit" value="" class="icon-confirm">
						<Actions>
							<ActionButton class="ab-integrated" icon="icon-close" @click.stop.prevent="closeNewPersonForm" />
						</Actions>
					</form>
				</div>
			</AppNavigationItem>
		</template>
	</AppNavigation>
</template>

<script>
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'PersonsNavigation',
	components: {
		AppNavigation,
		AppNavigationItem,
		ActionButton,
		Actions,
	},
	data: function() {
		return {
			menuOpenPersonId: 0,
			showNewPersonForm: false,
		}
	},
	computed: {
		...mapState(['activePersonId', 'activeModule', 'showSidebar', 'persons']),
		...mapGetters(['person', 'personsLength']),
	},
	methods: {
		createPerson: function(e) {
			const name = e.currentTarget.childNodes[0].value
			this.$store.dispatch('addPerson', name)
			this.$store.commit('showSidebar', true)
			this.showNewPersonForm = false
			e.currentTarget.childNodes[0].value = ''
		},
		personUpdateName: function(v) {
			this.$store.dispatch('setValue', { id: this.menuOpenPersonId, key: 'name', value: v })
		},
		closeNewPersonForm: function() {
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
	.active-person {
		background-color: var(--color-background-dark);
	}
	.active-person:hover {
		background-color: var(--color-background-darker);
	}
	.active-module {
		background-color: var(--color-background-darker);
	}
	.active-module:hover {
		background-color: var(--color-background-darker);
	}
	.app-navigation-entry:hover {
		background-color: var(--color-background-darker);
	}
</style>
