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
			{{ t('health', 'Measurement', {}) }}
		</h2>

		<h3 v-if="!person.measurementChartDetail">
			{{ t('health', 'Chart', {}) }}
		</h3>
		<h3 v-if="person.measurementChartDetail">
			{{ t('health', 'Charts', {}) }}
		</h3>
		<h4 v-if="person.measurementChartDetail">
			{{ t('health', 'Overall view', {}) }}
		</h4>
		<MeasurementChart
			v-if="!loading"
			:person="person"
			:data="measurementData" />
		<div v-if="loading" class="icon-loading" />

		<h4 v-if="person.measurementChartDetail">
			{{ t('health', 'Detail view', {}) }}
		</h4>
		<MeasurementDetailChart
			v-if="!loading && person.measurementChartDetail"
			:person="person"
			:data="measurementData" />
		<div v-if="loading && person.measurementChartDetail" class="icon-loading" />

		<h3>{{ t('health', 'Data', {}) }}</h3>
		<MeasurementTable
			v-if="!loading"
			:data="measurementData"
			:person="person" />
		<div v-if="loading" class="icon-loading" />
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import MeasurementTable from './MeasurementTable'
import MeasurementChart from './MeasurementChart'
import MeasurementDetailChart from './MeasurementDetailChart'
import moment from '@nextcloud/moment'

export default {
	name: 'MeasurementContent',
	components: {
		MeasurementChart,
		MeasurementTable,
		MeasurementDetailChart,
	},
	computed: {
		...mapState(['activeModule', 'showSidebar', 'measurementDatasets', 'loading']),
		...mapGetters(['person']),
		measurementData() {
			return !this.measurementDatasets
				? null
				// eslint-disable-next-line vue/no-side-effects-in-computed-properties
				: this.measurementDatasets.sort(function(a, b) {
					return moment(b.datetime) - moment(a.datetime)
				})
		},
	},
}
</script>
