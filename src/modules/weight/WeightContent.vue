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
		<h2>Weight <span>for {{ person.name }}</span></h2>
		<div v-if="person.weight.weightTarget != ''">
			<h3>Target</h3>
			<p>You started with {{ person.weight.weightTargetInitialWeight }}{{ person.weight.unit }} for your target. Your actual weight is now {{ getLastWeight }}{{ person.weight.unit }} and your target values {{ person.weight.weightTarget }}{{ person.weight.unit }}.</p>
			<p
				v-if="getLastWeight - person.weight.weightTarget >= 0">
				So you lost already {{ person.weight.weightTargetInitialWeight - getLastWeight }}{{ person.weight.unit }} and you have {{ getLastWeight - person.weight.weightTarget }}{{ person.weight.unit }} to go.
			</p>
			<p
				v-if="getLastWeight - person.weight.weightTarget >= 0">
				Go on and eliminate the blue bar:
				<ProgressBar
					:value="getProgressbarValue"
					:class="{'small':true}" />
			</p>
			<p
				v-else>
				Good, you reached your target!
			</p>
		</div>
		<div v-else>
			If you want, you can set a target for your weight. Do this in the settings in the sidebar. You will see right here how much you lost and what is left. Maybe you can be motivated this way.
		</div>
		<h3>Chart</h3>
		<WeightChart
			:person="person"
			:data="data" />
		<h3>Data</h3>
		<p v-show="dataInsertInfo" class="dataInsertInfo">
			{{ dataInsertInfo }}
		</p>
		<WeightTable :data.sync="data" :measurement-name="person.weight.measurementName" :weight-unit="person.weight.unit" />
	</div>
</template>

<script>
import ProgressBar from '@nextcloud/vue/dist/Components/ProgressBar'
import WeightTable from './WeightTable.vue'
import WeightChart from './WeightChart'

export default {
	name: 'WeightContent',
	components: {
		ProgressBar,
		WeightTable,
		WeightChart,
	},
	props: {
		person: {
			type: Object,
			default: null,
		},
	},
	data: function() {
		return {
			dataInsertInfo: '',
			dataTableHighlight: null,
			chartDateRange: 'week',
			data: [
				{
					date: '2020-08-01',
					weight: 80,
					measurement: 30,
					bodyfat: 20,
				},
				{
					date: '2020-08-02',
					weight: 80,
					measurement: 30,
					bodyfat: 17,
				},
				{
					date: '2020-08-03',
					weight: 82,
					measurement: 29,
					bodyfat: 17,
				},
				{
					date: '2020-08-04',
					weight: 81,
					measurement: 15,
					bodyfat: 16,
				},
				{
					date: '2020-08-05',
					weight: 79.5,
					measurement: 16,
					bodyfat: 15,
				},
			],
		}
	},
	computed: {
		getLastWeight: function() {
			return 80
		},
		getProgressbarValue: function() {
			return 40
		},
	},
	methods: {
		changeCell: function(rowIndex, columnIndex, value) {
			const dataIndex = rowIndex - 1
			console.debug('cell change on row/column/value: ' + rowIndex + ' ' + columnIndex + ' ' + value)
			if (columnIndex === 0) {
				this.data[dataIndex].date = value
			} else if (columnIndex === 1) {
				this.data[dataIndex].weight = value
			} else if (columnIndex === 2) {
				if (this.person.weight.measurementName) {
					this.data[dataIndex].measurement = value
				} else {
					this.data[dataIndex].bodyfat = value
				}
			} else if (columnIndex === 3) {
				this.data[dataIndex].bodyfat = value
			}
		},
	},
}
</script>
<style lang="scss" scoped>
	.progress-bar.small {
		width: 35%;
	}
	h3 {
	    font-size: 20px;
	    margin-top: 40px;
	}
</style>
