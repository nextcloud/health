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
			:value="person.weightUnit"
			icon="icon-category-customization"
			@submit="updateWeightUnit" />
		<li>
			<h4>
				Weight target<span>in {{ person.weightUnit }}<br>blank for none<br>On update the initial weight for the target will be ???</span>
			</h4>
		</li>
		<ActionInput
			type="number"
			:value="person.weightTarget"
			icon="icon-category-monitoring"
			@submit="updateWeightTarget" />
		<li>
			<h4>
				Weight target start weight<span>in {{ person.weightUnit }}<br>your reference weight for the target</span>
			</h4>
		</li>
		<ActionInput
			type="number"
			:value="(person.weightTargetInitialWeight !== null )? person.weightTargetInitialWeight: lastWeight"
			icon="icon-quota"
			@submit="updateWeightTargetInitialWeight" />
		<li>
			<h4>
				Measurement Name<span><br>What else do you want to track? Set here a name for it and you can add data in the data-table. The values have to be numbers.</span>
			</h4>
		</li>
		<ActionInput
			type="text"
			:value="person.weightMeasurementName"
			icon="icon-rename"
			@submit="updateMeasurementName" />
	</ul>
</template>

<script>
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'WeightSidebar',
	components: {
		ActionInput,
	},
	computed: {
		...mapState(['activeModule', 'showSidebar']),
		...mapGetters(['person', 'lastWeight']),
	},
	methods: {
		updateWeightUnit: function(e) {
			this.$store.commit('updateWeightUnit', e.target[1].value)
		},
		updateWeightTarget: function(e) {
			this.$store.commit('updateWeightTarget', e.target[1].value)
		},
		updateWeightTargetInitialWeight: function(e) {
			this.$store.commit('updateWeightTargetInitialWeight', e.target[1].value)
		},
		updateMeasurementName: function(e) {
			this.$store.commit('updateWeightMeasurementName', e.target[1].value)
		},
	},
}
</script>
<style lang="scss" scoped>
</style>
