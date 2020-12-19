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
			{{ t('health', 'Sleep for {name}', {name: person.name}) }}
		</h2>
		<DataTable
			:context-filter="contextFilter"
			:header="header"
			entity-name="Sleeptime" />
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import moment from '@nextcloud/moment'
import DataTable from '../generic/DataTable'

export default {
	name: 'SleepContent',
	components: {
		DataTable,
	},
	data: function() {
		return {
			contextFilter: {
				app: 'health',
				module: 'sleep',
			},
		}
	},
	computed: {
		...mapState(['activePersonId']),
		...mapGetters(['person']),
		header: function() {
			return [
				{
					name: t('health', 'Asleep time'),
					columnId: 'datetimeStart',
					type: 'datetime',
					show: true,
					default: function() {
						return moment().format('YYYY-MM-DDTHH:mm')
					},
				},
				{
					name: t('health', 'Wakeup time'),
					columnId: 'datetimeEnd',
					type: 'datetime',
					show: true,
					default: function() {
						return moment().format('YYYY-MM-DDTHH:mm')
					},
				},
				{
					name: t('health', 'Duration', {}),
					columnId: 'duration',
					type: 'calculate',
					show: true,
					calc: function(dataset) {
						if (!dataset || !dataset.datetimeStart || !dataset.datetimeEnd) {
							return ''
						}
						const start = moment(dataset.datetimeStart)
						const end = moment(dataset.datetimeEnd)
						if (end > start) {
							const duration = moment.duration(end.diff(start))
							let text = ''
							if (duration._data.days > 0) {
								text = text + n('health', '%nd ', '%nd ', duration._data.days)
							}
							if (duration._data.hours > 0) {
								text = text + n('health', '%nh ', '%nh ', duration._data.hours)
							}
							if (duration._data.minutes > 0) {
								text = text + n('health', '%nmin ', '%nmin ', duration._data.minutes)
							}
							return text
						} else {
							console.debug('error', { start: start, end: end })
							return ''
						}
					},
				},
				{
					name: t('health', 'Quality'),
					columnId: 'quality',
					type: 'select',
					show: true,
					options: [
						{ id: 0, label: t('health', 'horrible', {}) },
						{ id: 1, label: t('health', 'low', {}) },
						{ id: 2, label: t('health', 'medium', {}) },
						{ id: 3, label: t('health', 'well', {}) },
						{ id: 4, label: t('health', 'perfect', {}) },
					],
				},
				{
					name: t('health', 'Number of wakeups'),
					columnId: 'wakeups',
					type: 'number',
					show: true,
					min: 0,
					max: 50,
				},
			]
		},
	},
	methods: {
	},
}
</script>
