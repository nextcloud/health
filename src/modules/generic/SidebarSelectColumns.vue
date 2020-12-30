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
		<div
			v-for="(c, i) in columns"
			:id="c.id"
			:key="i">
			<label>
				<input
					:id="c.id"
					v-model="values[c.id]"
					type="checkbox"
					@change="saveColumnSet">
				{{ c.name }}
			</label>
		</div>
	</ul>
</template>

<script>
import { mapState } from 'vuex'

export default {
	name: 'SidebarSelectColumns',
	components: {
	},
	props: {
		personId: {
			type: Number,
			default: null,
		},
		columns: {
			type: Array,
			default: function() { return [] },
		},
		contextFilter: {
			type: Object,
			default: null,
		},
	},
	data: function() {
		return {
			values: {},
			id: null,
		}
	},
	computed: {
		...mapState(['activePersonId']),
	},
	watch: {
		activePersonId: function() {
			console.debug('person changed, load new sidebar column values')
			this.loadValues()
		},
	},
	beforeMount() {
		this.loadValues()
	},
	methods: {
		saveColumnSet: function() {
			this.setValuesToStore()

			const request = {}
			request.contextFilter = this.contextFilter
			if (this.id) {
				request.entityFilter = {
					id: this.id,
				}
			}
			request.entityData = this.values
			// console.debug('update column set, request:', request)
			this.$store.dispatch('cesRequest', request).then(result => {
				console.debug('column set saved', result)
				this.id = result[0].id
			})
		},
		loadValues: function() {
			// console.debug('load values for sidebarcolumns')
			// load settings from db
			const request = {}
			request.contextFilter = this.contextFilter
			request.entityFilter = {
				personId: this.personId,
			}
			// console.debug('try to load column set, request:', request)
			this.$store.dispatch('cesRequest', request).then(results => {
				// console.debug('result', results)
				if (results.length > 0) {
					this.id = results[0].id
					const result = JSON.parse(results[0].data)
					delete result.personId
					this.values = result
					this.setValuesToStore()
				} else {
					console.debug('no saved sets found, load defaults')
					this.values = {}
					this.columns.forEach(item => {
						if (item.default) {
							console.debug('default for ' + item.id, item.default)
							this.values[item.id] = item.default
						}
					})
					console.debug('defaults loaded', this.values)
					this.saveColumnSet()
				}
			})
		},
		setValuesToStore: function() {
			const o = {
				module: this.contextFilter.module,
				type: this.contextFilter.type,
				data: this.values,
			}
			this.$store.dispatch('updateModuleSettings', o)
		},
	},
}
</script>
