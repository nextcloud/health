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
		<li><h3>{{ t('health', 'General settings', {}) }}</h3></li>
		<li><h4>{{ t('health', 'Unit for weight', {}) }}</h4></li>
		<ActionInput
			type="text"
			:value="person.weightUnit"
			icon="icon-category-customization"
			@submit="updateWeightUnit" />
		<li>
			<h4>
				{{ t('health', 'Measurement Name', {}) }}<span><br>{{ t('health', 'What else do you want to track? Set here a name and you can add data in the data-table. The values have to be numbers.', {}) }}</span>
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
				{{ t('health', 'Weight target{a}in {unit}{br}Leave blank for none.{b}', {unit: person.weightUnit, a: '<span>', b: '</span>', br: '<br>', }) }}
			</h4>
		</li>
		<ActionInput
			type="number"
			:value="person.weightTarget"
			icon="icon-category-monitoring"
			@submit="updateWeightTarget" />
		<li>
			<h4>
				{{ t('health', 'Weight target initial weight{a}in {unit}{br}your reference weight for the target{b}', { a: '<span>', b: '</span>', br: '<br>', unit: person.weightUnit, }) }}
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
		}
	},
	computed: {
		...mapState(['activeModule', 'showSidebar']),
		...mapGetters(['person']),
	},
	methods: {
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
<style lang="scss" scoped>
</style>
