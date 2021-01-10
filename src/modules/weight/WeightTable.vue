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
	<div class="responsive">
		<table>
			<thead>
				<th>
					Date
				</th>
				<th>
					Weight <span>in {{ person.weightUnit }}</span>
				</th>
				<th>
					Waist <span>in cm</span>
				</th>
				<th>
					Hip <span>in cm</span>
				</th>
				<th v-if="hasMeasurement" class="hide-if-small">
					{{ person.weightMeasurementName }}
				</th>
				<th class="hide-if-small">
					Bodyfat <span>in %</span>
				</th>
				<th>
					Actions
					<button class="icon-add" @click="showAddRow = true" />
				</th>
			</thead>
			<tbody>
				<tr v-show="showAddRow">
					<td>
						<input
							ref="weightinsertdate"
							:value="today"
							type="Date"
							class="widthfitcontent">
					</td>
					<td>
						<input
							ref="weightinsertweight"
							type="Number"
							min="1"
							max="200"
							step="0.1">
					</td>
					<td>
						<input
							ref="weightinsertwaist"
							type="Number"
							min="1"
							max="200"
							step="0.1">
					</td>
					<td>
						<input
							ref="weightinserthip"
							type="Number"
							min="1"
							max="200"
							step="0.1">
					</td>
					<td v-if="hasMeasurement" class="hide-if-small">
						<input ref="weightinsertmeasurement" type="Number">
					</td>
					<td class="hide-if-small">
						<input
							ref="weightinsertbodyfat"
							type="Number"
							min="0"
							max="100"
							step="0.1">
					</td>
					<td>
						<button
							class="icon-checkmark"
							@click="insertTableData()" />
						<button
							class="icon-close"
							@click="showAddRow = false" />
					</td>
				</tr>
				<tr v-for="(v, i) in data"
					:key="i">
					<td>
						<div v-if="editRowId === i">
							<input
								ref="weightinputdate"
								:value="v.date"
								type="Date"
								class="widthfitcontent">
						</div>
						<div v-else>
							{{ v.date | formatMyDate }}
						</div>
					</td>
					<td>
						<div v-if="editRowId === i">
							<input
								ref="weightinputweight"
								:value="v.weight"
								type="Number"
								min="1"
								max="200"
								step="0.1">
						</div>
						<div v-else>
							{{ v.weight }}
						</div>
					</td>
					<td>
						<div v-if="editRowId === i">
							<input
								ref="weightinputwaist"
								:value="v.waist"
								type="Number"
								min="1"
								max="200"
								step="0.1">
						</div>
						<div v-else>
							{{ v.waist }}
						</div>
					</td>
					<td>
						<div v-if="editRowId === i">
							<input
								ref="weightinputhip"
								:value="v.hip"
								type="Number"
								min="1"
								max="200"
								step="0.1">
						</div>
						<div v-else>
							{{ v.hip }}
						</div>
					</td>
					<td v-if="hasMeasurement" class="hide-if-small">
						<div v-if="editRowId === i">
							<input ref="weightinputmeasurement" :value="v.measurement" type="Number">
						</div>
						<div v-else>
							{{ v.measurement }}
						</div>
					</td>
					<td class="hide-if-small">
						<div v-if="editRowId === i">
							<input
								ref="weightinputbodyfat"
								:value="v.bodyfat"
								type="Number"
								min="0"
								max="100"
								step="0.1">
						</div>
						<div v-else>
							{{ v.bodyfat }}
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
							@click="editRowId = i" />
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
// import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'
import moment from '@nextcloud/moment'
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'WeightTable',
	components: {
		// ActionInput,
	},
	filters: {
		formatMyDate: function(v) {
			return moment(v).format('DD.MM.YYYY')
		},
	},
	data: function() {
		return {
			editRowId: null,
			showAddRow: false,
		}
	},
	computed: {
		...mapState(['activeModule', 'showSidebar', 'weightData']),
		...mapGetters(['person']),
		data: function() {
			return this.weightData
		},
		hasMeasurement: function() {
			if (this.person.weightMeasurementName === '' || this.person.weightMeasurementName === null) {
				return false
			}
			return true
		},
		today: function() {
			return moment().format('YYYY-MM-DD')
		},
	},
	methods: {
		updateTableData() {
			console.debug('updateTableData refs:')
			console.debug(this.$refs)
			const row = {
				id: this.editRowId,
				weight: this.$refs.weightinputweight[0].value,
				waist: this.$refs.weightinputwaist[0].value,
				hip: this.$refs.weightinputhip[0].value,
				measurement: (this.$refs.weightinputmeasurement !== undefined) ? this.$refs.weightinputmeasurement[0].value : null,
				date: this.$refs.weightinputdate[0].value,
				bodyfat: this.$refs.weightinputbodyfat[0].value,
			}
			console.debug(row)
			this.$store.dispatch('updateWeightData', row)
			this.editRowId = null
		},
		insertTableData: function() {
			console.debug('insertTableData refs:')
			console.debug(this.$refs)
			const row = {
				weight: this.$refs.weightinsertweight.value,
				waist: this.$refs.weightinsertwaist.value,
				hip: this.$refs.weightinserthip.value,
				measurement: (this.$refs.weightinsertmeasurement !== undefined) ? this.$refs.weightinsertmeasurement.value : null,
				date: this.$refs.weightinsertdate.value,
				bodyfat: this.$refs.weightinsertbodyfat.value,
			}
			console.debug(row)
			this.$refs.weightinsertweight.value = ''
			this.$refs.weightinsertwaist.value = ''
			this.$refs.weightinserthip.value = ''
			if (this.$refs.weightinsertmeasurement !== undefined) {
				this.$refs.weightinsertmeasurement.value = ''
			}
			this.$refs.weightinsertdate.value = ''
			this.$refs.weightinsertbodyfat.value = ''
			this.$store.dispatch('insertWeightData', row)
			this.showAddRow = false
		},
		deleteDataRow: function(i) {
			this.$store.dispatch('deleteWeightDataRow', i)
		},
	},
}
</script>
<style lang="scss" scoped>
	.responsive {
		overflow-y: auto;
		margin-bottom: 50px;
	}

	table {
		min-width: 98%;
		color: #2b2b2bd1;
	}

	tr:nth-child(even) {
		background-color: #c5c5c585;
	}

	tr:hover {
		background: #80808063;
	}

	th {
		border-bottom: 2px solid gray;
		font-weight: bold;
		font-size: 0.92em;
		padding: 5px;
		text-align: left;
		vertical-align: middle;
	}

	td {
		border-bottom: 1px solid gray;
		padding: 5px;
		text-align: left;
		vertical-align: middle;
	}

	button {
		padding: 13px 20px 13px 20px;
	}
	@media screen and (max-width: 500px) {
		.hide-if-small {
			display:none;
		}
	}

	.widthfitcontent {
		min-width: fit-content;
	}
</style>
