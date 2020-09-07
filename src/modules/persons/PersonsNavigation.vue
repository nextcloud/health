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
		<ul>
			<AppNavigationItem v-for="(p, index) in persons"
				:key="index"
				:title="p.name"
				:allow-collapse="true"
				:open="(index === 0)?true:false"
				icon="icon-user"
				:editable="true"
				edit-label="edit name"
				@update:menuOpen="menuOpenPersonId = index"
				@update:title="personUpdateName"
				@click="$store.commit('activePersonId', index); $store.commit('activeModule', 'person')">
				<template slot="actions">
					<ActionButton
						:close-after-click="true"
						icon="icon-detail"
						@click="$store.commit('showSidebar', true); $store.commit('activePersonId', index)">
						Show details
					</ActionButton>
					<ActionButton
						v-show="personsLength != 1"
						:close-after-click="true"
						icon="icon-delete"
						@click="$store.dispatch('deletePerson', menuOpenPersonId)">
						Delete
					</ActionButton>
				</template>
				<AppNavigationItem
					v-if="p.enabledModuleWeight"
					title="Weight"
					icon="icon-quota"
					@click="$store.commit('activePersonId', index); $store.commit('activeModule', 'weight')" />
				<AppNavigationItem
					v-if="p.enabledModuleBreaks"
					title="Breaks"
					icon="icon-pause"
					@click="$store.commit('activePersonId', index); $store.commit('activeModule', 'breaks')" />
				<AppNavigationItem
					v-if="p.enabledModuleTracking"
					title="Tracking"
					icon="icon-category-monitoring"
					@click="$store.commit('activePersonId', index); $store.commit('activeModule', 'tracking')" />
			</AppNavigationItem>
		</ul>
		<ul>
			<AppNavigationItem
				title="New person"
				icon="icon-add"
				:pinned="true"
				@click.prevent.stop="showNewPersonForm = true">
				<div v-show="showNewPersonForm" class="person-create">
					<form
						@submit.prevent.stop="createPerson">
						<input
							ref="newPersonName"
							:placeholder="'Name'"
							type="text"
							required>
						<input type="submit" value="" class="icon-confirm">
						<Actions>
							<ActionButton class="ab-integrated" icon="icon-close" @click.stop.prevent="closeNewPersonForm" />
						</Actions>
					</form>
				</div>
			</AppNavigationItem>
		</ul>
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
		...mapState(['activePersonId', 'activeModule', 'showSidebar']),
		...mapGetters(['person', 'personsLength', 'persons']),
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
			this.$store.dispatch('updatePerson', { id: this.menuOpenPersonId, key: 'name', value: v })
			// this.$store.commit('updatePersonName', { id: this.menuOpenPersonId, name: v })
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
</style>
