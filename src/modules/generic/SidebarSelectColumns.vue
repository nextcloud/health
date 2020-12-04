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
		<li><h3>{{ t('health', 'General settings', {}) }}</h3></li>
		<li><h4>{{ t('health', 'Select columns', {}) }}</h4></li>
		<ActionCheckbox
			v-for="(c, i) in columns"
			:id="c.id"
			:key="i"
			@check="values[c.id] = true"
			@uncheck="values[c.id] = false"
			// @change wird zu früh ausgeführt, speichern muss später getriggert werden -> @check="...; save"
			@change="saveColumnSet">
			{{ c.name }}
		</ActionCheckbox>
	</ul>
</template>

<script>
// import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'
import ActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox'
// import { mapState, mapGetters } from 'vuex'

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
	methods: {
		saveColumnSet: function() {
			const request = {}
			request.contextFilter = this.contextFilter
			if (this.id) {
				request.entityFilter = {
					id: this.id,
				}
			}
			request.entityData = this.values
			console.debug('update column set', this.values)
			this.$store.dispatch('cesRequest', request).then(result => {
				console.debug('column set saved', result)
			})
		},
	},
}
</script>
