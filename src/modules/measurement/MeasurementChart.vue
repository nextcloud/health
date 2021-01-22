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
	<div>
		<Chart
			:chart-style="chartStyle"
			:data="data"
			:definition="setDefinitions"
			:options="options" />
	</div>
</template>

<script>
import Chart from '../../general/Chart'

export default {
	name: 'MeasurementChart',
	components: {
		Chart,
	},
	props: {
		data: {
			type: Array,
			default: null,
		},
		person: {
			type: Object,
			default: null,
		},
	},
	computed: {
		setDefinitions: function() {
			return [
				{
					title: t('health', 'Temperature', {}),
					columnId: 'temperature',
					timeId: 'datetime',
					valueId: 'temperature',
					getValueY: function(v) {
						return v
					},
					borderColor: 'darkblue',
					borderWidth: 2,
					fill: true,
					show: this.person.measurementColumnTemperature,
				},
				{
					title: t('health', 'Heart rate', {}),
					columnId: 'heartRate',
					timeId: 'datetime',
					valueId: 'heartRate',
					getValueY: function(v) {
						return v
					},
					borderColor: 'darkgreen',
					borderWidth: 2,
					fill: false,
					show: this.person.measurementColumnHeartRate,
				},
				{
					title: t('health', 'Blood pressure systolic', {}),
					columnId: 'bloodPressureS',
					timeId: 'datetime',
					valueId: 'bloodPressureS',
					getValueY: function(v) {
						return v
					},
					borderColor: 'rgb(110,20,128)',
					borderWidth: 2,
					fill: false,
					show: this.person.measurementColumnBloodPres,
				},
				{
					title: t('health', 'Blood pressure diastolic', {}),
					columnId: 'bloodPressureD',
					timeId: 'datetime',
					valueId: 'bloodPressureD',
					getValueY: function(v) {
						return v
					},
					borderColor: 'rgb(188,4,219)',
					borderWidth: 2,
					fill: false,
					show: this.person.measurementColumnBloodPres,
				},
				{
					title: t('health', 'Oxygen saturation', {}),
					columnId: 'oxygenSat',
					timeId: 'datetime',
					valueId: 'oxygenSat',
					getValueY: function(v) {
						return v
					},
					borderColor: 'rgb(12,147,159)',
					borderWidth: 2,
					fill: true,
					show: this.person.measurementColumnOxygenSat,
				},
				{
					title: t('health', 'Blood sugar', {}),
					columnId: 'bloodSugar',
					timeId: 'datetime',
					valueId: 'bloodSugar',
					getValueY: function(v) {
						return v
					},
					borderColor: 'rgb(181,80,17)',
					borderWidth: 2,
					fill: true,
					show: this.person.measurementColumnBloodSugar,
				},
				{
					title: t('health', 'Defecation', {}),
					columnId: 'defecation',
					timeId: 'datetime',
					valueId: 'defecation',
					getValueY: function(v) {
						const maxIndex = 3
						return Math.round((v + 1) / (maxIndex + 1) * 100)
					},
					borderColor: 'darkgray',
					borderWidth: 2,
					fill: true,
					type: 'bar',
					show: this.person.measurementColumnDefecation,
				},
			]
		},
		options: function() {
			return {
				title: {
					text: t('health', 'Chart'),
				},
				responsive: true,
				maintainAspectRatio: false,
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
								labelString: t('health', 'Values'),
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
						right: 30,
						left: 25,
					},
				},
				legend: {
					position: 'bottom',
				},
			}
		},
		chartStyle() {
			return {
				height: '250px',
			}
		},
	},
}
</script>
