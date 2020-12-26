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
	<div class="datatable">
		<Table
			:header="header"
			:data="dataForTable"
			:loading="loading"
			:entity-name="entityName"
			:group-by="groupBy"
			@addItem="addItem"
			@updateItem="updateItem"
			@deleteItem="deleteItem" />
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import moment from '@nextcloud/moment'
import Table from '../../ces/Table'

export default {
	name: 'DataTable',
	components: {
		Table,
	},
	props: {
		contextFilter: {
			type: Object,
			default: null,
		},
		entityName: {
			type: String,
			default: t('health', 'item', {}),
		},
		header: {
			type: Array,
			default: function() { return [] },
		},
		groupBy: {
			type: Object,
			// eslint-disable-next-line vue/require-valid-default-prop
			default: {
				day: false,
				week: false,
				year: false,
				dateColumnsId: 'datetime',
			},
		},
	},
	data: function() {
		return {
			loading: true,
		}
	},
	computed: {
		...mapState(['activePersonId', 'moduleData']),
		...mapGetters(['person']),
		dataForTable: function() {
			const data = []
			this.datasets.forEach(d => {
				const tmp = JSON.parse(d.data)
				tmp.id = d.id
				data.push(tmp)
			})
			return data
		},
		datasets: function() {
			if (this.moduleData[this.contextFilter.module] && this.moduleData[this.contextFilter.module][this.contextFilter.type]) {
				return this.moduleData[this.contextFilter.module][this.contextFilter.type]
			} else {
				return []
			}
		},
	},
	watch: {
		activePersonId: function() {
			console.debug('person changed, load new datasets')
			this.loadDatasets()
		},
	},
	mounted() {
		this.loadDatasets()
	},
	methods: {
		addItem: function(item) {
			// console.debug('new item', item)

			item.personId = this.person.id

			const cesRequest = {}
			cesRequest.contextFilter = this.contextFilter
			cesRequest.contextFilter.type = 'datasets'
			cesRequest.entityData = item

			this.$store.dispatch('cesRequest', cesRequest).then(newItem => {
				console.debug('saved item', newItem)
				// this.datasets.push(newItem[0])
				// this.sortDatasets()
				this.loadDatasets()
			})
		},
		updateItem: function(item) {
			// console.debug('update item', item)

			const cesRequest = {}
			cesRequest.contextFilter = this.contextFilter
			cesRequest.contextFilter.type = 'datasets'
			cesRequest.entityFilter = {
				id: item.id,
			}
			cesRequest.entityData = item.data

			this.$store.dispatch('cesRequest', cesRequest).then(result => {
				console.debug('item updated', result)
				this.loadDatasets()
				// const datasets = this.datasets.slice()
				// result.forEach(r => {
				// datasets[item.id] = r
				// })
				// this.datasets = datasets
				// this.sortDatasets()
			})
		},
		deleteItem: function(id) {
			// const entityId = this.datasets[id].id
			const entityId = id
			console.debug('delete item with id', entityId)

			const cesRequest = {}
			cesRequest.contextFilter = this.contextFilter
			cesRequest.contextFilter.type = 'datasets'
			cesRequest.entityFilter = {
				id: entityId,
				remove: true,
			}

			this.$store.dispatch('cesRequest', cesRequest).then(result => {
				console.debug('item deleted', result)
				this.loadDatasets()
				// this.datasets.splice(id, 1)
			})
		},
		loadDatasets: function() {
			// this.loading = true
			const cesRequest = {}
			cesRequest.contextFilter = this.contextFilter
			cesRequest.contextFilter.type = 'datasets'
			cesRequest.entityFilter = {
				personId: this.person.id,
			}
			this.$store.dispatch('cesRequest', cesRequest).then(result => {
				result.sort(function(a, b) {
					const dataA = JSON.parse(a.data)
					const dataB = JSON.parse(b.data)

					if (moment(dataA.datetime) > moment(dataB.datetime)) {
						return -1
					} else if (moment(dataA.datetime) < moment(dataB.datetime)) {
						return 1
					} else {
						return 0
					}
				})
				const data = {
					module: this.contextFilter.module,
					type: this.contextFilter.type,
				}
				data.data = result
				this.$store.dispatch('updateModuleData', data)
				// this.datasets = result
				// this.sortDatasets()
				this.loading = false
			})
		},
	},
}
</script>
<style lang="scss" scoped>
	.textarea-mission {
		width: 67%;
		min-height: 200px;
	}

	.content-wrapper-health {
		width: 98%;
	}

	.widget {
		border: 1px solid gray;
		border-radius: 4px;
		background-color: #80808026;
		padding: 4px;
		width: 100px;
		margin: 10px;
		float: left;
	}

	.widget h3 {
		margin-top: 5px;
		margin-bottom: 2px;
		font-size: large;
	}

	.widget .date {
		color: gray;
		font-size: 0.8em;
		text-align: right;
	}

	.widget span {
		padding-left: 2px;
		padding-right: 2px;
	}

	.widget .firstNumber {
		font-weight: bold;
		text-align: right;
	}

	.widget .secondNumber {
		text-align: right;
	}

	.clear {
		clear: both;
	}
</style>
