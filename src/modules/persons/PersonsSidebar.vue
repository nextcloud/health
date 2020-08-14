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
	<ul>
		<li><h4>Age</h4></li>
		<ActionInput
			type="number"
			:value="person.age"
			icon="icon-user"
			@submit="updateAge" />
		<li><h4>Size<span>in cm</span></h4></li>
		<ActionInput
			type="number"
			:value="person.size"
			icon="icon-fullscreen"
			@submit="updateSize" />
		<li><h4>Sex</h4></li>
		<ActionRadio
			name="sex"
			value="female"
			:checked="person.sex === 'female'"
			@change="updateSex('female')">
			female
		</ActionRadio>
		<ActionRadio
			name="sex"
			value="male"
			:checked="person.sex === 'male'"
			@change="updateSex('male')">
			male
		</ActionRadio>
		<li><h4>Manage modules</h4></li>
		<ActionCheckbox
			:checked="person.enabledModules.weight"
			value="weight"
			@change="updateEnabledModules">
			Weight
		</ActionCheckbox>
		<ActionCheckbox
			:checked="person.enabledModules.breaks"
			value="breaks"
			@change="updateEnabledModules">
			Breaks
		</ActionCheckbox>
		<ActionCheckbox
			:checked="person.enabledModules.tracking"
			value="tracking"
			@change="updateEnabledModules">
			Tracking
		</ActionCheckbox>
	</ul>
</template>

<script>
import ActionRadio from '@nextcloud/vue/dist/Components/ActionRadio'
import ActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox'
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'

export default {
	name: 'PersonsSidebar',
	components: {
		ActionCheckbox,
		ActionRadio,
		ActionInput,
	},
	props: {
		person: {
			type: Object,
			default: null,
		},
	},
	data: function() {
		return {
		}
	},
	methods: {
		updateAge: function(e) {
			const p = this.person
			p.age = e.target[1].value
			this.$emit('update:person', p)
		},
		updateSize: function(e) {
			const p = this.person
			p.size = e.target[1].value
			this.$emit('update:person', p)
		},
		updateSex: function(e) {
			const p = this.person
			p.sex = e
			this.$emit('update:person', p)
		},
		updateEnabledModules: function(e) {
			const p = this.person
			if (e.target.value === 'weight') {
				p.enabledModules.weight = !p.enabledModules.weight
			} else if (e.target.value === 'breaks') {
				p.enabledModules.breaks = !p.enabledModules.breaks
			} else if (e.target.value === 'tracking') {
				p.enabledModules.tracking = !p.enabledModules.tracking
			}
			this.$emit('update:person', p)
		},
	},
}
</script>
<style lang="scss">
	li h4 {
		margin-top: 10px;
	}
	h4 span {
		opacity: .7;
		font-size: 0.8em;
		margin-left: 5px;
	}
</style>
