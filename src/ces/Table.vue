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
		<h3>{{ t('health', 'Data', {}) }}</h3>
		<div v-if="loading" class="icon-loading">
			&nbsp;
		</div>
		<ModalAddItem
			v-if="!loading"
			:header="header"
			icon="icon-add"
			@addItem="addItem" />
		<table v-if="!loading">
			<thead>
				<tr>
					<th v-for="(h, i) in header" :key="i" :class="{ hide: !h.show }">
						{{ t('health', h.name, {}) }}
					</th>
					<th>
						{{ t('health', 'Actions', {}) }}
					</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="(d, i) in datasets" :key="i" :class="{ hideMobile: d.type === 'group' }">
					<td
						v-for="(h, ii) in header"
						:key="ii"
						:data-label="t('health', h.name, {})"
						:class="{ hide: !h.show }"
						:style="(h.style) ? h.style(d[h.columnId]): ''">
						<div v-if="d.type === 'group'" :class="{ group: true, year: d.period === 'year', week: d.period === 'week', day: d.period === 'day' }">
							{{ d[h.columnId] }}
						</div>
						<div v-else>
							<div v-if="h.type === 'date' && d[h.columnId]" class="wrapper">
								{{ d[h.columnId] | formatMyDate }}
							</div>
							<div v-else-if="h.type === 'datetime' && d[h.columnId]" class="wrapper">
								{{ d[h.columnId] | formatMyDatetime }}
							</div>
							<div v-else-if="h.type === 'select' && d[h.columnId]" class="wrapper">
								{{ (h.options && h.options[d[h.columnId].id] && h.options[d[h.columnId].id].label) ? h.options[d[h.columnId].id].label: 'no label found' }}
							</div>
							<div v-else-if="h.type === 'multiselect' && d[h.columnId]" class="wrapper">
								<ul>
									<li v-for="(option, optionIndex) in d[h.columnId]"
										:key="optionIndex">
										{{ (h.options && h.options[option.id] && h.options[option.id].label) ? h.options[option.id].label: 'no label found' }}
									</li>
								</ul>
							</div>
							<div v-else-if="h.type === 'longtext' && d[h.columnId]" class="wrapper">
								{{ d[h.columnId] }}
							</div>
							<div v-else-if="h.type === 'boolean' && d[h.columnId]" class="wrapper">
								{{ d[h.columnId] ? ('textTrue' in h) ? h.textTrue : t('health', 'true') : '' }}
								{{ !d[h.columnId] ? ('textFalse' in h) ? h.textFalse : t('health', 'false') : '' }}
							</div>
							<div v-else-if="h.type === 'text' && d[h.columnId]" class="wrapper">
								{{ d[h.columnId] }}
							</div>
							<div v-else-if="h.type === 'number' && d[h.columnId]" class="wrapper">
								{{ d[h.columnId] }}
							</div>
							<div v-else-if="h.type === 'calculate' && d" class="wrapper">
								{{ h.calc(d) }}
							</div>
						</div>
					</td>
					<td v-if="d.type === 'group'">
						<div class="group">
							{{ n('health', '%n item', '%n items', d.groupCount, {}) }}
						</div>
					</td>
					<td v-else>
						<div class="inlineButtons">
							<ModalAddItem
								:id="i"
								:header="header"
								:item-data="d"
								icon="icon-rename"
								@addItem="updateItem" />
						</div>
						<div class="inlineButtons">
							<button
								class="icon-delete"
								@click="deleteItem(i)" />
						</div>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</template>

<script>
import moment from '@nextcloud/moment'
import ModalAddItem from './ModalAddItem'

export default {
	name: 'Table',
	filters: {
		formatMyDate: function(v) {
			return moment(v).format('DD.MM.YYYY')
		},
		formatMyDatetime: function(v) {
			return moment(v).format('DD.MM.YYYY hh:mm a')
		},
	},
	components: {
		ModalAddItem,
	},
	props: {
		header: {
			type: Array,
			default: null,
		},
		data: {
			type: Array,
			default: null,
		},
		entityName: {
			type: String,
			default: t('health', 'item', {}),
		},
		loading: {
			type: Boolean,
			default: false,
		},
		groupBy: {
			type: Object,
			default: null,
		},
	},
	data: function() {
		return {
		}
	},
	computed: {
		datasets: function() {
			return this.data
		},
	},
	methods: {
		getItemsFromObject: function(o) {
			if (o instanceof Array) {
				return o
			} else {
				let data = null
				for (const [, v] of Object.entries(o)) {
					const items = this.getItemsFromObject(v)
					if (data === null) {
						data = items
					} else {
						data = items.concat(data)
					}
				}
				return data
			}
		},
		calcCount: function(items) {
			return items.length
		},
		deleteItem: function(id) {
			this.$emit('deleteItem', this.datasets[id].id)
		},
		addItem: function(item) {
			this.$emit('addItem', item)
		},
		updateItem: function(item) {
			console.debug('get id', {
				datasets: this.datasets,
				item: item,
				entity: this.datasets[item.id],
				id: this.datasets[item.id].id,
			})
			item.id = this.datasets[item.id].id
			this.$emit('updateItem', item)
		},
	},
}
</script>
<style lang="scss" scoped>
	* {
		box-sizing:border-box;
	}

	table {
		width: 97%;
		color: #2b2b2bd1;
	}

	table, td, tr, th {
		border-bottom: 1px solid gray;
		border-collapse: collapse;
		text-align: left;
		vertical-align: middle;
		color: var(--color-main-text);
	}

	td, tr, th {
		padding: 5px;
		vertical-align: top;
	}

	th {
		border-bottom: 2px solid gray;
		font-weight: bold;
		font-size: 0.92em;
		padding: 5px;
		text-align: left;
		vertical-align: middle;
	}

	tr:nth-child(even) {
		// background-color: #c5c5c585;
	}

	tr:hover {
		background: #80808063;
		opacity: 1;
	}

	tr:hover td {
		opacity: 1;
	}

	.datatable ul {
		list-style: square;
		padding-left: 20px;
	}

	@media screen and (max-width:700px) {

		table, tr, td {
			padding:0;
			border-bottom: none;
			// opacity: .7;
		}

		table {
			border:none;
		}

		thead {
			display:none;
		}

		tr {
			float: left;
			width: 100%;
			margin-bottom: 3em;
			margin-top: 3em;
		}

		td:first-child {
			border-top: gray 10px solid;
		}

		td {
			float: left;
			width: 100%;
			padding:1em;
		}

		td::before {
			content:attr(data-label);
			word-wrap: break-word;
			width: 45%;
			float:left;
			padding:1em;
			font-weight: bold;
			margin:-1em 1em -1em -1em;
		}

		.hideMobile {
			display: none;
		}

	}

	.hide {
		display: none;
	}

	.widthfitcontent {
		min-width: fit-content;
	}

	button {
		padding: 13px 20px 13px 20px;
	}

	textarea {
		width: 160px;
		height: 100px;
	}

	.group {
		font-weight: bold;
		margin-top: 30px;
		opacity: 0.5;
	}

	.group.year {
		font-size: x-large;
	}

	.group.week {
		font-size: large;
	}

	.inlineButtons {
		float: left;
	}

</style>
