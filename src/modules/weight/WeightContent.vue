<!--
	- @copyright Copyright (c) 2019 Florian Steffens
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
		<h2>Weight<span>for {{ person.name }}</span></h2>
		<div v-if="person.weight.weightTarget != ''">
			<h3>Target</h3>
			<p>You started with {{ person.weight.weightTargetInitialWeight }}{{ person.weight.unit }} for your target. Your actual weight is now {{ getLastWeight }}{{ person.weight.unit }} and your target values {{ person.weight.weightTarget }}{{ person.weight.unit }}.</p>
			<p
				v-if="getLastWeight - person.weight.weightTarget >= 0">
				So you lost already {{ person.weight.weightTargetInitialWeight - getLastWeight }}{{ person.weight.unit }} and you have {{ getLastWeight - person.weight.weightTarget }}{{ person.weight.unit }} to go.
			</p>
			<p
				v-if="getLastWeight - person.weight.weightTarget >= 0">
				Go on and eliminate the blue bar:
				<ProgressBar
					:value="getProgressbarValue"
					:class="{'small':true}" />
			</p>
			<p
				v-else>
				Good, you reached your target!
			</p>
		</div>
		<div v-else>
			If you want, you can set a target for your weight. Do this in the settings in the sidebar. You will see right here how much you lost and what is left. Maybe you can be motivated this way.
		</div>
		<h3>Chart</h3>
		<h3>Data</h3>
		<VueTableDynamic
			ref="table"
			:params="getDatatableData" />
	</div>
</template>

<script>
import ProgressBar from '@nextcloud/vue/dist/Components/ProgressBar'
import VueTableDynamic from 'vue-table-dynamic'

export default {
	name: 'WeightContent',
	components: {
		ProgressBar,
		VueTableDynamic,
	},
	props: {
		person: {
			type: Object,
			default: null,
		},
	},
	data: function() {
		return {
			params: {
				data: [
					['zeile 1', 'z2', 'letzeres'],
					['Cell-1', 'Cell-2', 'Cell-3'],
					['Cell-4', 'Cell-5', 'Cell-6'],
					['Cell-7', 'Cell-8', 'Cell-9'],
				],
				header: 'row',
				border: true,
				stripe: true,
				sort: [0, 1, 2],
				pagination: true,
				pageSize: 10,
				pageSizes: [10, 20, 50],
				edit: { row: 'all' },
			},
			data: [
				{
					date: '01/08/2020',
					weight: 80,
					measurement: 30,
					bodyfat: 20,
				},
				{
					date: '02/08/2020',
					weight: 80,
					measurement: 30,
					bodyfat: 17,
				},
				{
					date: '03/08/2020',
					weight: 82,
					measurement: 29,
					bodyfat: 17,
				},
				{
					date: '04/08/2020',
					weight: 81,
					measurement: 15,
					bodyfat: 16,
				},
				{
					date: '05/08/2020',
					weight: 79.5,
					measurement: 16,
					bodyfat: 15,
				},
			],
		}
	},
	computed: {
		getLastWeight: function() {
			return 80
		},
		getProgressbarValue: function() {
			return 40
		},
		getDatatableData: function() {
			const data = []
			if (this.person.weight.measurementName) {
				data.push(['Date', 'Weight', this.person.weight.measurementName, 'Bodyfat'])
			} else {
				data.push(['Date', 'Weight', 'Bodyfat'])
			}
			for (let i = 0; i < this.data.length; ++i) {
				if (this.person.weight.measurementName) {
					data.push([this.data[i].date, this.data[i].weight, this.data[i].measurement, this.data[i].bodyfat])
				} else {
					data.push([this.data[i].date, this.data[i].weight, this.data[i].bodyfat])
				}
			}
			return {
				header: 'row',
				border: true,
				stripe: true,
				sort: [0, 1, 2],
				pagination: true,
				pageSize: 10,
				pageSizes: [10, 20, 50],
				edit: { row: 'all' },
				data: data,
			}
		},
	},
	methods: {
	},
}
</script>
<style lang="scss" scoped>
	.progress-bar.small {
		width: 35%;
	}
</style>
