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
		<h2>
			{{ t('health', 'Weight', {}) }}
		</h2>

		<h3>
			{{ t('health', 'Body mass index (BMI)') }}
		</h3>
		<div v-if="!minWeightDataset || !person.size || !person.age">
			{{ t('health', 'To calculate your BMI, please set your weight in the table below and you age and size in the person settings.') }}
		</div>
		<table v-if="minWeightDataset && person.size && person.age">
			<tr>
				<td style="font-weight: 200; padding-left: 10px;">
					{{ t('health', 'minimum', {}) }}
				</td>
				<td style="font-weight: bold; padding-left: 10px;">
					{{ t('health', 'actual', {}) }}
				</td>
				<td style="font-weight: 200; padding-left: 10px;">
					{{ t('health', 'maximum', {}) }}
				</td>
			</tr>
			<tr>
				<td>
					<WeightBmi
						v-if="minWeightDataset && person.size && person.age"
						:title="t('health', 'minimum')"
						:size="person.size"
						:date="minWeightDataset ? minWeightDataset.date : null"
						:age="person.age"
						:weight="minWeightDataset ? minWeightDataset.weight : null"
						:unit="weightUnit" />
				</td>
				<td>
					<WeightBmi
						v-if="minWeightDataset && person.size && person.age"
						:title="t('health', 'actual')"
						:show-base-info="false"
						:size="person.size"
						:age="person.age"
						:date="lastWeightDataset ? lastWeightDataset.date : null"
						:weight="lastWeightDataset ? lastWeightDataset.weight : null"
						:unit="weightUnit" />
				</td>
				<td>
					<WeightBmi
						v-if="minWeightDataset && person.size && person.age"
						:title="t('health', 'maximum')"
						:show-base-info="false"
						:size="person.size"
						:age="person.age"
						:date="maxWeightDataset ? maxWeightDataset.date : null"
						:weight="maxWeightDataset ? maxWeightDataset.weight : null"
						:unit="weightUnit" />
				</td>
			</tr>
			<tr>
				<td colspan="3">
					{{ t('health', 'The calculated value is valid only for adults. Its base are the tables from the WHO.') }}
				</td>
			</tr>
		</table>

		<h3>Target</h3>
		<TextResultsLooseWeight
			v-if="lastWeight && weightTarget <= weightTargetInitialWeight"
			:bounty="weightTargetBounty"
			:initial-weight="weightTargetInitialWeight"
			:target="weightTarget"
			:weight="lastWeight"
			:unit="weightUnit" />
		<TextResultsGainWeight
			v-else-if="lastWeight && weightTarget > weightTargetInitialWeight"
			:bounty="weightTargetBounty"
			:initial-weight="weightTargetInitialWeight"
			:target="weightTarget"
			:weight="lastWeight"
			:unit="weightUnit" />
		<TextResultsNoData v-else />

		<h3>Data</h3>
		<DataTable
			:data="weightData"
			:header="headerDefinition"
			entity-name="Weight dataset"
			@addItem="addItem"
			@updateItem="updateItem"
			@deleteItem="deleteItem" />
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import WeightBmi from './WeightBmi'
import TextResultsLooseWeight from './contentParts/TextResultsLooseWeight'
import TextResultsGainWeight from './contentParts/TextResultsGainWeight'
import TextResultsNoData from './contentParts/TextResultsNoData'
import DataTable from '../../general/DataTable'

export default {
	name: 'WeightContent',
	components: {
		WeightBmi,
		TextResultsLooseWeight,
		TextResultsGainWeight,
		TextResultsNoData,
		DataTable,
	},
	data: function() {
		return {
			dataInsertInfo: '',
			dataTableHighlight: null,
			chartDateRange: 'week',
		}
	},
	computed: {
		...mapState(['activeModule', 'showSidebar', 'rWeightDatasets']),
		...mapGetters(['person']),
		getProgressbarValue: function() {
			const lostAlready = this.weightTargetInitialWeight - this.lastWeight
			const wantToLost = this.weightTargetInitialWeight - this.weightTarget
			const result = Math.round((100 - ((lostAlready / wantToLost)) * 100) * 100) / 100
			return (result > 100) ? 100 : result
		},
		weightTargetInitialWeight() {
			return 'weightTargetInitialWeight' in this.person ? this.person.weightTargetInitialWeight : null
		},
		weightTarget() {
			return 'weightTarget' in this.person ? this.person.weightTarget : null
		},
		weightTargetBounty() {
			return 'weightTargetBounty' in this.person ? this.person.weightTargetBounty : null
		},
		weightUnit() {
			return 'weightUnit' in this.person ? this.person.weightUnit : null
		},
		lastWeight: function() {
			if (this.weightData && this.weightData.length >= 1) {
				return this.weightData[0].weight
			}
			return null
		},
		lastWeightDataset: function() {
			if (!this.weightData || this.weightData.length === 0) {
				return null
			}
			let i = 0
			while (this.weightData && this.weightData.length >= 1 && !this.weightData[i].weight) {
				i++
			}
			if (i > 0 || (i === 0 && this.weightData[i].weight)) {
				return this.weightData[i]
			} else {
				return null
			}
		},
		minWeightDataset() {
			if (this.weightData === null || this.weightData.length === 0) {
				return null
			}
			let min = null
			this.weightData.forEach(item => {
				if (item.weight && (min === null || min.weight > item.weight)) {
					min = item
				}
			})
			return min
		},
		maxWeightDataset() {
			if (this.weightData === null || this.weightData.length === 0) {
				return null
			}
			let max = null
			this.weightData.forEach(item => {
				if (item.weight && (max === null || max.weight < item.weight)) {
					max = item
				}
			})
			return max
		},
		weightData() {
			return !this.rWeightDatasets
				? null
				// eslint-disable-next-line vue/no-side-effects-in-computed-properties
				: this.rWeightDatasets.sort(function(a, b) {
					return new Date(b.date) - new Date(a.date)
				})
		},
		headerDefinition() {
			return [
				{
					name: t('health', 'Date'),
					columnId: 'date',
					type: 'date',
					show: true,
					default: function() {
						return new Date().toISOString().slice(0, 10)
					},
				},
				{
					name: t('health', 'Weight'),
					columnId: 'weight',
					type: 'number',
					show: true,
				},
				{
					name: t('health', 'Bodyfat'),
					columnId: 'bodyfat',
					type: 'number',
					show: true,
				},
				{
					name: 'weightMeasurementName' in this.person && this.person.weightMeasurementName ? this.person.weightMeasurementName : t('health', 'Measurement'),
					columnId: 'measurement',
					type: 'number',
					show: true,
				},
			]
		},
	},
	methods: {
		addItem(item) {
			// console.debug('add item', item)
			this.$store.dispatch('rWeightDatasetsAppend', item)
		},
		updateItem(item) {
			// console.debug('update item', item)
			this.$store.dispatch('rWeightDatasetsUpdate', item)
		},
		deleteItem(item) {
			// console.debug('delete item', item)
			this.$store.dispatch('rWeightDatasetsDelete', item)
		},
	},
}
</script>
