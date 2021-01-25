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
	<ul>
		<li><h3>{{ t('health', 'Column selection', {}) }}</h3></li>
		<div>
			<label>
				<input
					id="mood"
					v-model="columns.quality"
					type="checkbox"
					@change="saveColumn('quality')">
				{{ t('health', 'Quality', {}) }}
			</label>
		</div>
		<div>
			<label>
				<input
					id="wakeups"
					v-model="columns.wakeups"
					type="checkbox"
					@change="saveColumn('wakeups')">
				{{ t('health', 'Track wakeups', {}) }}
			</label>
		</div>
	</ul>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'SleepSidebar',
	data: function() {
		return {
			columns: {
				quality: true,
				wakeups: true,
			},
		}
	},
	computed: {
		...mapState(['activeModule', 'showSidebar']),
		...mapGetters(['person']),
	},
	watch: {
		person: function() {
			this.updateLocalColumnData()
		},
	},
	mounted() {
		this.updateLocalColumnData()
	},
	methods: {
		updateLocalColumnData() {
			if (this.person) {
				this.columns.quality = this.person.sleepColumnQuality
				this.columns.wakeups = this.person.sleepColumnWakeups
			} else {
				console.debug('no person found to update [watch person in SleepSidebar]')
			}
		},
		saveColumn(key) {
			this.$store.dispatch('setValue', { key: 'sleepColumn' + key[0].toUpperCase() + key.substring(1), value: this.columns[key] })
		},
	},
}
</script>
