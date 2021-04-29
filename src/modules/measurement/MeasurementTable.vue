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
			:entity-name="t('health', 'measurement dataset')"
			@addItem="addItem"
			@updateItem="updateItem"
			@deleteItem="deleteItem" />
	</div>
</template>

<script>
import DataTable from '../../general/DataTable'
import moment from '@nextcloud/moment'

export default {
	name: 'MeasurementTable',
	components: {
		DataTable,
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
		headerDefinition() {
			return [
				{
					name: t('health', 'Date'),
					columnId: 'datetime',
					type: 'datetime',
					show: true,
					default() {
						return moment().format('YYYY-MM-DDTHH:mm')
					},
				},
				{
					name: t('health', 'Temperature'),
					columnId: 'temperature',
					type: 'number',
					show: this.person.measurementColumnTemperature,
					min: 35,
					max: 42,
				},
				{
					name: t('health', 'Heart rate'),
					columnId: 'heartRate',
					type: 'number',
					show: this.person.measurementColumnHeartRate,
					min: 0,
				},
				{
					name: t('health', 'Blood pressure systolic'),
					columnId: 'bloodPressureS',
					type: 'number',
					show: this.person.measurementColumnBloodPres,
					min: 0,
				},
				{
					name: t('health', 'Blood pressure diastolic'),
					columnId: 'bloodPressureD',
					type: 'number',
					show: this.person.measurementColumnBloodPres,
					min: 0,
				},
				{
					name: t('health', 'Oxygen saturation'),
					columnId: 'oxygenSat',
					type: 'number',
					show: this.person.measurementColumnOxygenSat,
					min: 80,
					max: 100,
					// unter 90 ist dramatisch
					style(value) {
						if (value < 90) {
							return 'color: darkred; font-weight: bolder;'
						} else {
							return ''
						}
					},
				},
				{
					name: t('health', 'Blood sugar'),
					columnId: 'bloodSugar',
					type: 'number',
					show: this.person.measurementColumnBloodSugar,
					min: 40,
					max: 600,
					// 80-120 ist normal
					style(value) {
						if (value < 80 || value > 120) {
							return 'color: darkred; font-weight: bolder;'
						} else {
							return ''
						}
					},
				},
				{
					name: t('health', 'Defecation'),
					columnId: 'defecation',
					type: 'select',
					show: this.person.measurementColumnDefecation,
					options: [
						{ id: 0, label: t('health', 'Low', {}) },
						{ id: 1, label: t('health', 'Medium', {}) },
						{ id: 2, label: t('health', 'High', {}) },
						{ id: 3, label: t('health', 'Very high', {}) },
					],
					style(value) {
						if (value === 3) {
							return 'color: darkred; font-weight: bolder;'
						} else {
							return ''
						}
					},
				},
				{
					name: t('health', 'Appetite'),
					columnId: 'appetite',
					type: 'select',
					show: this.person.measurementColumnAppetite,
					options: [
						{ id: 0, label: t('health', 'Low', {}) },
						{ id: 1, label: t('health', 'Medium', {}) },
						{ id: 2, label: t('health', 'High', {}) },
						{ id: 3, label: t('health', 'Very high', {}) },
					],
					style(value) {
						if (value === 3) {
							return 'color: darkred; font-weight: bolder;'
						} else {
							return ''
						}
					},
				},
				{
					name: t('health', 'Allergies'),
					columnId: 'allergies',
					type: 'select',
					show: this.person.measurementColumnAllergies,
					options: [
						{ id: 0, label: t('health', 'Low', {}) },
						{ id: 1, label: t('health', 'Medium', {}) },
						{ id: 2, label: t('health', 'High', {}) },
						{ id: 3, label: t('health', 'Very high', {}) },
					],
					style(value) {
						if (value === 3) {
							return 'color: darkred; font-weight: bolder;'
						} else {
							return ''
						}
					},
				},
				{
					name: t('health', 'Comment'),
					columnId: 'comment',
					type: 'longtext',
					show: true,
					placeholder: t('health', 'Give some comment, if you want...', {}),
					maxlength: 1000,
				},
			]
		},
	},
	methods: {
		addItem(item) {
			// console.debug('add item', item)
			this.$store.dispatch('measurementDatasetsAppend', item)
		},
		updateItem(item) {
			// console.debug('update item', item)
			this.$store.dispatch('measurementDatasetsUpdate', item)
		},
		deleteItem(item) {
			// console.debug('delete item', item)
			this.$store.dispatch('measurementDatasetsDelete', item)
		},
	},
}
</script>
