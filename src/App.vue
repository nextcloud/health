<template>
	<Content :class="{'icon-loading': loading}" app-name="health">
		<PersonsNavigation
			:persons="persons"
			:active-person-id.sync="activePersonId"
			:active-module.sync="activeModule"
			:show-sidebar.sync="showSidebar"
			:notifications.sync="notifications" />
		<AppContent>
			<div class="top-bar" />
			<div class="content-menu-topright">
				<Actions :title="'Details'">
					<ActionButton icon="icon-menu-sidebar" @click="showSidebar = !showSidebar" />
				</Actions>
			</div>
			<div class="content-wrapper">
				<Notifications
					:persons.sync="persons"
					:active-person-id.sync="activePersonId"
					:notifications.sync="notifications" />
				<div>
					activePersonId: {{ activePersonId }}<br>
					active module: {{ activeModule }}<br>
				</div>
				<WeightContent v-if="activeModule === 'weight' && persons[activePersonId].enabledModules.weight"
					:person.sync="persons[activePersonId]" />
			</div>
		</AppContent>
		<AppSidebar v-show="showSidebar"
			:title="persons[activePersonId].name"
			@close="showSidebar=false">
			<template #primary-actions />
			<AppSidebarTab id="person" name="Person" icon="icon-user">
				<PersonsSidebar
					:person.sync="persons[activePersonId]" />
			</AppSidebarTab>
			<AppSidebarTab id="weight" name="Weight" icon="icon-quota">
				<WeightSidebar
					v-if="persons[activePersonId].enabledModules.weight"
					:person.sync="persons[activePersonId]"
					:last-weight="persons[activePersonId].weight.lastWeight" />
			</AppSidebarTab>
		</AppSidebar>
	</Content>
</template>

<script>
import Content from '@nextcloud/vue/dist/Components/Content'
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import AppSidebar from '@nextcloud/vue/dist/Components/AppSidebar'
import AppSidebarTab from '@nextcloud/vue/dist/Components/AppSidebarTab'
// import ProgressBar from '@nextcloud/vue/dist/Components/ProgressBar'
// import LineChart from './LineChart.js'
// import VueTableDynamic from 'vue-table-dynamic'
import PersonsNavigation from './modules/persons/PersonsNavigation'
import Notifications from './Notifications'
import PersonsSidebar from './modules/persons/PersonsSidebar'
import WeightSidebar from './modules/weight/WeightSidebar'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import WeightContent from './modules/weight/WeightContent'

export default {
	name: 'App',
	components: {
		Content,
		AppContent,
		AppSidebar,
		AppSidebarTab,
		ActionButton,
		Actions,
		PersonsNavigation,
		Notifications,
		WeightSidebar,
		PersonsSidebar,
		WeightContent,
	},
	data: function() {
		return {
			loading: false,
			showSidebar: false,
			activePersonId: 0,
			activeModule: 'weight', // persons
			notifications: [
				{
					type: 'hint',
					text: 'TEST',
				},
			],
			THISISASEPERATORANDISVERYLONGFORNONEUSE: null,
			weight: {
				chartData: null,
			},
			persons: [
				{
					id: 1,
					name: 'Me, Florian',
					notifications: [
						{
							type: 'good',
							text: 'test notofication',
						},
					],
					age: 30,
					sex: 'male',
					size: 170,
					enabledModules: {
						weight: true,
						breaks: false,
						tracking: false,
					},
					weight: {
						unit: 'kg',
						weightTarget: 70,
						weightTargetInitialWeight: 99,
						lastWeight: 10,
						measurementName: 'test',
					},
				},
				{
					id: 2,
					name: 'Madita',
					notifications: [
						{
							type: 'good',
							text: 'test notofication madita',
						},
					],
					age: 21,
					sex: 'female',
					size: 175,
					enabledModules: {
						weight: true,
						breaks: false,
						tracking: false,
					},
					weight: {
						unit: 'kg',
						weightTarget: 70,
						weightTargetInitialWeight: 99,
						lastWeight: 10,
						measurementName: null,
					},
				},
			],
		}
	},
	methods: {
		onCellChange(rowIndex, columnIndex, data) {
			// this.log('onCellChange: ' + rowIndex + ':' + columnIndex + ' -> ' + data)
			// this.log('table data: ' + this.$refs.table.getData())
		},
		weightDataAdd: function() {
			this.params.data.push(['1', '2', '3'])
		},
		weightAddRow: function() {
			this.persons[this.activePersonId].weight.data.push(
				{
					date: '03/08/2020',
					weight: 88,
					armleft: 0,
					armright: 0,
					chest: 0,
					waist: 0,
					hips: 0,
					thighleft: 0,
					thighricht: 0,
					bodyfat: 10,
				}
			)
			this.weightChartData()
		},
		weightChartData: function() {
			const data = []
			for (let i = 0; i < this.persons[this.activePersonId].weight.data.length; i++) {
				data.push({
					t: this.persons[this.activePersonId].weight.data[i].date,
					y: this.persons[this.activePersonId].weight.data[i].weight,
				})
			}
			this.log('computed array for weight-chart')
			this.log(data)

			this.weight.chartData = {
				labels: [
					'01.08.',
					'02.08.',
					'03.08.',
					'04.08.',
					'05.08.',
				],
				datasets: [
					{
						label: 'My First dataset',
						backgroundColor: '#ffeeee',
						borderColor: 'red',
						fill: false,
						data: data,
					},
				],
			}
		},
	},
}
</script>
<style lang="scss" scoped>
	.content-wrapper {
		padding: 35px 10px 10px 10px;
	}
	.top-bar {
		width: 100%;
		height: 35px;
		position: fixed;
		z-index: inherit;
		background: var(--color-main-background);
	}
	.detailsMainInfo {
		padding: 10px;
	}
	.content-menu-topright {
		position: fixed;
		right: 0px;
		z-index: 1001;
	}
</style>
