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
				@click="$emit('update:activePersonId', index); $emit('update:activeModule', 'persons')">
				<template slot="actions">
					<ActionButton
						:close-after-click="true"
						icon="icon-detail"
						@click="$emit('update:showSidebar', true); $emit('update:activePersonId', index)">
						Show details
					</ActionButton>
					<ActionButton
						:close-after-click="true"
						icon="icon-delete"
						@click="personDelete">
						Delete
					</ActionButton>
				</template>
				<AppNavigationItem
					v-if="persons[index].enabledModules.weight"
					title="Weight"
					icon="icon-quota"
					@click="$emit('update:activePersonId', index); $emit('update:activeModule', 'weight')" />
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
	name: 'PersonNavigation',
	components: {
		AppNavigation,
		AppNavigationItem,
		ActionButton,
		Actions,
	},
	props: {
		persons: {
			type: Array,
			default: null,
		},
		activePersonId: {
			type: Number,
			default: 0,
		},
		activeModule: {
			type: String,
			default: 'persons',
		},
		showSidebar: {
			type: Boolean,
		},
		notifications: {
			type: Array,
			default: null,
		},
	},
	data: function() {
		return {
			menuOpenPersonId: 0,
			showNewPersonForm: false,
		}
	},
	methods: {
		createPerson: function(e) {
			const p = this.persons
			const newId = this.persons.length + 1
			p.push({
				id: newId,
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
					target: null,
					targetInitialWeight: null,
					unit: 'kg',
				},
			})
			this.showNewPersonForm = false
			this.$emit('update:showSidebar', true)
			this.$emit('update:activePersonId', this.persons.length - 1)
			e.currentTarget.childNodes[0].value = ''
		},
		personDelete(e) {
			if (this.persons.length === 1) {
				this.notifications.push(
					{
						type: 'warn',
						text: 'You can not delete the last person.',
					})
			} else {
				const p = this.persons
				p.splice(this.menuOpenPersonId, 1)
				this.$emit('update:persons', p)
			}
		},
		personUpdateName: function(v) {
			const p = this.persons
			p[this.menuOpenPersonId].name = v
			this.$emit('update:persons', p)
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
