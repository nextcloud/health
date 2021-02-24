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
	name: 'SmokingChart',
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
					title: t('health', 'Cigarettes', {}),
					columnId: 'cigarettes',
					timeId: 'date',
					valueId: 'cigarettes',
					getValueY(v) {
						return v
					},
					borderColor: 'darkblue',
					borderWidth: 2,
					fill: true,
					show: this.person.smokingColumnCigarettes,
				},
				{
					title: t('health', 'Desire level', {}),
					columnId: 'desireLevel',
					timeId: 'date',
					valueId: 'desireLevel',
					getValueY(v) {
						const maxIndex = 6
						return Math.round((v + 1) / (maxIndex + 1) * 10)
					},
					borderColor: 'darkred',
					borderWidth: 2,
					fill: false,
					show: this.person.smokingColumnDesireLevel,
				},
				{
					title: t('health', 'Saved money', {}),
					columnId: 'savedMoney',
					timeId: 'date',
					valueId: 'calculate',
					getValueY(v) {
						if (!this.calcData.price || !this.calcData.start || v.cigarettes >= this.calcData.start) {
							return null
						} else {
							const count = this.calcData.start - v.cigarettes
							return Math.round(count * this.calcData.price * 100) / 100
						}
					},
					calcData: {
						price: this.person.smokingPrice,
						currency: this.person.currency,
						start: this.person.smokingStartValue,
					},
					borderColor: 'green',
					borderWidth: 1,
					fill: true,
					type: 'bar',
					show: this.person.smokingColumnSavedMoney,
				},
				{
					title: t('health', 'Goal', {}),
					columnId: 'goal',
					timeId: 'date',
					valueId: 'static',
					getValueY: this.person.smokingGoal,
					borderColor: 'darkgreen',
					borderWidth: 2,
					borderDash: [8, 5],
					fill: false,
					show: !!this.person.smokingGoal,
				},
				{
					title: t('health', 'Initial cigarettes', {}),
					columnId: 'startValue',
					timeId: 'date',
					valueId: 'static',
					getValueY: this.person.smokingStartValue,
					borderColor: 'darkred',
					borderWidth: 2,
					borderDash: [2, 5],
					fill: false,
					show: !!this.person.smokingStartValue,
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
