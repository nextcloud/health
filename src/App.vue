<template>
	<Content :class="{'icon-loading': loading}" app-name="health">
		<PersonNavigation
			:persons="persons"
			:active-person-id.sync="activePersonId"
			:active-module.sync="activeModule"
			:show-sidebar.sync="showSidebar"
			:notifications.sync="notifications" />
		<AppContent>
			<div class="top-bar" />
			<div class="content-wrapper">
				<Notifications
					:persons.sync="persons"
					:active-person-id.sync="activePersonId"
					:notifications.sync="notifications" />
				<div>
					menuOpenPersonId: {{ menuOpenPersonId }}<br>
					activePersonId: {{ activePersonId }}<br>
					showNewPersonForm: {{ showNewPersonForm }}<br>
					module weight: {{ persons[activePersonId].enabledModules.weight }}<br>
					active module: {{ activeModule }}<br>
				</div>
			</div>
		</AppContent>
		<AppSidebar v-show="showSidebar"
			:title="persons[activePersonId].name"
			subtitle="created 41 days ago"
			@close="showSidebar=false">
			<template #primary-actions>
				<ul>
					<li v-for="(n, index) in persons[activePersonId].notifications" :key="index" :class="{'green': n.type == 'green', 'orange': n.type == 'orange', 'red': n.type == 'red'}">
						{{ n.message }}
					</li>
				</ul>
			</template>
			<AppSidebarTab id="person" name="Person" icon="icon-user">
				<ul>
					<li><h4>Age</h4></li>
					<ActionInput
						type="number"
						:value="persons[activePersonId].age"
						icon="icon-user"
						@submit="ageUpdate" />
					<li><h4>Size<span>in cm</span></h4></li>
					<ActionInput
						type="number"
						:value="persons[activePersonId].size"
						icon="icon-fullscreen"
						@submit="sizeUpdate" />
					<li><h4>Sex</h4></li>
					<ActionRadio
						name="sex"
						value="female"
						:checked="persons[activePersonId].sex === 'female'"
						@change="sexUpdate">
						female
					</ActionRadio>
					<ActionRadio
						name="sex"
						value="male"
						:checked="persons[activePersonId].sex === 'male'"
						@change="sexUpdate">
						male
					</ActionRadio>
					<li><h4>Manage modules</h4></li>
					<ActionCheckbox
						:checked="persons[activePersonId].enabledModules.weight"
						value="weight"
						@change="persons[activePersonId].enabledModules.weight = !persons[activePersonId].enabledModules.weight">
						Weight
					</ActionCheckbox>
				</ul>
			</AppSidebarTab>
			<AppSidebarTab
				v-if="persons[activePersonId].enabledModules.weight"
				id="weight"
				name="Weight"
				icon="icon-quota">
				<ul>
					<li><h4>Unit for weight</h4></li>
					<ActionInput
						type="text"
						:value="persons[activePersonId].weight.unit"
						icon="icon-category-customization"
						@submit="weightUnitUpdate" />
					<li><h4>Weight target<span>in {{ persons[activePersonId].weight.unit }}<br>blank for none<br>On update the initial weight for the target will be {{ weightGetLast }}{{ weightGetUnit }}</span></h4></li>
					<ActionInput
						type="number"
						:value="persons[activePersonId].weight.target"
						icon="icon-category-monitoring"
						@submit="weightTargetUpdate" />
				</ul>
			</AppSidebarTab>
		</AppSidebar>
	</Content>
</template>

<script>
import Content from '@nextcloud/vue/dist/Components/Content'
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import AppSidebar from '@nextcloud/vue/dist/Components/AppSidebar'
import AppSidebarTab from '@nextcloud/vue/dist/Components/AppSidebarTab'
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'
import ActionRadio from '@nextcloud/vue/dist/Components/ActionRadio'
import ActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox'
// import ProgressBar from '@nextcloud/vue/dist/Components/ProgressBar'
// import LineChart from './LineChart.js'
// import VueTableDynamic from 'vue-table-dynamic'
// import SettingsGroup from './components/SettingsGroup'
import PersonNavigation from './modules/persons/PersonNavigation'
import Notifications from './Notifications'

export default {
	name: 'App',
	components: {
		Content,
		AppContent,
		AppSidebar,
		AppSidebarTab,
		ActionInput,
		ActionRadio,
		ActionCheckbox,
		// ProgressBar,
		// LineChart,
		// VueTableDynamic,
		// SettingsGroup,
		PersonNavigation,
		Notifications,
	},
	data: function() {
		return {
			loading: false,
			showSidebar: false,
			activePersonId: 0,
			activeModule: 'persons',
			notifications: [
				{
					type: 'hint',
					text: 'TEST',
				},
			],
			THISISASEPERATORANDISVERYLONGFORNONEUSE: null,
			params: {
				data: [
					['zeile 1', 'z2', 'letzeres'],
					['Cell-1', 'Cell-2', 'Cell-3'],
					['Cell-4', 'Cell-5', 'Cell-6'],
					['Cell-7', 'Cell-8', 'Cell-9'],
				],
				header: 'row',
				border: true,
				stripe: true,
				sort: [0, 1, 2],
				pagination: true,
				pageSize: 10,
				pageSizes: [10, 20, 50],
				edit: { row: 'all' },
			},
			showNewPersonForm: false,
			menuOpenPersonId: 0,
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
					},
					weight: {
						unit: 'kg',
						target: 70,
						targetInitialWeight: 99,
						data: [
							{
								date: '02/08/2020',
								weight: 80,
								armleft: 0,
								armright: 0,
								chest: 0,
								waist: 0,
								hips: 0,
								thighleft: 0,
								thighricht: 0,
								bodyfat: 10,
							},
							{
								date: '03/08/2020',
								weight: 82,
								armleft: 0,
								armright: 0,
								chest: 0,
								waist: 0,
								hips: 0,
								thighleft: 0,
								thighricht: 0,
								bodyfat: 10,
							},
						],
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
					},
					weight: {
						unit: 'kg',
						target: 70,
						targetInitialWeight: 99,
						data: [
							{
								date: '05/08/2020',
								weight: 0,
								armleft: 0,
								armright: 0,
								chest: 0,
								waist: 0,
								hips: 0,
								thighleft: 0,
								thighricht: 0,
								bodyfat: 10,
							},
						],
					},
				},
			],
		}
	},
	mounted() {
		this.weightChartData()
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
		ageUpdate(e) {
			this.persons[this.activePersonId].age = e.target[1].value
			this.log('age updated')
		},
		sizeUpdate(e) {
			this.persons[this.activePersonId].size = e.target[1].value
			this.log('size updated')
		},
		sexUpdate(e) {
			this.persons[this.activePersonId].sex = e.target.value
			this.log('sex updated')
		},
		weightTargetUpdate(e) {
			this.persons[this.activePersonId].weight.target = e.target[1].value
			this.persons[this.activePersonId].weight.targetInitialWeight = this.weightGetLast
			this.log('weight target updated')
		},
		weightUnitUpdate(e) {
			this.persons[this.activePersonId].weight.unit = e.target[1].value
			this.log('weight unit updated')
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
	li h4 {
		margin-top: 10px;
	}
	span {
		opacity: .7;
		font-size: 0.8em;
		margin-left: 5px;
	}
	.progress-bar.small {
		width: 35%;
	}
</style>
