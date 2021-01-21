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
		<div>
			<label>
				<input
					id="temperature"
					v-model="columns.temperature"
					type="checkbox"
					@change="saveColumn('temperature')">
				{{ t('health', 'Temperature', {}) }}
			</label>
		</div>
		<div>
			<label>
				<input
					id="heartRate"
					v-model="columns.heartRate"
					type="checkbox"
					@change="saveColumn('heartRate')">
				{{ t('health', 'Heart rate', {}) }}
			</label>
		</div>
		<div>
			<label>
				<input
					id="bloodPres"
					v-model="columns.bloodPres"
					type="checkbox"
					@change="saveColumn('bloodPres')">
				{{ t('health', 'Blood pressure') }}
			</label>
		</div>
		<div>
			<label>
				<input
					id="oxygenSat"
					v-model="columns.oxygenSat"
					type="checkbox"
					@change="saveColumn('oxygenSat')">
				{{ t('health', 'Oxygen saturation', {}) }}
			</label>
		</div>
		<div>
			<label>
				<input
					id="bloodSugar"
					v-model="columns.bloodSugar"
					type="checkbox"
					@change="saveColumn('bloodSugar')">
				{{ t('health', 'Blood sugar', {}) }}
			</label>
		</div>
		<div>
			<label>
				<input
					id="defecation"
					v-model="columns.defecation"
					type="checkbox"
					@change="saveColumn('defecation')">
				{{ t('health', 'Defecation', {}) }}
			</label>
		</div>
		<div>
			<label>
				<input
					id="appetite"
					v-model="columns.appetite"
					type="checkbox"
					@change="saveColumn('appetite')">
				{{ t('health', 'Appetite', {}) }}
			</label>
		</div>
		<div>
			<label>
				<input
					id="allergies"
					v-model="columns.allergies"
					type="checkbox"
					@change="saveColumn('allergies')">
				{{ t('health', 'Allergies', {}) }}
			</label>
		</div>
	</ul>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'MeasurementSidebar',
	data: function() {
		return {
			columns: {
				temperature: true,
				heartRate: true,
				bloodPres: false,
				oxygenSat: false,
				bloodSugar: true,
				defecation: false,
				appetite: true,
				allergies: true,
			},
		}
	},
	computed: {
		...mapState(['activeModule', 'showSidebar']),
		...mapGetters(['person']),
	},
	watch: {
		person: function() {
			this.updateLocalColumnData()
		},
	},
	mounted() {
		this.updateLocalColumnData()
	},
	methods: {
		updateLocalColumnData() {
			if (this.person) {
				this.columns.temperature = this.person.measurementColumnTemperature
				this.columns.heartRate = this.person.measurementColumnHeartRate
				this.columns.bloodPres = this.person.measurementColumnBloodPres
				this.columns.oxygenSat = this.person.measurementColumnOxygenSat
				this.columns.bloodSugar = this.person.measurementColumnBloodSugar
				this.columns.defecation = this.person.measurementColumnDefecation
				this.columns.appetite = this.person.measurementColumnAppetite
				this.columns.allergies = this.person.measurementColumnAllergies
			} else {
				console.debug('no person found to update [watch person in MeasurementSidebar]')
			}
		},
		saveColumn(key) {
			this.$store.dispatch('setValue', { key: 'measurementColumn' + key[0].toUpperCase() + key.substring(1), value: this.columns[key] })
		},
	},
}
</script>
