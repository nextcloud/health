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
		<SidebarSelectColumns :context-filter="contextFilter" :columns="columns" :person-id="person.id" />
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import SidebarSelectColumns from '../generic/SidebarSelectColumns'

export default {
	name: 'MeasurementSidebar',
	components: {
		SidebarSelectColumns,
	},
	data: function() {
		return {
			contextFilter: {
				app: 'health',
				module: 'measurement',
				type: 'sidebarColumns',
			},
		}
	},
	computed: {
		...mapState(['activeModule', 'showSidebar']),
		...mapGetters(['person']),
		columns: function() {
			return [
				{ id: 'heart', name: t('health', 'Blood pressure and heart', {}) },
				{ id: 'temperature', name: t('health', 'Temperature', {}) },
				{ id: 'oxygensaturation', name: t('health', 'Oxygen saturation', {}) },
				{ id: 'bloodsugar', name: t('health', 'Blood sugar', {}) },
				{ id: 'defecation', name: t('health', 'Defecation', {}) },
				{ id: 'appetite', name: t('health', 'Appetite', {}) },
				{ id: 'allergy', name: t('health', 'Allergies', {}) },
			]
		},
	},
	methods: {

	},
}
</script>
