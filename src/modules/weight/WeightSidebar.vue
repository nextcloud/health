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
		<li><h4>Unit for weight</h4></li>
		<ActionInput
			type="text"
			:value="person.weight.unit"
			icon="icon-category-customization"
			@submit="updateWeightUnit" />
		<li>
			<h4>
				Weight target<span>in {{ person.weight.unit }}<br>blank for none<br>On update the initial weight for the target will be ???</span>
			</h4>
		</li>
		<ActionInput
			type="number"
			:value="person.weight.weightTarget"
			icon="icon-category-monitoring"
			@submit="updateWeightTarget" />
		<li>
			<h4>
				Weight target start weight<span>in {{ person.weight.unit }}<br>your reference weight for the target</span>
			</h4>
		</li>
		<ActionInput
			type="number"
			:value="(person.weight.weightTargetInitialWeight !== null )? person.weight.weightTargetInitialWeight: lastWeight"
			icon="icon-quota"
			@submit="updateWeightTargetInitialWeight" />
	</ul>
</template>

<script>
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'

export default {
	name: 'WeightSidebar',
	components: {
		ActionInput,
	},
	props: {
		person: {
			type: Object,
			default: null,
		},
		lastWeight: {
			type: Number,
			default: 0,
		},
	},
	data: function() {
		return {
		}
	},
	methods: {
		updateWeightUnit: function(e) {
			const p = this.person
			p.weight.unit = e.target[1].value
			this.$emit('update:person', p)
		},
		updateWeightTarget: function(e) {
			const p = this.person
			p.weight.weightTarget = e.target[1].value
			this.$emit('update:person', p)
		},
		updateWeightTargetInitialWeight: function(e) {
			const p = this.person
			p.weight.weightTargetInitialWeight = e.target[1].value
			this.$emit('update:person', p)
		},
	},
}
</script>
<style lang="scss" scoped>
</style>
