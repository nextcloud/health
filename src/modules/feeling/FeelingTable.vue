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
			entity-name="feeling dataset"
			@addItem="addItem"
			@updateItem="updateItem"
			@deleteItem="deleteItem" />
	</div>
</template>

<script>
import DataTable from '../../general/DataTable'
import moment from '@nextcloud/moment'

export default {
	name: 'FeelingTable',
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
					default: function() {
						return moment().format('YYYY-MM-DDTHH:mm')
					},
				},
				{
					name: t('health', 'Mood'),
					columnId: 'mood',
					type: 'select',
					show: this.person.feelingColumnMood,
					options: [
						{ id: 0, label: t('health', 'Perfect', {}) },
						{ id: 1, label: t('health', 'Fine', {}) },
						{ id: 2, label: t('health', 'Okay', {}) },
						{ id: 3, label: t('health', 'Don\'t know', {}) },
						{ id: 4, label: t('health', 'Don\'t ask me', {}) },
					],
				},
				{
					name: t('health', 'Sadness level'),
					columnId: 'sadnessLevel',
					type: 'select',
					show: this.person.feelingColumnSadnessLevel,
					options: [
						{ id: 0, label: t('health', 'None', {}) },
						{ id: 1, label: t('health', 'Low', {}) },
						{ id: 2, label: t('health', 'Medium', {}) },
						{ id: 3, label: t('health', 'High', {}) },
					],
				},
				{
					name: t('health', 'Symptoms'),
					columnId: 'symptoms',
					show: this.person.feelingColumnSymptoms,
					type: 'multiselect',
					options: [
						t('health', 'Fatigue', {}),
						t('health', 'No Appetite', {}),
						t('health', 'Overeating', {}),
						t('health', 'Repeated thoughts', {}),
						t('health', 'Unmotivated', {}),
						t('health', 'Irritable', {}),
						t('health', 'Lack of Concentration', {}),
						t('health', 'Anxiety', {}),
						t('health', 'Isolation self from others', {}),
						t('health', 'Thoughts of death/sicide', {}),
						t('health', 'Feeling hopeless', {}),
						t('health', 'Feeling worthless', {}),
						t('health', 'Indecisive', {}),
					],
				},
				{
					name: t('health', 'Attacks'),
					columnId: 'attacks',
					show: this.person.feelingColumnAttacks,
					type: 'multiselect',
					options: [
						t('health', 'Panic attack', {}),
						t('health', 'Fit of range', {}),
						t('health', 'Asthma attack', {}),
					],
				},
				{
					name: t('health', 'Medication'),
					columnId: 'medication',
					type: 'longtext',
					show: this.person.feelingColumnMedication,
					placeholder: t('health', 'What medicine did you take?', {}),
				},
				{
					name: t('health', 'Pain'),
					columnId: 'pain',
					type: 'select',
					show: this.person.feelingColumnPain,
					options: [
						{ id: 0, label: '0' },
						{ id: 1, label: '1' },
						{ id: 2, label: '2' },
						{ id: 3, label: '3' },
						{ id: 4, label: '4' },
						{ id: 5, label: '5' },
						{ id: 6, label: '6' },
						{ id: 7, label: '7' },
						{ id: 8, label: '8' },
						{ id: 9, label: '9' },
						{ id: 10, label: '10' },
					],
				},
				{
					name: t('health', 'Energy'),
					columnId: 'energy',
					type: 'select',
					show: this.person.feelingColumnEnergy,
					options: [
						{ id: 0, label: '0' },
						{ id: 1, label: '1' },
						{ id: 2, label: '2' },
						{ id: 3, label: '3' },
						{ id: 4, label: '4' },
						{ id: 5, label: '5' },
						{ id: 6, label: '6' },
						{ id: 7, label: '7' },
						{ id: 8, label: '8' },
						{ id: 9, label: '9' },
						{ id: 10, label: '10' },
					],
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
			this.$store.dispatch('feelingDatasetsAppend', item)
		},
		updateItem(item) {
			// console.debug('update item', item)
			this.$store.dispatch('feelingDatasetsUpdate', item)
		},
		deleteItem(item) {
			// console.debug('delete item', item)
			this.$store.dispatch('feelingDatasetsDelete', item)
		},
	},
}
</script>
