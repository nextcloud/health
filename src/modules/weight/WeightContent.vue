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
		<h2 class="first-h2">
			Weight <span>for {{ person.name }}</span>
		</h2>
		<div>
			<WeightBmi
				:size="person.size"
				:age="person.age"
				:weight="lastWeight" />
		</div>
		<div v-if="person.weightTarget != '' && person.weightTarget != null">
			<h3>Target</h3>
			<div v-if="lastWeight">
				<p>You started with {{ person.weightTargetInitialWeight }}{{ person.weightUnit }} for your target. Your actual weight is now {{ lastWeight }}{{ person.weightUnit }} and your target values {{ person.weightTarget }}{{ person.weightUnit }}.</p>
				<p v-if="lastWeight > person.weightTarget && lastWeight < person.weightTargetInitialWeight">
					So you lost already {{ person.weightTargetInitialWeight - lastWeight }}{{ person.weightUnit }} and you have {{ lastWeight - person.weightTarget }}{{ person.weightUnit }} to go.
					<br>
					<br>
					Go on and eliminate the blue bar:
					<ProgressBar
						:value="getProgressbarValue"
						:class="{'small':true}" />
				</p>
				<p v-if="lastWeight > person.weightTarget && lastWeight > person.weightTargetInitialWeight">
					Ups, you become more and more. Be careful!
				</p>
				<p v-if="lastWeight <= person.weightTarget" class="green">
					Good, you reached your target!
				</p>
				<p v-if="lastWeight <= person.weightTarget" class="bountybox">
					{{ person.weightTargetBounty }}
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
			v-show="weightData"
			:person="person"
			:data="weightData" />
		<h3>Data</h3>
		<WeightTable
			v-show="weightData" />
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
import WeightBmi from './WeightBmi'

export default {
	name: 'WeightContent',
	components: {
		ProgressBar,
		WeightTable,
		WeightChart,
		WeightBmi,
	},
	data: function() {
		return {
			dataInsertInfo: '',
			dataTableHighlight: null,
			chartDateRange: 'week',
		}
	},
	computed: {
		...mapState(['activeModule', 'showSidebar', 'weightData']),
		...mapGetters(['person']),
		getProgressbarValue: function() {
			const lostAlready = this.person.weightTargetInitialWeight - this.lastWeight
			const wantToLost = this.person.weightTargetInitialWeight - this.person.weightTarget
			const result = 100 - ((lostAlready / wantToLost) * 100)
			return (result > 100) ? 100 : result
		},
		lastWeight: function() {
			if (this.weightData && this.weightData.length > 1) {
				return this.weightData[0].weight
			}
			return null
		},
	},
}
</script>
<style lang="scss" scoped>
	.progress-bar.small {
		width: 35%;
		height: 6px !important;
	}
	.green {
		color: green;
		font-weight: 500;
		padding-left: 20px;
		padding-right: 20px;
		padding-top: 20px;
	}
	.bountybox {
		padding: 10px;
		border: 1px solid #80808073;
		margin: 20px;
		width: min-content;
	}
	.first-h3 {
		margin-top: 0px;
	}
	.first-h2 {
		margin-top: 40px;
	}
</style>
