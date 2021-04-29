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
			:entity-name="t('health', 'smoking data')"
			@addItem="addItem"
			@updateItem="updateItem"
			@deleteItem="deleteItem" />
	</div>
</template>

<script>
import DataTable from '../../general/DataTable'

export default {
	name: 'SmokingTable',
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
					columnId: 'date',
					type: 'date',
					show: true,
					default() {
						return new Date().toISOString().slice(0, 10)
					},
				},
				{
					name: t('health', 'Cigarettes'),
					columnId: 'cigarettes',
					type: 'number',
					show: this.person.smokingColumnCigarettes,
				},
				{
					name: t('health', 'Withdrawal symptoms'),
					columnId: 'desireLevel',
					type: 'select',
					options: [
						{ id: 0, label: t('health', 'None') },
						{ id: 1, label: t('health', 'Noticeable') },
						{ id: 2, label: t('health', 'Some') },
						{ id: 3, label: t('health', 'Moderate') },
						{ id: 4, label: t('health', 'Strong') },
						{ id: 5, label: t('health', 'Very strong') },
						{ id: 6, label: t('health', 'Extreme') },
					],
					show: this.person.smokingColumnDesireLevel,
				},
				{
					name: t('health', 'Saved money'),
					columnId: 'savedMoney',
					type: 'calculate',
					calc(dataset) {
						// console.debug('calc', { this: this, dataset: dataset })
						if (!this.calcData.price || !this.calcData.start) {
							return null
						} else if (dataset.cigarettes >= this.calcData.start) {
							return t('health', 'Nothing saved')
						} else {
							const count = this.calcData.start - dataset.cigarettes
							const currency = this.calcData.currency ? this.calcData.currency : ''
							return '' + Math.round(count * this.calcData.price * 100) / 100 + ' ' + currency
						}
					},
					calcData: {
						price: this.person.smokingPrice,
						currency: this.person.currency,
						start: this.person.smokingStartValue,
					},
					show: this.person.smokingColumnSavedMoney,
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
			this.$store.dispatch('smokingDatasetsAppend', item)
		},
		updateItem(item) {
			// console.debug('update item', item)
			this.$store.dispatch('smokingDatasetsUpdate', item)
		},
		deleteItem(item) {
			// console.debug('delete item', item)
			this.$store.dispatch('smokingDatasetsDelete', item)
		},
	},
}
</script>
