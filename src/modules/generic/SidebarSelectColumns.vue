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
		<ActionCheckbox
			v-for="(c, i) in columns"
			:id="c.id"
			:key="i"
			:checked="values[c.id]"
			@check="values[c.id] = true"
			@uncheck="values[c.id] = false"
			@change="saveColumnSet">
			{{ c.name }}
		</ActionCheckbox>
	</ul>
</template>

<script>
import ActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox'

export default {
	name: 'SidebarSelectColumns',
	components: {
		ActionCheckbox,
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
	beforeMount() {
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
				console.debug('no saved sets found')
			}
		})
	},
	methods: {
		saveColumnSet: function(e) {
			// console.debug('save column set: event', e)
			this.values[e.target.id] = e.target.checked
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
