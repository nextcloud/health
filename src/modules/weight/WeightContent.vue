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
		<div
			v-if="!loading">
			<div v-if="!minWeightDataset || !person.size || !person.age">
				{{ t('health', 'To calculate your BMI, please set your weight in the table below and your age and height in the person settings.') }}
			</div>
			<div class="row">
				<div class="col-1 hide-s">
					{{ t('health', 'minimum', {}) }}
					<WeightBmi
						v-if="minWeightDataset && person.size && person.age"
						:title="t('health', 'minimum')"
						:date="minWeightDataset ? minWeightDataset.date : null"
						:weight="minWeightDataset ? minWeightDataset.weight : null"
						:person="person" />
				</div>
				<div class="col-1" style="font-weight: bold;">
					{{ t('health', 'actual', {}) }}
					<WeightBmi
						v-if="minWeightDataset && person.size && person.age"
						:title="t('health', 'actual')"
						:show-base-info="false"
						:date="lastWeightDataset ? lastWeightDataset.date : null"
						:weight="lastWeightDataset ? lastWeightDataset.weight : null"
						:person="person" />
				</div>
				<div class="col-1 hide-s">
					{{ t('health', 'maximum', {}) }}
					<WeightBmi
						v-if="minWeightDataset && person.size && person.age"
						:title="t('health', 'maximum')"
						:show-base-info="false"
						:date="maxWeightDataset ? maxWeightDataset.date : null"
						:weight="maxWeightDataset ? maxWeightDataset.weight : null"
						:person="person" />
				</div>
			</div>
			{{ t('health', 'The calculated value is valid only for adults. Its base are the tables from the WHO.') }}
		</div>
		<div v-if="loading" class="icon-loading" />

		<h3>{{ t('health', 'Target', {}) }}</h3>
		<div
			v-if="!loading">
			<TextResultsLooseWeight
				v-if="lastWeight && weightTarget && weightTargetInitialWeight && weightTarget <= weightTargetInitialWeight"
				:bounty="weightTargetBounty"
				:initial-weight="weightTargetInitialWeight"
				:target="weightTarget"
				:weight="lastWeight"
				:unit="weightUnit" />
			<TextResultsGainWeight
				v-else-if="lastWeight && weightTarget && weightTargetInitialWeight && weightTarget > weightTargetInitialWeight"
				:bounty="weightTargetBounty"
				:initial-weight="weightTargetInitialWeight"
				:target="weightTarget"
				:weight="lastWeight"
				:unit="weightUnit" />
			<TextResultsNoData v-else />
		</div>
		<div v-if="loading" class="icon-loading" />

		<h3>{{ t('health', 'Chart', {}) }}</h3>
		<WeightChart
			v-if="!loading"
			:person="person"
			:data="weightData" />
		<div v-if="loading" class="icon-loading" />

		<h3>{{ t('health', 'Data', {}) }}</h3>
		<WeightTable
			v-if="!loading"
			:data="weightData"
			:person="person" />
		<div v-if="loading" class="icon-loading" />
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import WeightBmi from './WeightBmi'
import TextResultsLooseWeight from './contentParts/TextResultsLooseWeight'
import TextResultsGainWeight from './contentParts/TextResultsGainWeight'
import TextResultsNoData from './contentParts/TextResultsNoData'
import WeightTable from './WeightTable'
import WeightChart from './WeightChart'

export default {
	name: 'WeightContent',
	components: {
		WeightChart,
		WeightBmi,
		TextResultsLooseWeight,
		TextResultsGainWeight,
		TextResultsNoData,
		WeightTable,
	},
	computed: {
		...mapState(['activeModule', 'showSidebar', 'rWeightDatasets', 'loading']),
		...mapGetters(['person']),
		getProgressbarValue() {
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
		lastWeight() {
			if (this.weightData && this.weightData.length >= 1) {
				return this.weightData[0].weight
			}
			return null
		},
		lastWeightDataset() {
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
	},
}
</script>
