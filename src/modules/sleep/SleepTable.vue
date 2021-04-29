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
			:entity-name="t('health', 'sleep dataset')"
			@addItem="addItem"
			@updateItem="updateItem"
			@deleteItem="deleteItem" />
	</div>
</template>

<script>
import DataTable from '../../general/DataTable'
import moment from '@nextcloud/moment'

export default {
	name: 'SleepTable',
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
					name: t('health', 'Asleep'),
					columnId: 'asleep',
					type: 'datetime',
					show: true,
					default() {
						return moment().format('YYYY-MM-DDTHH:mm')
					},
				},
				{
					name: t('health', 'Wakeup'),
					columnId: 'wakeup',
					type: 'datetime',
					show: true,
					default() {
						return moment().format('YYYY-MM-DDTHH:mm')
					},
				},
				{
					name: t('health', 'Duration'),
					columnId: 'duration',
					type: 'calculate',
					show: true,
					calc(dataset) {
						if (!dataset || !dataset.asleep || !dataset.wakeup) {
							return ''
						}
						const start = moment(dataset.asleep)
						const end = moment(dataset.wakeup)
						if (end > start) {
							let duration = moment.duration(end.diff(start))
							if ('durationWakeups' in dataset && dataset.durationWakeups) {
								duration = duration.subtract(dataset.durationWakeups, 'minutes')
								if (!duration.isValid()) {
									console.debug('duration is not valid', duration)
									return ''
								}
							}
							let text = ''
							if (duration._data.days > 0) {
								if (text !== '') {
									text = text + ' '
								}
								text = text + n('health', '%nd', '%nd', duration._data.days)
							}
							if (duration._data.hours > 0) {
								if (text !== '') {
									text = text + ' '
								}
								text = text + n('health', '%nh', '%nh', duration._data.hours)
							}
							if (duration._data.minutes > 0) {
								if (text !== '') {
									text = text + ' '
								}
								text = text + n('health', '%nmin', '%nmin', duration._data.minutes)
							}
							return text
						} else {
							// console.debug('error', { start: start, end: end })
							return ''
						}
					},
				},
				{
					name: t('health', 'Quality'),
					columnId: 'quality',
					type: 'select',
					show: this.person.sleepColumnQuality,
					options: [
						{ id: 0, label: t('health', 'Perfect', {}) },
						{ id: 1, label: t('health', 'Fine', {}) },
						{ id: 2, label: t('health', 'Okay', {}) },
						{ id: 3, label: t('health', 'Low', {}) },
						{ id: 4, label: t('health', 'Very low', {}) },
					],
				},
				{
					name: t('health', 'Counted wakeups'),
					columnId: 'countedWakeups',
					type: 'number',
					show: this.person.sleepColumnWakeups,
				},
				{
					name: t('health', 'Duration wakeups (minutes)'),
					columnId: 'durationWakeups',
					type: 'number',
					show: this.person.sleepColumnWakeups,
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
			this.$store.dispatch('sleepDatasetsAppend', item)
		},
		updateItem(item) {
			// console.debug('update item', item)
			this.$store.dispatch('sleepDatasetsUpdate', item)
		},
		deleteItem(item) {
			// console.debug('delete item', item)
			this.$store.dispatch('sleepDatasetsDelete', item)
		},
	},
}
</script>
