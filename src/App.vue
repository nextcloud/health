<template>
	<NcContent :class="{'icon-loading': initialLoading}" app-name="health">
		<PersonsNavigation />
		<NcAppContent>
			<div class="content-menu-top-right">
				<NcActions v-if="canManage" :title="'Details'">
					<NcActionButton :aria-label="t('health', 'Show sidebar')" icon="icon-menu-sidebar" @click="$store.commit('showSidebar', !showSidebar)" />
				</NcActions>
			</div>
			<h3 v-if="person && person.name" class="icon-user h3-icon">
				{{ person.name }}
			</h3>
			<PersonsContent v-if="person && activeModule === 'person'" />
			<WeightContent v-if="person && activeModule === 'weight' && person.enabledModuleWeight" />
			<FeelingContent v-if="person && activeModule === 'feeling' && person.enabledModuleFeeling" />
			<MeasurementContent v-if="person && activeModule === 'measurement' && person.enabledModuleMeasurement" />
			<SleepContent v-if="person && activeModule === 'sleep' && person.enabledModuleSleep" />
			<SmokingContent v-if="person && activeModule === 'smoking' && person.enabledModuleSmoking" />
			<ActivitiesContent v-if="person && activeModule === 'activities' && person.enabledModuleActivities" />
			<MedicationContent v-if="person && activeModule === 'medicine' && person.enabledModuleMedicine" />
		</NcAppContent>
		<Sidebar :loading="loading" />
	</NcContent>
</template>

<script>
import { NcContent, NcAppContent, NcActionButton, NcActions } from '@nextcloud/vue'
import PersonsNavigation from './modules/persons/PersonsNavigation'
import WeightContent from './modules/weight/WeightContent'
import FeelingContent from './modules/feeling/FeelingContent'
import { mapState, mapGetters } from 'vuex'
import PersonsContent from './modules/persons/PersonsContent'
import SleepContent from './modules/sleep/SleepContent'
import MeasurementContent from './modules/measurement/MeasurementContent'
import SmokingContent from './modules/smoking/SmokingContent'
import ActivitiesContent from './modules/activities/ActivitiesContent'
import MedicationContent from './modules/medication/MedicationContent'
import Sidebar from './Sidebar.vue'

export default {
	name: 'App',
	components: {
		Sidebar,
		MeasurementContent,
		SleepContent,
		NcContent,
		NcAppContent,
		NcActionButton,
		NcActions,
		PersonsNavigation,
		WeightContent,
		FeelingContent,
		PersonsContent,
		SmokingContent,
		ActivitiesContent,
		MedicationContent,
	},
	data() {
		return {
			loading: true,
		}
	},
	computed: {
		...mapState(['activePersonId', 'activeModule', 'showSidebar', 'persons', 'initialLoading']),
		...mapGetters(['person', 'canManage']),
	},
	async beforeMount() {
		this.loading = true
		await this.$store.dispatch('loadPersons')
		this.loading = false
	},
}
</script>
<style lang="scss">

	.app-content {
		padding-left: calc(var(--default-grid-baseline) * 6) !important;
		padding-right: calc(var(--default-grid-baseline) * 5) !important;
	}

	.content-wrapper *:after, .content-wrapper *:before{
		-webkit-box-sizing: border-box;
		-moz-box-sizing: border-box;
		box-sizing: border-box;
	}

	.row {
		// background-color: blueviolet;
		width: 100%;
	}

	.row.first-row {
		margin-top: calc(var(--default-grid-baseline) * 10);
	}

	.col-1 { width: 25%; }

	.col-2 { width: 50%; }

	.col-3 { width: 75%; }

	.col-4 { width: 100%; }

	[class*='col-'] {
		float: left;
		padding: 15px 15px 0;
		// border: 1px solid red;
		// background-color: aqua;
	}

	[class*='col-']:first-child {
		// padding-left: 0;
	}

	.floatReverse [class*='col-'] {
		float: right;
	}

	.row::after {
		content: '';
		clear: both;
		display: table;
	}

	@media only screen and (max-width: 1025px) {
		.hide-m {
			display: none;
		}

		.col-1 { width: 50%; }

		.col-3 { width: 100%; }

	}

	@media only screen and (max-width: 641px) {
		.hide-s {
			display: none;
		}

		.col-1 { width: 50%; }

		.col-2 { width: 100%; }

	}

	.content-wrapper {
		padding: 35px 10px 10px 10px;
	}

	.detailsMainInfo {
		padding: 10px;
	}

	.content-menu-top-right {
		position: fixed;
		right: 20px;
		z-index: 1001;
	}

	h2 {
		font-size: x-large;
	}

	h3 {
		font-size: 20px;
		margin-top: 40px;
	}

	h4 {
		font-size: large;
		font-weight: 300;
		margin-bottom: 20px;
		margin-top: 10px;
	}

	.h3-icon {
		background-position: left;
		padding-left: 20px;
		opacity: 0.4;
	}

	.content-wrapper h3:first-child {
		margin-top: 2px;
	}

	.content-wrapper span {
		opacity: .7;
		font-size: 0.8em;
		margin-left: 5px;
	}

	.app-content {
		padding-left: 15px;
		padding-right: 15px;
	}

	.app-sidebar-tabs h3 {
		border-bottom: 1px solid #80808057;
	}

	.green {
		color: #19880C;
	}

	.red {
		color: #bd1500;
	}

</style>
