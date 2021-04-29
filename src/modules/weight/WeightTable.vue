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
		<DataTable
			:data="data"
			:header="headerDefinition"
			:entity-name="t('health', 'weight dataset')"
			@addItem="addItem"
			@updateItem="updateItem"
			@deleteItem="deleteItem" />
	</div>
</template>

<script>
import DataTable from '../../general/DataTable'
import MixinBmi from './MixinBmi'

export default {
	name: 'WeightTable',
	components: {
		DataTable,
	},
	mixins: [MixinBmi],
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
		headerDefinition() {
			return [
				{
					name: t('health', 'Date'),
					columnId: 'date',
					type: 'date',
					show: true,
					default() {
						return new Date().toISOString().slice(0, 10)
					},
				},
				{
					name: t('health', 'Weight'),
					columnId: 'weight',
					type: 'number',
					show: this.person.weightColumnWeight,
					suffix: this.person.weightUnit ? this.person.weightUnit : '',
				},
				{
					name: t('health', 'BMI'),
					columnId: 'bmi',
					type: 'calculate',
					calc(dataset) {
						return this.calcData.bmiApi.methods.bmi(this.calcData.person, dataset.weight)
					},
					calcData: {
						person: this.person,
						bmiApi: MixinBmi,
					},
					show: this.person.weightColumnBmi,
				},
				{
					name: t('health', 'Bodyfat'),
					columnId: 'bodyfat',
					type: 'number',
					show: this.person.weightColumnBodyfat,
					suffix: '%',
				},
				{
					name: 'weightMeasurementName' in this.person && this.person.weightMeasurementName ? this.person.weightMeasurementName : t('health', 'Measurement'),
					columnId: 'measurement',
					type: 'number',
					show: this.person.weightColumnMeasurement,
				},
				{
					name: t('health', 'Waist circumference'),
					columnId: 'waistSize',
					type: 'number',
					show: this.person.weightColumnWaistSize,
					suffix: 'cm',
				},
				{
					name: t('health', 'Hip circumference'),
					columnId: 'hipSize',
					type: 'number',
					show: this.person.weightColumnHipSize,
					suffix: 'cm',
				},
				{
					name: t('health', 'Muscle part'),
					columnId: 'musclePart',
					type: 'number',
					show: this.person.weightColumnMusclePart,
					suffix: '%',
				},
				{
					name: t('health', 'Comment'),
					columnId: 'comment',
					type: 'longtext',
					show: true,
				},
			]
		},
	},
	methods: {
		addItem(item) {
			// console.debug('add item', item)
			this.$store.dispatch('rWeightDatasetsAppend', item)
		},
		updateItem(item) {
			// console.debug('update item', item)
			this.$store.dispatch('rWeightDatasetsUpdate', item)
		},
		deleteItem(item) {
			// console.debug('delete item', item)
			this.$store.dispatch('rWeightDatasetsDelete', item)
		},
	},
}
</script>
