<!--
	- @copyright Copyright (c) 2020 Florian Steffens <flost-dev@mailbox.org>
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
		<div
			v-if="data && getChartData.datasets[0].data.length > 1"
			class="chartDataRangeSelector">
			<select
				id="chartDataRangeSelector"
				v-model="chartDateRange"
				name="chartDataRangeSelector">
				<option value="week">
					{{ t('health', 'Show chart for the last week') }}
				</option>
				<option value="month">
					{{ t('health', 'Show chart for the last month') }}
				</option>
				<option value="year">
					{{ t('health', 'Show chart for the last year') }}
				</option>
				<option value="all">
					{{ t('health', 'Show all') }}
				</option>
			</select>
		</div>
		<LineChart
			v-if="data && getChartData.datasets[0].data.length > 1"
			:height="200"
			:chart-data="getChartData"
			:range="chartDateRange" />
		<EmptyContent
			v-if="data && getChartData.datasets[0].data.length <= 1"
			icon="icon-category-monitoring">
			No data for a chart
			<template #desc>
				{{ t('health', 'More than one dataset is required.') }}<br>
				<span v-if="data.length > 1">{{ t('health', 'You selected to show only data from the last {chartDateRange}.', {chartDateRange: chartDateRange}) }}</span>
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
			chartDateRange: 'month',
		}
	},
	computed: {
		chartStyles: function() {
			return {
				height: '200px',
				position: 'relative',
			}
		},
		getChartData: function() {
			const data = []
			const targetData = []
			const targetInitialData = []
			const measurement = []
			const bodyfat = []
			if (this.data !== null && this.data !== undefined) {
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
								y: this.person.weightTarget,
							})
							targetInitialData.push({
								t: moment(this.data[i].date),
								y: this.person.weightTargetInitialWeight,
							})
							measurement.push({
								t: moment(this.data[i].date),
								y: this.data[i].measurement,
							})
							bodyfat.push({
								t: moment(this.data[i].date),
								y: this.data[i].bodyfat,
							})
						}
					}
				}
			}
			return {
				datasets: [
					{
						label: t('health', 'weight'),
						borderColor: '#0082c9',
						fill: false,
						data: data,
						yAxisID: 'weight',
					},
					{
						label: t('health', 'target'),
						borderColor: 'green',
						fill: false,
						data: targetData,
						borderDash: [
							8,
							5,
						],
						yAxisID: 'weight',
					},
					{
						label: t('health', 'target initial weight'),
						borderColor: 'darkred',
						fill: false,
						data: targetInitialData,
						borderDash: [
							2,
							5,
						],
						yAxisID: 'weight',
					},
					{
						label: this.person.weightMeasurementName,
						backgroundColor: 'lightgray',
						// borderColor: 'gray',
						borderWidth: 1,
						fill: true,
						data: measurement,
						type: 'bar',
						yAxisID: 'percent',
					},
					{
						label: t('health', 'bodyfat'),
						backgroundColor: 'gray',
						// borderColor: 'darkgray',
						borderWidth: 1,
						fill: false,
						data: bodyfat,
						type: 'bar',
						yAxisID: 'percent',
					},
				],
			}
		},
	},
}
</script>
<style lang="scss" scoped>
	.empty-content {
		margin-top: 0px !important;
		margin-bottom: 20px;
	}

	.chartLegend {
		font-size: 0.8em;
	}

	.chartLegend .green {
		font-weight: bold;
		color: green;
	}

	.chartLegend .darkgray {
		font-weight: bold;
		color: gray;
	}

	.chartLegend .gray {
		font-weight: bold;
		color: lightgray;
	}

	.chartLegend .blue {
		font-weight: bold;
		color: darkblue;
	}

	.chartLegend .darkred {
		font-weight: bold;
		color: darkred;
	}

	.weight-chart {
		width: 95%;
	}
</style>
