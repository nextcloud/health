<!--
	- @copyright Copyright (c) 2020 Florian Steffens <flost-dev@mailbox.org>
	-
	- @author Marcus Nitzschke <mail@kendix.org>
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
			{{ t('health', 'Medication', {}) }}
		</h2>

		<h3>{{ t('health', 'Medication plans', {}) }}</h3>
		<MedicationPlanTable
			v-if="!loading"
			:data="medicationPlans"
			:person="person"
		/>

		<h3>{{ t('health', 'Medication', {}) }}</h3>
		<MedicationTable
			v-if="!loading && selectedMedicationPlan !== null"
			:data="medicationData"
			:person="person"
		/>

		<NcEmptyContent
			v-if="selectedMedicationPlan === null"
			:title="t('health', 'Select a medication plan')"
		>
			<template #icon>
				<ClipboardOutline />
			</template>
		</NcEmptyContent>

		<div v-if="loading" class="icon-loading" />
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import MedicationPlanTable from './MedicationPlanTable'
import MedicationTable from './MedicationTable'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent'
import ClipboardOutline from 'vue-material-design-icons/ClipboardOutline.vue'

export default {
	name: 'MedicationContent',
	components: {
		ClipboardOutline,
		NcEmptyContent,
		MedicationPlanTable,
		MedicationTable,
	},
	computed: {
		...mapState(['activeModule', 'showSidebar', 'medicationPlanDatasets', 'medicationDatasets', 'selectedMedicationPlan', 'loading']),
		...mapGetters(['person']),
		medicationData() {
			return !this.medicationDatasets
					 ? null
				// eslint-disable-next-line vue/no-side-effects-in-computed-properties
					 : this.medicationDatasets.sort(function(a, b) {
						 return a.name.localeCompare(b.name)
					 })
		},
		medicationPlans() {
			return !this.medicationPlanDatasets
					 ? null
				// eslint-disable-next-line vue/no-side-effects-in-computed-properties
					 : this.medicationPlanDatasets.sort(function(a, b) {
						 return new Date(b.date) - new Date(a.date)
					 })
		},
	},
}
</script>

<style lang="scss" scoped>
	.empty-content {
		margin-top: 0 !important;
		margin-bottom: 20px;
	}
</style>
