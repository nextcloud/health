<template>
	<NcContent :class="{'icon-loading': initialLoading}" app-name="health">
		<PersonsNavigation />
		<NcAppContent>
			<div class="content-menu-topright">
				<NcActions v-if="canManage" :title="'Details'">
					<NcActionButton icon="icon-menu-sidebar" @click="$store.commit('showSidebar', !showSidebar)" />
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
		<NcAppSidebar
			v-show="showSidebar"
			v-if="person && !loading && canManage"
			:title="person.name"
			:subtitle="t('health', 'Created at {creationTime}', { creationTime: formatMyDate(person.insertTime) })"
			@close="$store.commit('showSidebar', false)"
		>
			<NcAppSidebarTab
				id="person"
				:name="t('health', 'Person')"
				icon="icon-user"
				:order="0"
			>
				<PersonsSidebar />
			</NcAppSidebarTab>
			<NcAppSidebarTab
				id="sharing"
				:name="t('health', 'Share')"
				icon="icon-share"
				:order="0.5"
			>
				<SharingSidebar />
			</NcAppSidebarTab>
			<NcAppSidebarTab
				v-if="person.enabledModuleWeight"
				id="weight"
				:name="t('health', 'Weight')"
				icon="icon-quota"
				:order="1"
			>
				<WeightSidebar />
			</NcAppSidebarTab>
			<NcAppSidebarTab
				v-if="person.enabledModuleFeeling"
				id="feeling"
				:name="t('health', 'Feeling')"
				icon="icon-category-monitoring"
				:order="2"
			>
				<FeelingSidebar />
			</NcAppSidebarTab>
			<NcAppSidebarTab
				v-if="person.enabledModuleMeasurement"
				id="measurement"
				:name="t('health', 'Measurement')"
				icon="icon-home"
				:order="3"
			>
				<MeasurementSidebar />
			</NcAppSidebarTab>
			<NcAppSidebarTab
				v-if="person.enabledModuleSleep"
				id="sleep"
				:name="t('health', 'Sleep')"
				icon="icon-download"
				:order="4"
			>
				<SleepSidebar />
			</NcAppSidebarTab>
			<NcAppSidebarTab
				v-if="person.enabledModuleSmoking"
				id="smoking"
				:name="t('health', 'Smoking')"
				icon="icon-address"
				:order="5"
			>
				<SmokingSidebar />
			</NcAppSidebarTab>
			<NcAppSidebarTab
				v-if="person.enabledModuleActivities"
				id="activities"
				:name="t('health', 'Activities')"
				icon="icon-upload"
				:order="6"
			>
				<ActivitiesSidebar />
			</NcAppSidebarTab>
		</NcAppSidebar>
	</NcContent>
</template>

<script>
import NcContent from '@nextcloud/vue/dist/Components/NcContent'
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent'
import NcAppSidebar from '@nextcloud/vue/dist/Components/NcAppSidebar'
import NcAppSidebarTab from '@nextcloud/vue/dist/Components/NcAppSidebarTab'
import PersonsNavigation from './modules/persons/PersonsNavigation'
import PersonsSidebar from './modules/persons/PersonsSidebar'
import SharingSidebar from './modules/persons/SharingSidebar'
import WeightSidebar from './modules/weight/WeightSidebar'
import FeelingSidebar from './modules/feeling/FeelingSidebar'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton'
import NcActions from '@nextcloud/vue/dist/Components/NcActions'
import WeightContent from './modules/weight/WeightContent'
import FeelingContent from './modules/feeling/FeelingContent'
import { mapState, mapGetters } from 'vuex'
import PersonsContent from './modules/persons/PersonsContent'
import SleepContent from './modules/sleep/SleepContent'
import MeasurementContent from './modules/measurement/MeasurementContent'
import MeasurementSidebar from './modules/measurement/MeasurementSidebar'
import SleepSidebar from './modules/sleep/SleepSidebar'
import moment from '@nextcloud/moment'
import SmokingSidebar from './modules/smoking/SmokingSidebar'
import SmokingContent from './modules/smoking/SmokingContent'
import ActivitiesContent from './modules/activities/ActivitiesContent'
import ActivitiesSidebar from './modules/activities/ActivitiesSidebar'
import MedicationContent from './modules/medication/MedicationContent'

export default {
	name: 'App',
	components: {
		ActivitiesSidebar,
		MeasurementContent,
		SleepContent,
		MeasurementSidebar,
		SleepSidebar,
		NcContent,
		NcAppContent,
		NcAppSidebar,
		NcAppSidebarTab,
		NcActionButton,
		NcActions,
		PersonsNavigation,
		WeightSidebar,
		FeelingSidebar,
		PersonsSidebar,
		SharingSidebar,
		WeightContent,
		FeelingContent,
		PersonsContent,
		SmokingSidebar,
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
	methods: {
		formatMyDate(v) {
			return moment(v).format('DD.MM.YYYY')
		},
	},
}
</script>
<style lang="scss">

	.content-wrapper *:after, .content-wrapper *:before{
		-webkit-box-sizing: border-box;
		-moz-box-sizing: border-box;
		box-sizing: border-box;
	}

	.row {
		// background-color: blueviolet;
		width: 100%;
	}

	.col-1 { width: 25%; }

	.col-2 { width: 50%; }

	.col-3 { width: 75%; }

	.col-4 { width: 100%; }

	[class*='col-'] {
		float: left;
		padding: 15px;
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

	.content-menu-topright {
		position: fixed;
		right: 20px;
		z-index: 1001;
	}

	h2 {
		margin-top: -10px;
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
