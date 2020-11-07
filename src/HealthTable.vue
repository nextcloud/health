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
		<table>
			<thead>
				<tr>
					<th v-for="(h, i) in header" :key="i" :class="{ hide: !h.show }">
						{{ t('health', h.name, {}) }}
					</th>
					<th>{{ t('health', 'Actions', {}) }}</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="(d, i) in data" :key="i">
					<td
						v-for="(h, ii) in header"
						:key="ii"
						:data-label="t('health', h.name, {})"
						:class="{ hide: !h.show }">
						<div v-if="h.type === 'date'">
							<div v-if="editRowId !== i">
								{{ d[ii] | formatMyDate }}
							</div>
							<input
								v-if="editRowId === i"
								:value="values[ii]"
								type="date"
								class="widthfitcontent">
						</div>
						<div v-else-if="h.type === 'datetime'">
							<div v-if="editRowId !== i">
								{{ d[ii] | formatMyDatetime }}
							</div>
							<input
								v-if="editRowId === i"
								:value="values[ii]"
								type="datetime-local"
								class="widthfitcontent">
						</div>
						<div v-else-if="h.type === 'select'" class="wrapper">
							<div v-if="editRowId === i">
								<Multiselect
									v-model="values[ii]"
									:options="h.options"
									track-by="id"
									label="label" />
							</div>
							<div v-if="editRowId !== i">
								{{ h.options[d[ii]].label }}
							</div>
						</div>
						<div v-else-if="h.type === 'multiselect'" class="wrapper">
							<div v-if="editRowId === i">
								<Multiselect
									v-model="values[ii]"
									:options="h.options"
									:multiple="true"
									track-by="id"
									label="label" />
							</div>
							<div v-if="editRowId !== i">
								{{ d[ii] }}
							</div>
						</div>
						<div v-else-if="h.type === 'boolean'">
							<input
								id="h.columnId"
								type="checkbox"
								value="h.columnId"
								:checked="values[ii]"
								:disabled="editRowId !== i">
							<label :for="h.columnId">{{ h.name }}</label>
						</div>
						<div v-else>
							<div v-if="editRowId === i">
								{{ d[ii] }}
							</div>
							<div v-if="editRowId !== i">
								<ActionInput
									:type="text"
									:value="values[ii]"
									icon="icon-edit" />
							</div>
						</div>
					</td>
					<td>
						<button
							v-if="editRowId === i"
							class="icon-checkmark"
							@click="updateTableData()" />
						<button
							v-if="editRowId === null"
							class="icon-rename"
							@click="setEdit(i)" />
						<button
							v-if="editRowId === null"
							class="icon-delete"
							@click="deleteDataRow(i)" />
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</template>

<script>
import moment from '@nextcloud/moment'
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'

export default {
	name: 'HealthTable',
	filters: {
		formatMyDate: function(v) {
			return moment(v).format('DD.MM.YYYY')
		},
		formatMyDatetime: function(v) {
			return moment(v).format('DD.MM.YYYY HH:MM')
		},
	},
	components: {
		Multiselect,
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
	},
	data: function() {
		return {
			editRowId: null,
			values: {},
		}
	},
	computed: {
	},
	methods: {
		updateTableData: function() {
			this.editRowId = null
			console.debug('send row for updating from health table')
			console.debug(this.values)
		},
		deleteDataRow: function() {
			console.debug('send row-id for deleting from health table')
			console.debug(this.editRowId)
			this.editRowId = null
		},
		setEdit: function(i) {
			this.values = this.data[i].slice()
			this.editRowId = i
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
	}
	td, tr, th {
		padding: 5px;
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
		background-color: #c5c5c585;
	}
	tr:hover {
		background: #80808063;
	}
	@media screen and (max-width:700px) {

		table, tr, td {
			padding:0;
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
			margin-bottom: 2em;
		}

		td {
			float: left;
			width: 100%;
			padding:1em;
		}

		td::before {
			content:attr(data-label);
			word-wrap: break-word;
			width: 20%;
			float:left;
			padding:1em;
			font-weight: bold;
			margin:-1em 1em -1em -1em;
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
</style>
