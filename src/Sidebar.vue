<template>
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
</template>

<script>
import { NcAppSidebar, NcAppSidebarTab } from '@nextcloud/vue'
import MeasurementSidebar from './modules/measurement/MeasurementSidebar.vue'
import WeightSidebar from './modules/weight/WeightSidebar.vue'
import SleepSidebar from './modules/sleep/SleepSidebar.vue'
import PersonsSidebar from './modules/persons/PersonsSidebar.vue'
import SmokingSidebar from './modules/smoking/SmokingSidebar.vue'
import ActivitiesSidebar from './modules/activities/ActivitiesSidebar.vue'
import SharingSidebar from './modules/persons/SharingSidebar.vue'
import FeelingSidebar from './modules/feeling/FeelingSidebar.vue'
import { mapGetters, mapState } from 'vuex'
import moment from '@nextcloud/moment'

export default {
	components: {
		MeasurementSidebar,
		SharingSidebar,
		ActivitiesSidebar,
		SleepSidebar,
		NcAppSidebarTab,
		SmokingSidebar,
		NcAppSidebar,
		WeightSidebar,
		PersonsSidebar,
		FeelingSidebar,
	},
	props: {
		loading: {
			type: Boolean,
			default: false,
		},
	},
	computed: {
		...mapState(['activePersonId', 'activeModule', 'showSidebar', 'persons', 'initialLoading']),
		...mapGetters(['person', 'canManage']),
	},
	methods: {
		formatMyDate(v) {
			return moment(v).format('DD.MM.YYYY')
		},
	},
}
</script>
