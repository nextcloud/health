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
			{{ t('health', 'Feeling') }}
		</h2>
		<DataTable
			:context-filter="contextFilter"
			:header="header"
			entity-name="Feeling" />
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import moment from '@nextcloud/moment'
import DataTable from '../generic/DataTable'

export default {
	name: 'FeelingContent',
	components: {
		DataTable,
	},
	data: function() {
		return {
			datasets: [],
			contextFilter: {
				app: 'health',
				module: 'feeling',
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
					name: t('health', 'Mood'),
					columnId: 'mood',
					type: 'select',
					show: this.getColumnShow('mood'),
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
					columnId: 'sadness',
					type: 'select',
					show: this.getColumnShow('sadness'),
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
					show: this.getColumnShow('symptoms'),
					type: 'multiselect',
					options: [
						{ id: 0, label: t('health', 'Fatigue', {}) },
						{ id: 1, label: t('health', 'No Appetite', {}) },
						{ id: 2, label: t('health', 'Overeating', {}) },
						{ id: 3, label: t('health', 'Repeated thoughts', {}) },
						{ id: 4, label: t('health', 'Unmotivated', {}) },
						{ id: 5, label: t('health', 'Irritable', {}) },
						{ id: 6, label: t('health', 'Lack of Concentration', {}) },
						{ id: 7, label: t('health', 'Anxiety', {}) },
						{ id: 8, label: t('health', 'Isolation self from others', {}) },
						{ id: 9, label: t('health', 'Thoughts of death/sicide', {}) },
						{ id: 10, label: t('health', 'Feeling hopeless', {}) },
						{ id: 11, label: t('health', 'Feeling worthless', {}) },
						{ id: 12, label: t('health', 'Indecisive', {}) },
					],
				},
				{
					name: t('health', 'Attacks'),
					columnId: 'attacks',
					show: this.getColumnShow('attacks'),
					type: 'multiselect',
					options: [
						{ id: 0, label: t('health', 'Panic attack', {}) },
						{ id: 1, label: t('health', 'Fit of range', {}) },
						{ id: 2, label: t('health', 'Asthma attack', {}) },
					],
				},
				{
					name: t('health', 'Medication'),
					columnId: 'medication',
					type: 'longtext',
					show: this.getColumnShow('medication'),
					placeholder: t('health', 'What medicine did you take?', {}),
				},
				{
					name: t('health', 'Pain'),
					columnId: 'pain',
					type: 'select',
					show: this.getColumnShow('pain'),
					options: [
						{ id: 0, label: t('health', 'None', {}) },
						{ id: 1, label: t('health', 'Low', {}) },
						{ id: 2, label: t('health', 'Medium', {}) },
						{ id: 3, label: t('health', 'High', {}) },
						{ id: 4, label: t('health', 'Very high', {}) },
						{ id: 5, label: t('health', 'Extreme', {}) },
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
		getColumnShow: function(key) {
			const settings = this.moduleSettings
			if (settings && settings[this.contextFilter.module] && settings[this.contextFilter.module].sidebarColumns && settings[this.contextFilter.module].sidebarColumns[key]) {
				return settings[this.contextFilter.module].sidebarColumns[key]
			} else {
				return false
			}
		},
	},
}
</script>
