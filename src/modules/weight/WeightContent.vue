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
		<h2>Weight<span>for {{ person.name }}</span></h2>
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
		<div class="chartDataRangeSelector">
			<select
				id="chartDataRangeSelector"
				v-model="chartDateRange"
				name="chartDataRangeSelector">
				<option value="week">
					Show chart for the last week
				</option>
				<option value="month">
					Show chart for the last month
				</option>
				<option value="year">
					Show chart for the last year
				</option>
				<option value="all">
					Show all
				</option>
			</select>
		</div>
		<LineChart
			v-show="getChartData.datasets[0].data.length !== 0"
			:chart-data="getChartData"
			:height="200"
			:range="chartDateRange" />
		<p v-show="getChartData.datasets[0].data.length === 0">
			No data to show you :(
		</p>
		<h3>Data</h3>
		<p v-show="dataInsertInfo" class="dataInsertInfo">
			{{ dataInsertInfo }}
		</p>
		<WeightTable :data.sync="data" :measurement-name="person.weight.measurementName" :weight-unit="person.weight.unit" />
	</div>
</template>

<script>
import ProgressBar from '@nextcloud/vue/dist/Components/ProgressBar'
// import VueTableDynamic from 'vue-table-dynamic'
import LineChart from './LineChart.js'
import WeightTable from './WeightTable.vue'
import moment from '@nextcloud/moment'

export default {
	name: 'WeightContent',
	components: {
		ProgressBar,
		// VueTableDynamic,
		LineChart,
		WeightTable,
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
		getChartData: function() {
			const data = []
			for (let i = 0; i < this.data.length; i++) {
				if (this.data[i].weight !== '' && this.data[i].weight !== null) {
					console.debug(Math.abs(moment(this.data[i].date).diff(moment(), 'days')))
					let diff = null
					if (this.chartDateRange === 'week') {
						diff = 7
					} else if (this.chartDateRange === 'month') {
						diff = 31
					} else if (this.chartDateRange === 'year') {
						diff = 365
					}
					if (diff === null || Math.abs(moment(this.data[i].date).diff(moment(), 'days')) <= diff) {
						data.push({
							t: moment(this.data[i].date),
							y: this.data[i].weight,
						})
					}
				}
			}
			return {
				datasets: [
					{
						label: 'Weight',
						backgroundColor: '#ffeeee',
						borderColor: 'red',
						fill: false,
						data: data,
					},
				],
			}
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
</style>
