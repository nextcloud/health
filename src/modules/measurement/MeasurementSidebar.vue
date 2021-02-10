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
	<ul>
		<li><h3>{{ t('health', 'Column selection', {}) }}</h3></li>
		<ActionCheckbox
			:checked="person.measurementColumnTemperature"
			@change="$store.dispatch('setValue', { key: 'measurementColumnTemperature', value: $event.target.checked })">
			{{ t('health', 'Temperature', {}) }}
		</ActionCheckbox>
		<ActionCheckbox
			:checked="person.measurementColumnHeartRate"
			@change="$store.dispatch('setValue', { key: 'measurementColumnHeartRate', value: $event.target.checked })">
			{{ t('health', 'Heart rate', {}) }}
		</ActionCheckbox>
		<ActionCheckbox
			:checked="person.measurementColumnBloodPres"
			@change="$store.dispatch('setValue', { key: 'measurementColumnBloodPres', value: $event.target.checked })">
			{{ t('health', 'Blood pressure') }}
		</ActionCheckbox>
		<ActionCheckbox
			:checked="person.measurementColumnOxygenSat"
			@change="$store.dispatch('setValue', { key: 'measurementColumnOxygenSat', value: $event.target.checked })">
			{{ t('health', 'Oxygen saturation', {}) }}
		</ActionCheckbox>
		<ActionCheckbox
			:checked="person.measurementColumnBloodSugar"
			@change="$store.dispatch('setValue', { key: 'measurementColumnBloodSugar', value: $event.target.checked })">
			{{ t('health', 'Blood sugar', {}) }}
		</ActionCheckbox>
		<ActionCheckbox
			:checked="person.measurementColumnAppetite"
			@change="$store.dispatch('setValue', { key: 'measurementColumnAppetite', value: $event.target.checked })">
			{{ t('health', 'Appetite', {}) }}
		</ActionCheckbox>
		<ActionCheckbox
			:checked="person.measurementColumnAllergies"
			@change="$store.dispatch('setValue', { key: 'measurementColumnAllergies', value: $event.target.checked })">
			{{ t('health', 'Allergies', {}) }}
		</ActionCheckbox>
		<ActionCheckbox
			:checked="person.measurementColumnDefecation"
			@change="$store.dispatch('setValue', { key: 'measurementColumnDefecation', value: $event.target.checked })">
			{{ t('health', 'Defecation', {}) }}
		</ActionCheckbox>

		<li><h3>{{ t('health', 'General settings', {}) }}</h3></li>
		<ActionCheckbox
			:checked="person.measurementChartDetail"
			@change="$store.dispatch('setValue', { key: 'measurementChartDetail', value: $event.target.checked })">
			{{ t('health', 'Show time detail chart', {}) }}
		</ActionCheckbox>
	</ul>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import ActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox'

export default {
	name: 'MeasurementSidebar',
	components: {
		ActionCheckbox,
	},
	computed: {
		...mapState(['activeModule', 'showSidebar']),
		...mapGetters(['person']),
	},
}
</script>
