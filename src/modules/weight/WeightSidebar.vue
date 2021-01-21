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
					id="weight"
					v-model="columns.weight"
					type="checkbox"
					@change="saveColumn('weight')">
				{{ t('health', 'Weight', {}) }}
			</label>
		</div>
		<div>
			<label>
				<input
					id="bodyfat"
					v-model="columns.bodyfat"
					type="checkbox"
					@change="saveColumn('bodyfat')">
				{{ t('health', 'Bodyfat', {}) }}
			</label>
		</div>
		<div>
			<label>
				<input
					id="measurement"
					v-model="columns.measurement"
					type="checkbox"
					@change="saveColumn('measurement')">
				{{ person.weightMeasurementName ? person.weightMeasurementName : t('health', 'Custom measurement') }}
			</label>
		</div>
		<div>
			<label>
				<input
					id="waistSize"
					v-model="columns.waistSize"
					type="checkbox"
					@change="saveColumn('waistSize')">
				{{ t('health', 'Waist size', {}) }}
			</label>
		</div>
		<div>
			<label>
				<input
					id="hipSize"
					v-model="columns.hipSize"
					type="checkbox"
					@change="saveColumn('hipSize')">
				{{ t('health', 'Hip size', {}) }}
			</label>
		</div>
		<div>
			<label>
				<input
					id="musclePart"
					v-model="columns.musclePart"
					type="checkbox"
					@change="saveColumn('musclePart')">
				{{ t('health', 'Muscle part', {}) }}
			</label>
		</div>

		<li><h3>{{ t('health', 'General settings', {}) }}</h3></li>
		<li><h4>{{ t('health', 'Unit for weight', {}) }}</h4></li>
		<ActionInput
			type="text"
			:value="person.weightUnit"
			icon="icon-category-customization"
			@submit="updateWeightUnit" />
		<li>
			<h4>
				{{ t('health', 'Measurementdata Name', {}) }}<span><br>{{ t('health', 'What else do you want to track? Set here a name and you can add data in the data-table. The values have to be numbers.', {}) }}</span>
			</h4>
		</li>
		<ActionInput
			type="text"
			:value="person.weightMeasurementName"
			icon="icon-rename"
			@submit="updateMeasurementName" />
		<li>
			<h3 class="toggable"
				@click="showTargetOptions = !showTargetOptions">
				{{ t('health', 'Target', {}) }}
			</h3>
		</li>
		<li>
			<h4>
				{{ t('health', 'Weight target') }}
				<span>
					{{ t('health', 'in {unit}', { unit: person.weightUnit }) }}
					<br>
					{{ t('health', 'Leave blank for none.') }}
				</span>
			</h4>
		</li>
		<ActionInput
			type="number"
			:value="person.weightTarget"
			icon="icon-category-monitoring"
			@submit="updateWeightTarget" />
		<li>
			<h4>
				{{ t('health', 'Weight target initial weight') }}
				<span>
					{{ t('health', 'in {unit}', { unit: person.weightUnit }) }}
					<br>
					{{ t('health', 'This is your reference weight for the target') }}
				</span>
			</h4>
		</li>
		<ActionInput
			type="number"
			:value="(person.weightTargetInitialWeight !== null )? person.weightTargetInitialWeight: ''"
			icon="icon-quota"
			@submit="updateWeightTargetInitialWeight" />
		<li>
			<h4>
				{{ t('health', 'Weight target bounty', {}) }}
			</h4>
		</li>
		<div class="textarea-sidebar">
			<textarea ref="weightTargetBounty" :value="person.weightTargetBounty" :placeholder="t('health', 'Add some bounty to motivate yourself, if you like to.', {})" />
		</div>
		<button
			@click="updateWeightTargetBounty">
			{{ t('health', 'Save bounty', {}) }}
		</button>
	</ul>
</template>

<script>
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'WeightSidebar',
	components: {
		ActionInput,
	},
	data: function() {
		return {
			columns: {
				weight: true,
				bodyfat: true,
				measurement: false,
				waistSize: false,
				hipSize: false,
				musclePart: false,
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
				this.columns.weight = this.person.weightColumnWeight
				this.columns.bodyfat = this.person.weightColumnBodyfat
				this.columns.measurement = this.person.weightColumnMeasurement
				this.columns.waistSize = this.person.weightColumnWaistSize
				this.columns.hipSize = this.person.weightColumnHipSize
				this.columns.musclePart = this.person.weightColumnMusclePart
			} else {
				console.debug('no person found to update [watch person in WeightSidebar]')
			}
		},
		saveColumn(key) {
			// console.debug('update column', key)
			// console.debug('value', this.columns[key])
			this.$store.dispatch('setValue', { key: 'weightColumn' + key[0].toUpperCase() + key.substring(1), value: this.columns[key] })
		},
		updateWeightUnit: function(e) {
			this.$store.dispatch('setValue', { key: 'weightUnit', value: e.target[1].value })
		},
		updateWeightTarget: function(e) {
			this.$store.dispatch('setValue', { key: 'weightTarget', value: e.target[1].value })
		},
		updateWeightTargetInitialWeight: function(e) {
			this.$store.dispatch('setValue', { key: 'weightTargetInitialWeight', value: e.target[1].value })
		},
		updateMeasurementName: function(e) {
			this.$store.dispatch('setValue', { key: 'weightMeasurementName', value: e.target[1].value })
		},
		updateWeightTargetBounty: function() {
			this.$store.dispatch('setValue', { key: 'weightTargetBounty', value: this.$refs.weightTargetBounty.value })
		},
	},
}
</script>
