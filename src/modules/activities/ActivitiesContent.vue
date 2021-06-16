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
			{{ t('health', 'Activities', {}) }}
		</h2>

		<h3>{{ t('health', 'Chart', {}) }}</h3>
		<ActivitiesChart
			v-if="!loading"
			:person="person"
			:data="activitiesData" />
		<div v-if="loading" class="icon-loading" />

		<h3>{{ t('health', 'Data', {}) }}</h3>
		<ActivitiesTable
			v-if="!loading"
			:data="activitiesData"
			:person="person" />
		<div v-if="loading" class="icon-loading" />
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import ActivitiesTable from './ActivitiesTable'
import ActivitiesChart from './ActivitiesChart'

export default {
	name: 'ActivitiesContent',
	components: {
		ActivitiesChart,
		ActivitiesTable,
	},
	computed: {
		...mapState(['activeModule', 'showSidebar', 'activitiesDatasets', 'loading']),
		...mapGetters(['person']),
		activitiesData() {
			return !this.activitiesDatasets
				? null
				// eslint-disable-next-line vue/no-side-effects-in-computed-properties
				: this.activitiesDatasets.sort(function(a, b) {
					return new Date(b.datetime) - new Date(a.datetime)
				})
		},
	},
}
</script>
