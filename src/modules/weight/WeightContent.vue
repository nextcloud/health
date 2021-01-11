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
		<div>
			<WeightBmi
				:size="person.size"
				:age="person.age"
				:weight="lastWeight" />
		</div>

		<h3>Target</h3>

		<TextResultsLooseWeight
			v-if="lastWeight && person.weightTarget <= person.weightTargetInitialWeight"
			:bounty="person.weightTargetBounty"
			:initial-weight="person.weightTargetInitialWeight"
			:target="person.weightTarget"
			:weight="lastWeight"
			:unit="person.weightUnit" />
		<TextResultsGainWeight
			v-else-if="lastWeight && person.weightTarget > person.weightTargetInitialWeight"
			:bounty="person.weightTargetBounty"
			:initial-weight="person.weightTargetInitialWeight"
			:target="person.weightTarget"
			:weight="lastWeight"
			:unit="person.weightUnit" />
		<TextResultsNoData v-else />

		<h3>Chart</h3>
		<WeightChart
			v-show="weightData"
			:person="person"
			:data="weightData" />
		<h3>Data</h3>
		<WeightTable
			v-show="weightData" />
		<p v-show="dataInsertInfo" class="dataInsertInfo">
			{{ dataInsertInfo }}
		</p>
	</div>
</template>

<script>
import WeightTable from './WeightTable.vue'
import WeightChart from './WeightChart'
import { mapState, mapGetters } from 'vuex'
import WeightBmi from './WeightBmi'
import TextResultsLooseWeight from './contentParts/TextResultsLooseWeight'
import TextResultsGainWeight from './contentParts/TextResultsGainWeight'
import TextResultsNoData from './contentParts/TextResultsNoData'

export default {
	name: 'WeightContent',
	components: {
		WeightTable,
		WeightChart,
		WeightBmi,
		TextResultsLooseWeight,
		TextResultsGainWeight,
		TextResultsNoData,
	},
	data: function() {
		return {
			dataInsertInfo: '',
			dataTableHighlight: null,
			chartDateRange: 'week',
		}
	},
	computed: {
		...mapState(['activeModule', 'showSidebar', 'weightData']),
		...mapGetters(['person']),
		getProgressbarValue: function() {
			const lostAlready = this.person.weightTargetInitialWeight - this.lastWeight
			const wantToLost = this.person.weightTargetInitialWeight - this.person.weightTarget
			const result = Math.round((100 - ((lostAlready / wantToLost)) * 100) * 100) / 100
			return (result > 100) ? 100 : result
		},
		lastWeight: function() {
			if (this.weightData && this.weightData.length >= 1) {
				return this.weightData[0].weight
			}
			return null
		},
	},
}
</script>
