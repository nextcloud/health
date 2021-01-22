<!--
	- @copyright Copyright (c) 2019 Florian Steffens <flost-dev@mailbox.org>
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
		<LineChart2
			:context-filter="contextFilter"
			:options="options"
			:height="150"
			:range="chartDateRange"
			:set-definitions="setDefinitions" />
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
// import moment from '@nextcloud/moment'
import LineChart2 from '../generic/charts/LineChart2'

export default {
	name: 'FeelingChart',
	components: {
		LineChart2,
	},
	props: {
		contextFilter: {
			type: Object,
			default: null,
		},
		columnShows: {
			type: Object,
			default: null,
		},
	},
	data: function() {
		return {
			chartDateRange: 'month',
			options: {
				title: {
					text: t('health', 'Feeling chart'),
				},
				responsive: true,
				scales: {
					xAxes: [
						{
							type: 'time',
							time: {
								minUnit: 'day',
							},
						},
					],
					yAxes: [
						{
							gridLines: {
								display: true,
								color: 'gray',
								z: 100,
								lineWidth: 1,
							},
							scaleLabel: {
								display: true,
								labelString: t('health', 'Values in %'),
							},
							min: 0,
							max: 100,
						},
					],
				},
				tooltips: {
					enabled: true,
				},
				layout: {
					padding: {
						right: 20,
						left: 20,
					},
				},
				legend: {
					position: 'bottom',
				},
			},
		}
	},
	computed: {
		...mapState(['activePersonId', 'moduleSettings']),
		...mapGetters(['person']),
		setDefinitions: function() {
			return [
				{
					title: t('health', 'Mood', {}),
					columnId: 'mood',
					timeId: 'datetime',
					valueId: 'mood',
					getValueY: function(v) {
						const max = 4
						return Math.round((max - v.id) / max * 100)
					},
					borderColor: 'darkblue',
					borderWidth: 2,
					fill: false,
					show: this.columnShows.mood,
				},
				{
					title: t('health', 'Sadness level', {}),
					columnId: 'sadness',
					timeId: 'datetime',
					valueId: 'sadness',
					getValueY: function(v) {
						const max = 3
						return Math.round(v.id / max * 100)
					},
					borderColor: 'darkgreen',
					backgroundColor: 'rgba(3,121,14,0.2)',
					borderWidth: 1,
					fill: true,
					show: this.columnShows.sadness,
				},
				{
					title: t('health', 'Symptoms', {}),
					columnId: 'symptoms',
					timeId: 'datetime',
					valueId: 'symptoms',
					getValueY: function(v) {
						const max = 13
						return Math.round(v.length / max * 100)
					},
					borderColor: 'darkgray',
					backgroundColor: 'lightgray',
					borderWidth: 1,
					fill: true,
					type: 'bar',
					show: this.columnShows.symptoms,
				},
				{
					title: t('health', 'Pain', {}),
					columnId: 'pain',
					timeId: 'datetime',
					valueId: 'pain',
					getValueY: function(v) {
						const max = 5
						const value = v
						return Math.round(value / max * 100)
					},
					borderColor: 'red',
					backgroundColor: 'rgba(255,0,0,0.2)',
					borderWidth: 1,
					fill: true,
					type: 'bar',
					show: this.columnShows.pain,
				},
			]
		},
	},
}
</script>
<style lang="css" scoped>

	select {
		margin-top: 20px;
		margin-bottom: 20px;
	}

</style>
