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
	<div>
		<h2>Weight <span>for {{ personName }}</span></h2>
		<div v-if="weightTarget != ''">
			<h3>Target</h3>
			<div v-if="lastWeight">
				<p>You started with {{ weightTargetInitialWeight }}{{ weightUnit }} for your target. Your actual weight is now {{ lastWeight }}{{ weightUnit }} and your target values {{ weightTarget }}{{ weightUnit }}.</p>
				<p v-if="lastWeight > weightTarget && lastWeight < weightTargetInitialWeight">
					So you lost already {{ weightTargetInitialWeight - lastWeight }}{{ weightUnit }} and you have {{ lastWeight - weightTarget }}{{ weightUnit }} to go.
					<br>
					<br>
					Go on and eliminate the blue bar:
					<ProgressBar
						:value="getProgressbarValue"
						:class="{'small':true}" />
				</p>
				<p v-if="lastWeight > weightTarget && lastWeight > weightTargetInitialWeight">
					Ups, you become more and more. Be careful!
				</p>
				<p v-if="lastWeight <= weightTarget">
					Good, you reached your target!
				</p>
			</div>
			<div v-else>
				<p>Please insert your actual weight in the table.</p>
			</div>
		</div>
		<div v-else>
			If you want, you can set a target for your weight. Do this in the settings in the sidebar. You will see right here how much you lost and what is left. Maybe you can be motivated this way.
		</div>
		<h3>Chart</h3>
		<WeightChart
			:person="person"
			:data="person.weight.data" />
		<h3>Data</h3>
		<WeightTable />
		<p v-show="dataInsertInfo" class="dataInsertInfo">
			{{ dataInsertInfo }}
		</p>
	</div>
</template>

<script>
import ProgressBar from '@nextcloud/vue/dist/Components/ProgressBar'
import WeightTable from './WeightTable.vue'
import WeightChart from './WeightChart'
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'WeightContent',
	components: {
		ProgressBar,
		WeightTable,
		WeightChart,
	},
	data: function() {
		return {
			dataInsertInfo: '',
			dataTableHighlight: null,
			chartDateRange: 'week',
		}
	},
	computed: {
		...mapState(['activeModule', 'showSidebar']),
		...mapGetters(['person', 'lastWeight', 'weightTarget', 'weightTargetInitialWeight', 'weightUnit', 'weightMeasurementName', 'personName']),
		getProgressbarValue: function() {
			const lostAlready = this.weightTargetInitialWeight - this.lastWeight
			const wantToLost = this.weightTargetInitialWeight - this.weightTarget
			const result = 100 - ((lostAlready / wantToLost) * 100)
			return (result > 100) ? 100 : result
		},
		data: function() {
			return this.person.weight.data
		},
	},
	methods: {
	},
}
</script>
<style lang="scss" scoped>
	.progress-bar.small {
		width: 35%;
		height: 6px !important;
	}
</style>
