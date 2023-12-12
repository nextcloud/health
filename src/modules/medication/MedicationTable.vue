<!--
	- @copyright Copyright (c) 2020 Florian Steffens <flost-dev@mailbox.org>
	-
	- @author Marcus Nitzschke <mail@kendix.org>
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
			:entity-name="t('health', 'Medication')"
			:hide-range="true"
			@addItem="addItem"
			@updateItem="updateItem"
			@deleteItem="deleteItem"
		/>
	</div>
</template>

<script>
import DataTable from '../../shared/DataTable'

export default {
	name: 'MedicationTable',
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
					name: t('health', 'Name'),
					columnId: 'name',
					type: 'text',
					show: true,
				},
				{
					name: t('health', 'Identifier'),
					columnId: 'identifier',
					type: 'text',
					show: true,
				},
				{
					name: t('health', 'Morning'),
					columnId: 'morning',
					type: 'number',
					show: true,
				},
				{
					name: t('health', 'Noon'),
					columnId: 'noon',
					type: 'number',
					show: true,
				},
				{
					name: t('health', 'Evening'),
					columnId: 'evening',
					type: 'number',
					show: true,
				},
				{
					name: t('health', 'Night'),
					columnId: 'night',
					type: 'number',
					show: true,
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
			this.$store.dispatch('medicationDatasetsAppend', item)
		},
		updateItem(item) {
			// console.debug('update item', item)
			this.$store.dispatch('medicationDatasetsUpdate', item)
		},
		deleteItem(item) {
			// console.debug('delete item', item)
			this.$store.dispatch('medicationDatasetsDelete', item)
		},
	},
}
</script>
