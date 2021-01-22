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
	name: 'FeelingChart',
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
					title: t('health', 'Mood', {}),
					columnId: 'mood',
					timeId: 'datetime',
					valueId: 'mood',
					getValueY: function(v) {
						const maxIndex = 3
						return Math.round((v + 1) / (maxIndex + 1) * 100)
					},
					borderColor: 'darkgray',
					borderWidth: 2,
					fill: true,
					type: 'bar',
					show: this.person.feelingColumnMood,
				},
				{
					title: t('health', 'Sadness level', {}),
					columnId: 'sadnessLevel',
					timeId: 'datetime',
					valueId: 'sadnessLevel',
					getValueY: function(v) {
						const maxIndex = 3
						return Math.round((v + 1) / (maxIndex + 1) * 100)
					},
					borderColor: 'rgb(115,32,32)',
					borderWidth: 2,
					fill: false,
					show: this.person.feelingColumnSadnessLevel,
				},
				{
					title: t('health', 'Symptoms', {}),
					columnId: 'symptoms',
					timeId: 'datetime',
					valueId: 'symptoms',
					getValueY: function(v) {
						const maxIndex = 6
						return Math.round((v + 1) / (maxIndex + 1) * 100)
					},
					borderColor: 'rgb(11,109,53)',
					borderWidth: 2,
					fill: true,
					type: 'bar',
					show: this.person.feelingColumnSymptoms,
				},
				{
					title: t('health', 'Attacks', {}),
					columnId: 'attacks',
					timeId: 'datetime',
					valueId: 'attacks',
					getValueY: function(v) {
						const maxIndex = 6
						return Math.round((v + 1) / (maxIndex + 1) * 100)
					},
					borderColor: 'rgb(11,109,53)',
					borderWidth: 2,
					fill: true,
					type: 'bar',
					show: this.person.feelingColumnAttacks,
				},
				{
					title: t('health', 'Pain', {}),
					columnId: 'pain',
					timeId: 'datetime',
					valueId: 'pain',
					getValueY: function(v) {
						const maxIndex = 10
						return Math.round((v + 1) / (maxIndex + 1) * 100)
					},
					borderColor: 'rgb(212,40,40)',
					borderWidth: 2,
					fill: true,
					show: this.person.feelingColumnPain,
				},
				{
					title: t('health', 'Energy', {}),
					columnId: 'energy',
					timeId: 'datetime',
					valueId: 'energy',
					getValueY: function(v) {
						const maxIndex = 10
						return Math.round((v + 1) / (maxIndex + 1) * 100)
					},
					borderColor: 'rgb(212,135,40)',
					borderWidth: 2,
					fill: true,
					show: this.person.feelingColumnEnergy,
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
