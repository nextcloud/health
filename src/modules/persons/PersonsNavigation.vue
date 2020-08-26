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
			<AppNavigationItem v-for="(person, index) in persons"
				:key="index"
				:title="person.name"
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
						@click="$store.commit('showSidebar', true)">
						Show details
					</ActionButton>
					<ActionButton
						v-show="!isLastPerson"
						:close-after-click="true"
						icon="icon-delete"
						@click="$store.commit('deletePerson', menuOpenPersonId)">
						Delete
					</ActionButton>
				</template>
				<AppNavigationItem
					v-if="persons[index].enabledModules.weight"
					title="Weight"
					icon="icon-quota"
					@click="$store.commit('activePersonId', index); $store.commit('activeModule', 'weight')" />
				<AppNavigationItem
					v-if="persons[index].enabledModules.breaks"
					title="Breaks"
					icon="icon-pause"
					@click="$store.commit('activePersonId', index); $store.commit('activeModule', 'breaks')" />
				<AppNavigationItem
					v-if="persons[index].enabledModules.tracking"
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
		persons: function() {
			return this.$store.state.persons
		},
		activePersonId: function() {
			return this.$store.state.activePersonId
		},
		activeModule: function() {
			return this.$store.state.activeModule
		},
		showSidebar: function() {
			return this.$store.state.showSidebar
		},
		isLastPerson: function() {
			return (this.$store.state.persons.length === 1)
		},
	},
	methods: {
		createPerson: function(e) {
			const p = {
				id: null,
				name: e.currentTarget.childNodes[0].value,
				age: null,
				enabledModules: {
					weight: true,
					breaks: false,
					tracking: false,
				},
				notifications: null,
				sex: null,
				size: null,
				weight: {
					data: null,
					weightTarget: null,
					weightTargetInitialWeight: null,
					unit: 'kg',
					lastWeight: null,
				},
			}
			this.$store.dispatch('addPerson', p)
			this.$store.commit('showSidebar', true)
			this.showNewPersonForm = false
			e.currentTarget.childNodes[0].value = ''
		},
		personUpdateName: function(v) {
			this.$store.commit('updatePersonName', { id: this.menuOpenPersonId, name: v })
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
