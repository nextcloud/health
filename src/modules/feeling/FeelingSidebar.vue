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
		<div class="checkbox-wrapper">
			<label>
				<input
					id="mood"
					v-model="columns.mood"
					type="checkbox"
					@change="saveColumn('mood')">
				{{ t('health', 'Mood', {}) }}
			</label>
		</div>
		<div class="checkbox-wrapper">
			<label>
				<input
					id="sadnessLevel"
					v-model="columns.sadnessLevel"
					type="checkbox"
					@change="saveColumn('sadnessLevel')">
				{{ t('health', 'Sadness level', {}) }}
			</label>
		</div>
		<div class="checkbox-wrapper">
			<label>
				<input
					id="symptoms"
					v-model="columns.symptoms"
					type="checkbox"
					@change="saveColumn('symptoms')">
				{{ t('health', 'Symptoms') }}
			</label>
		</div>
		<div class="checkbox-wrapper">
			<label>
				<input
					id="attacks"
					v-model="columns.attacks"
					type="checkbox"
					@change="saveColumn('attacks')">
				{{ t('health', 'Attacks', {}) }}
			</label>
		</div>
		<div class="checkbox-wrapper">
			<label>
				<input
					id="medication"
					v-model="columns.medication"
					type="checkbox"
					@change="saveColumn('medication')">
				{{ t('health', 'Medication', {}) }}
			</label>
		</div>
		<div class="checkbox-wrapper">
			<label>
				<input
					id="pain"
					v-model="columns.pain"
					type="checkbox"
					@change="saveColumn('pain')">
				{{ t('health', 'Pain', {}) }}
			</label>
		</div>
		<div class="checkbox-wrapper">
			<label>
				<input
					id="energy"
					v-model="columns.energy"
					type="checkbox"
					@change="saveColumn('energy')">
				{{ t('health', 'Energy', {}) }}
			</label>
		</div>
	</ul>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'FeelingSidebar',
	data: function() {
		return {
			columns: {
				mood: true,
				sadnessLevel: true,
				symptoms: true,
				attacks: true,
				medication: true,
				pain: true,
				energy: true,
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
				this.columns.mood = this.person.feelingColumnMood
				this.columns.sadnessLevel = this.person.feelingColumnSadnessLevel
				this.columns.symptoms = this.person.feelingColumnSymptoms
				this.columns.attacks = this.person.feelingColumnAttacks
				this.columns.medication = this.person.feelingColumnMedication
				this.columns.pain = this.person.feelingColumnPain
				this.columns.energy = this.person.feelingColumnEnergy
			} else {
				console.debug('no person found to update [watch person in FeelingSidebar]')
			}
		},
		saveColumn(key) {
			this.$store.dispatch('setValue', { key: 'feelingColumn' + key[0].toUpperCase() + key.substring(1), value: this.columns[key] })
		},
	},
}
</script>
