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
			:entity-name="t('health', 'Activity')"
			@addItem="addItem"
			@updateItem="updateItem"
			@deleteItem="deleteItem" />
	</div>
</template>

<script>
import DataTable from '../../general/DataTable'
import moment from '@nextcloud/moment'

export default {
	name: 'ActivitiesTable',
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
					name: t('health', 'Burned calories'),
					columnId: 'calories',
					type: 'number',
					show: this.person.activitiesColumnCalories,
					suffix: ' ' + t('health', 'kcal', {}),
				},
				{
					name: t('health', 'Activity duration'),
					columnId: 'duration',
					type: 'number',
					show: this.person.activitiesColumnDuration,
					suffix: ' ' + t('health', 'min.', {}),
				},
				{
					name: t('health', 'Distance'),
					columnId: 'distance',
					type: 'number',
					show: this.person.activitiesColumnDistance,
					suffix: ' ' + (this.person.activitiesDistanceUnit ? this.person.activitiesDistanceUnit : ''),
				},
				{
					name: t('health', 'Category'),
					columnId: 'category',
					type: 'select',
					options: [
						{ id: 0, label: t('health', 'Running') },
						{ id: 1, label: t('health', 'Yoga') },
						{ id: 2, label: t('health', 'Gymnastics') },
						{ id: 3, label: t('health', 'Climbing') },
						{ id: 4, label: t('health', 'Fitness') },
						{ id: 5, label: t('health', 'Workout') },
						{ id: 6, label: t('health', 'Job') },
						{ id: 7, label: t('health', 'Biking') },
						{ id: 8, label: t('health', 'Swimming') },
						{ id: 9, label: t('health', 'Walking') },
						{ id: 10, label: t('health', 'Cardio') },
						{ id: 11, label: t('health', 'Driving') },
						{ id: 12, label: t('health', 'eSports') },
						{ id: 13, label: t('health', 'Martial arts') },
						{ id: 14, label: t('health', 'Team sports') },
						{ id: 15, label: t('health', 'Sports with animals') },
						{ id: 16, label: t('health', 'Adventure sports') },
						{ id: 17, label: t('health', 'Mind sports') },
						{ id: 18, label: t('health', 'Water sports') },
						{ id: 19, label: t('health', 'Other') },
					],
					show: this.person.activitiesColumnCategory,
				},
				{
					name: t('health', 'Feeling after activity'),
					columnId: 'feeling',
					type: 'select',
					options: [
						{ id: 0, label: t('health', 'Fantastic', {}) },
						{ id: 1, label: t('health', 'Good', {}) },
						{ id: 2, label: t('health', 'Okay', {}) },
						{ id: 3, label: t('health', 'Bad', {}) },
						{ id: 4, label: t('health', 'Horrible', {}) },
					],
					show: this.person.activitiesColumnFeeling,
				},
				{
					name: t('health', 'Training intensity'),
					columnId: 'intensity',
					type: 'select',
					options: [
						{ id: 0, label: t('health', 'Low', {}) },
						{ id: 1, label: t('health', 'Medium', {}) },
						{ id: 2, label: t('health', 'High', {}) },
						{ id: 3, label: t('health', 'Very high', {}) },
					],
					show: this.person.activitiesColumnIntensity,
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
			this.$store.dispatch('activitiesDatasetsAppend', item)
		},
		updateItem(item) {
			console.debug('update item', item)
			this.$store.dispatch('activitiesDatasetsUpdate', item)
		},
		deleteItem(item) {
			// console.debug('delete item', item)
			this.$store.dispatch('activitiesDatasetsDelete', item)
		},
	},
}
</script>
