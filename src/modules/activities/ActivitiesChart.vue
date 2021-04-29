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
	name: 'ActivitiesChart',
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
		setDefinitions() {
			return [
				{
					title: t('health', 'Burned calories', {}),
					columnId: 'calories',
					timeId: 'datetime',
					valueId: 'calories',
					getValueY(v) {
						return v
					},
					borderColor: 'darkblue',
					borderWidth: 2,
					fill: true,
					show: this.person.activitiesColumnCalories,
					axesId: 'calories',
				},
				{
					title: t('health', 'Duration', {}),
					columnId: 'duration',
					timeId: 'datetime',
					valueId: 'duration',
					getValueY(v) {
						return v
					},
					borderColor: 'darkred',
					borderWidth: 2,
					fill: false,
					show: this.person.activitiesColumnDuration,
					axesId: 'duration',
				},
				{
					title: t('health', 'Feeling', {}),
					columnId: 'feeling',
					timeId: 'datetime',
					valueId: 'feeling',
					getValueY(v) {
						const maxIndex = 4
						return Math.round((v + 1) / (maxIndex + 1) * 100)
					},
					borderColor: 'green',
					borderWidth: 1,
					fill: false,
					// type: 'bar',
					show: this.person.activitiesColumnFeeling,
					axesId: 'feeling',
				},
				{
					title: t('health', 'Intensity', {}),
					columnId: 'intensity',
					timeId: 'datetime',
					valueId: 'intensity',
					getValueY(v) {
						const maxIndex = 3
						return Math.round((v + 1) / (maxIndex + 1) * 100)
					},
					borderColor: 'orange',
					borderWidth: 1,
					fill: false,
					type: 'bar',
					show: this.person.activitiesColumnIntensity,
					axesId: 'intensity',
				},
				{
					title: t('health', 'Distance', {}),
					columnId: 'distance',
					timeId: 'datetime',
					valueId: 'distance',
					getValueY(v) {
						return v
					},
					borderColor: 'darkgreen',
					borderWidth: 2,
					borderDash: [8, 5],
					fill: true,
					show: this.person.activitiesColumnDistance,
					axesId: 'distance',
				},
			]
		},
		options() {
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
							id: 'calories',
							gridLines: {
								display: true,
								color: 'gray',
								z: 100,
								lineWidth: 1,
							},
							scaleLabel: {
								display: true,
								labelString: t('health', 'Calories [kcal]'),
							},
							min: 0,
							position: 'left',
							ticks: {
								stepSize: 1,
								beginAtZero: true,
							},
						},
						{
							id: 'duration',
							gridLines: {
								display: false,
								color: 'gray',
								z: 100,
								lineWidth: 1,
							},
							scaleLabel: {
								display: true,
								labelString: t('health', 'Duration [min.]'),
							},
							min: 0,
							position: 'left',
							ticks: {
								stepSize: 1,
								beginAtZero: true,
							},
						},
						{
							id: 'feeling',
							gridLines: {
								display: false,
								color: 'gray',
								z: 100,
								lineWidth: 1,
							},
							scaleLabel: {
								display: true,
								labelString: t('health', 'Feeling after the activity [%]'),
							},
							min: 0,
							max: 100,
							position: 'left',
							ticks: {
								stepSize: 1,
								beginAtZero: true,
							},
						},
						{
							id: 'intensity',
							gridLines: {
								display: false,
								color: 'gray',
								z: 100,
								lineWidth: 1,
							},
							scaleLabel: {
								display: true,
								labelString: t('health', 'Intensity [%]'),
							},
							min: 0,
							max: 100,
							position: 'left',
							ticks: {
								stepSize: 1,
								beginAtZero: true,
							},
						},
						{
							id: 'distance',
							gridLines: {
								display: false,
								color: 'gray',
								z: 100,
								lineWidth: 1,
							},
							scaleLabel: {
								display: true,
								labelString: t('health', 'Distance [{activitiesDistanceUnit}]', { activitiesDistanceUnit: this.person.activitiesDistanceUnit }),
							},
							min: 0,
							position: 'left',
							ticks: {
								stepSize: 1,
								beginAtZero: true,
							},
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
