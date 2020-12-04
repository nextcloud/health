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
		<h2>
			{{ t('health', 'Measure for {name}', {name: person.name}) }}
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
		...mapState(['activePersonId']),
		...mapGetters(['person']),
		header: function() {
			return [
				{
					name: t('health', 'Date'),
					columnId: 'datetime',
					type: 'datetime',
					show: true,
					section: {
						id: 'meta',
						name: t('health', 'General information', {}),
					},
					default: function() {
						return moment().format('YYYY-MM-DDTHH:mm')
					},
				},
				{
					name: t('health', 'Heart rate'),
					columnId: 'heartrate',
					type: 'number',
					show: true,
					min: 0,
					section: {
						id: 'vitalfunction',
						name: t('health', 'Vital function and more', {}),
					},
				},
				{
					name: t('health', 'Temperature'),
					columnId: 'temperature',
					type: 'number',
					show: true,
					min: 35,
					max: 42,
					section: {
						id: 'vitalfunction',
						name: t('health', 'Vital function and more', {}),
					},
				},
				{
					name: t('health', 'Blood pressure systolic'),
					columnId: 'systolic',
					type: 'number',
					show: true,
					min: 0,
					section: {
						id: 'vitalfunction',
						name: t('health', 'Vital function and more', {}),
					},
				},
				{
					name: t('health', 'Blood pressure diastolic'),
					columnId: 'diastolic',
					type: 'number',
					show: true,
					min: 0,
					section: {
						id: 'vitalfunction',
						name: t('health', 'Vital function and more', {}),
					},
				},
				{
					name: t('health', 'Oxygen saturation'),
					columnId: 'oxygensaturation',
					type: 'number',
					show: true,
					min: 80,
					max: 100,
					// unter 90 ist dramatisch
					style: function(value) {
						console.debug('render style', value)
						if (value < 90) {
							return 'color: darkred; font-weight: bolder;'
						} else {
							return ''
						}
					},
					section: {
						id: 'vitalfunction',
						name: t('health', 'Vital function and more', {}),
					},
				},
				{
					name: t('health', 'Blood sugar'),
					columnId: 'bloodsugar',
					type: 'number',
					show: true,
					min: 40,
					max: 600,
					// 80-120 ist normal
					section: {
						id: 'vitalfunction',
						name: t('health', 'Vital function and more', {}),
					},
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
					name: t('health', 'Comment'),
					columnId: 'comment',
					type: 'longtext',
					show: true,
					placeholder: t('health', 'Give some comment, if you want...', {}),
					maxlength: 1000,
					section: {
						id: 'additional',
						name: t('health', 'Additional information', {}),
					},
				},
			]
		},
	},
	methods: {
	},
}
</script>
<style lang="scss" scoped>
</style>
