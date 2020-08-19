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
	<div class="weight-chart">
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
		<EmptyContent
			v-show="getChartData.datasets[0].data.length === 0"
			icon="icon-category-monitoring">
			No data for a chart
			<template #desc>
				More than one dataset is required.
			</template>
		</EmptyContent>
	</div>
</template>

<script>
import LineChart from './LineChart.js'
import moment from '@nextcloud/moment'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'

export default {
	name: 'WeightChart',
	components: {
		LineChart,
		EmptyContent,
	},
	props: {
		person: {
			type: Object,
			default: null,
		},
		data: {
			type: Array,
			default: null,
		},
	},
	data: function() {
		return {
			chartDateRange: 'week',
		}
	},
	computed: {
		getChartData: function() {
			const data = []
			const targetData = []
			for (let i = 0; i < this.data.length; i++) {
				if (this.data[i].weight !== '' && this.data[i].weight !== null) {
					// console.debug(Math.abs(moment(this.data[i].date).diff(moment(), 'days')))
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
						targetData.push({
							t: moment(this.data[i].date),
							y: this.person.weight.weightTarget,
						})
					}
				}
			}
			return {
				datasets: [
					{
						label: 'Weight',
						borderColor: 'red',
						fill: false,
						data: data,
					},
					{
						label: 'Target',
						borderColor: 'gray',
						fill: false,
						data: targetData,
						borderDash: [
							2,
							5,
						],
					},
				],
			}
		},
	},
	methods: {
	},
}
</script>
<style lang="scss" scoped>
	.empty-content {
		margin-top: 0px !important;
		margin-bottom: 20px;
	}
</style>
