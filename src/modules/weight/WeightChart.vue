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
import MixinBmi from './MixinBmi'

export default {
	name: 'WeightChart',
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
					title: t('health', 'Weight', {}),
					columnId: 'weight',
					timeId: 'date',
					valueId: 'weight',
					getValueY(v) {
						return v
					},
					borderColor: 'darkblue',
					borderWidth: 2,
					fill: true,
					show: this.person.weightColumnWeight,
					axesId: 'primary',
				},
				{
					title: t('health', 'BMI', {}),
					columnId: 'bmi',
					timeId: 'date',
					valueId: 'calculate',
					getValueY(d) {
						return Math.round(this.calcData.bmiApi.methods.bmi(this.calcData.person, d.weight))
					},
					calcData: {
						person: this.person,
						bmiApi: MixinBmi,
					},
					borderColor: 'rgb(15,128,114)',
					borderWidth: 1,
					fill: false,
					show: this.person.weightColumnBmi,
					axesId: 'bmi',
				},
				{
					title: t('health', 'Target', {}),
					columnId: 'target',
					timeId: 'date',
					valueId: 'static',
					getValueY: this.person.weightTarget,
					borderColor: 'darkgreen',
					borderWidth: 2,
					borderDash: [8, 5],
					fill: false,
					show: !!this.person.weightTarget && this.person.weightColumnWeight,
					axesId: 'primary',
				},
				{
					title: t('health', 'Initial weight', {}),
					columnId: 'initialWeight',
					timeId: 'date',
					valueId: 'static',
					getValueY: this.person.weightTargetInitialWeight,
					borderColor: 'darkred',
					borderWidth: 2,
					borderDash: [2, 5],
					fill: false,
					show: !!this.person.weightTargetInitialWeight && this.person.weightColumnWeight,
					axesId: 'primary',
				},
				{
					title: t('health', 'Body fat', {}),
					columnId: 'bodyfat',
					timeId: 'date',
					valueId: 'bodyfat',
					getValueY(v) {
						return v
					},
					borderColor: 'rgb(99,22,122)',
					borderWidth: 2,
					fill: false,
					show: this.person.weightColumnBodyfat,
					axesId: 'bodyfat',
				},
				{
					title: 'weightMeasurementName' in this.person && this.person.weightMeasurementName ? this.person.weightMeasurementName : t('health', 'Measurement'),
					columnId: 'measurement',
					timeId: 'date',
					valueId: 'measurement',
					getValueY(v) {
						return v
					},
					borderColor: 'darkorange',
					borderWidth: 2,
					fill: true,
					type: 'bar',
					show: this.person.weightColumnMeasurement,
					axesId: 'measurement',
				},
				{
					title: t('health', 'Waist size', {}),
					columnId: 'waistSize',
					timeId: 'date',
					valueId: 'waistSize',
					getValueY(v) {
						return v
					},
					borderColor: 'gray',
					borderWidth: 2,
					fill: false,
					show: this.person.weightColumnWaistSize,
					axesId: 'third',
				},
				{
					title: t('health', 'Hip size', {}),
					columnId: 'hipSize',
					timeId: 'date',
					valueId: 'hipSize',
					getValueY(v) {
						return v
					},
					borderColor: 'darkgray',
					borderWidth: 2,
					fill: false,
					show: this.person.weightColumnHipSize,
					axesId: 'third',
				},
				{
					title: t('health', 'Muscle part', {}),
					columnId: 'musclePart',
					timeId: 'date',
					valueId: 'musclePart',
					getValueY(v) {
						return v
					},
					borderColor: 'lightgreen',
					borderWidth: 2,
					fill: false,
					show: this.person.weightColumnMusclePart,
					axesId: 'musclePart',
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
							id: 'primary',
							gridLines: {
								display: true,
								color: 'gray',
								z: 100,
								lineWidth: 1,
							},
							scaleLabel: {
								display: true,
								labelString: t('health', 'Weight'),
							},
							min: 0,
							max: 100,
							position: 'left',
						},
						{
							id: 'secondary',
							gridLines: {
								display: false,
							},
							scaleLabel: {
								display: true,
								labelString: '',
							},
						},
						{
							id: 'third',
							gridLines: {
								display: false,
							},
							scaleLabel: {
								display: true,
								labelString: t('health', 'Sizes'),
							},
							// min: 0,
							// max: 100,
						},
						{
							id: 'bodyfat',
							gridLines: {
								display: false,
							},
							scaleLabel: {
								display: true,
								labelString: t('health', 'Body fat'),
							},
						},
						{
							id: 'bmi',
							gridLines: {
								display: false,
							},
							scaleLabel: {
								display: true,
								labelString: t('health', 'Body mass index'),
							},
						},
						{
							id: 'measurement',
							gridLines: {
								display: false,
							},
							scaleLabel: {
								display: true,
								labelString: this.person.weightMeasurementName ? this.person.weightMeasurementName : t('health', 'Measurement'),
							},
						},
						{
							id: 'musclePart',
							gridLines: {
								display: false,
							},
							scaleLabel: {
								display: true,
								labelString: t('health', 'Muscle part'),
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
