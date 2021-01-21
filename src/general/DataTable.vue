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
	- columnTypes: date, datetime, select, multiselect, longtext, boolean, text, number, calculate
	-
	-->

<template>
	<div style="margin-bottom: 100px;">
		<div v-if="loading" class="icon-loading" />
		<ModalItem
			v-if="!loading"
			:header="header"
			icon="icon-add"
			:entity-name="entityName"
			@addItem="addItem" />
		<table v-if="!loading">
			<thead>
				<tr>
					<th v-for="(h, i) in header" :key="i" :class="{ hide: !h.show }">
						{{ h.name }}
					</th>
					<th>
						{{ t('health', 'Actions', {}) }}
					</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="(d, i) in datasets" :key="i">
					<td
						v-for="(h, ii) in header"
						:key="ii"
						:data-label="h.name"
						:class="{ hide: !h.show }"
						:style="(h.style) ? h.style(d[h.columnId]): ''">
						<div>
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
					<td>
						<div class="inlineButtons">
							<ModalItem
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
		<EmptyContent
			v-if="!datasets || datasets.length === 0 && !loading"
			icon="icon-category-monitoring">
			No data yet
			<template #desc>
				{{ t('health', 'Click at the + to add the first data.') }}
				<ModalItem
					:header="header"
					icon="icon-add"
					@addItem="addItem" />
			</template>
		</EmptyContent>
	</div>
</template>

<script>
import ModalItem from './ModalItem'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'

export default {
	name: 'DataTable',
	filters: {
		formatMyDate: function(v) {
			return new Date(v).toLocaleDateString()
		},
		formatMyDatetime: function(v) {
			return new Date(v).toLocaleTimeString()
		},
	},
	components: {
		ModalItem,
		EmptyContent,
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
		calcCount: function(items) {
			return items.length
		},
		deleteItem: function(id) {
			this.$emit('deleteItem', this.datasets[id])
		},
		handleDataIncome(item) {
			if ('id' in item) {
				console.debug('id found -> update item', item)
				this.updateItem(item)
			} else {
				console.debug('id not found -> add item', item)
				this.addItem(item)
			}
		},
		addItem: function(item) {
			this.$emit('addItem', item)
		},
		updateItem: function(item) {
			this.$emit('updateItem', item)
		},
	},
}
</script>
<style lang="scss" scoped>

	.empty-content {
		margin-top: 0 !important;
		margin-bottom: 20px;
		text-align: center;
	}

	* {
		box-sizing:border-box;
	}

	table {
		width: 97%;
		color: #2b2b2bd1;
		margin-bottom: 20px;
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
