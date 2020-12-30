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
		<h3>{{ person.name }}</h3>
		<h2>
			{{ t('health', 'Measurement') }}
		</h2>
		<DataTable
			:context-filter="contextFilter"
			:header="header"
			entity-name="Measurement" />
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import moment from '@nextcloud/moment'
import DataTable from '../generic/DataTable'

export default {
	name: 'MeasurementContent',
	components: {
		DataTable,
	},
	data: function() {
		return {
			contextFilter: {
				app: 'health',
				module: 'measurement',
			},
		}
	},
	computed: {
		...mapState(['activePersonId', 'moduleSettings']),
		...mapGetters(['person']),
		header: function() {
			return [
				{
					name: t('health', 'Date'),
					columnId: 'datetime',
					type: 'datetime',
					show: true,
					default: function() {
						return moment().format('YYYY-MM-DDTHH:mm')
					},
				},
				{
					name: t('health', 'Temperature'),
					columnId: 'temperature',
					type: 'number',
					show: this.getColumnShow('temperature'),
					min: 35,
					max: 42,
				},
				{
					name: t('health', 'Heart rate'),
					columnId: 'heartrate',
					type: 'number',
					show: this.getColumnShow('heartrate'),
					min: 0,
				},
				{
					name: t('health', 'Blood pressure systolic'),
					columnId: 'systolic',
					type: 'number',
					show: this.getColumnShow('bloodPressure'),
					min: 0,
				},
				{
					name: t('health', 'Blood pressure diastolic'),
					columnId: 'diastolic',
					type: 'number',
					show: this.getColumnShow('bloodPressure'),
					min: 0,
				},
				{
					name: t('health', 'Oxygen saturation'),
					columnId: 'oxygensaturation',
					type: 'number',
					show: this.getColumnShow('oxygensaturation'),
					min: 80,
					max: 100,
					// unter 90 ist dramatisch
					style: function(value) {
						// console.debug('render style', value)
						if (value < 90) {
							return 'color: darkred; font-weight: bolder;'
						} else {
							return ''
						}
					},
				},
				{
					name: t('health', 'Blood sugar'),
					columnId: 'bloodsugar',
					type: 'number',
					show: this.getColumnShow('bloodsugar'),
					min: 40,
					max: 600,
					// 80-120 ist normal
					style: function(value) {
						console.debug('render style', value)
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
					show: this.getColumnShow('defecation'),
					options: [
						t('health', 'low', {}),
						t('health', 'middle', {}),
						t('health', 'high', {}),
						t('health', 'extreme', {}),
					],
					style: function(value) {
						console.debug('render style', value)
						if (value === 4) {
							return 'color: darkred; font-weight: bolder;'
						} else {
							return ''
						}
					},
				},
				{
					name: t('health', 'Appetite'),
					columnId: 'appetite',
					type: 'longtext',
					show: this.getColumnShow('appetite'),
					placeholder: t('health', 'What about your appetite...', {}),
					maxlength: 1000,
				},
				{
					name: t('health', 'Allergies'),
					columnId: 'allergies',
					type: 'longtext',
					show: this.getColumnShow('allergies'),
					placeholder: t('health', 'What about your allergies...', {}),
					maxlength: 1000,
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
		getColumnShow: function(key) {
			if (
				this.moduleSettings
				&& this.moduleSettings.measurement
				&& this.moduleSettings.measurement.sidebarColumns
				&& this.moduleSettings.measurement.sidebarColumns[key]
			) {
				return this.moduleSettings.measurement.sidebarColumns[key]
			} else {
				return false
			}
		},
	},
}
</script>
