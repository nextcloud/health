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
import PersonsNavigation from './modules/persons/PersonsNavigation'
import Notifications from './Notifications'
import PersonsSidebar from './modules/persons/PersonsSidebar'
import WeightSidebar from './modules/weight/WeightSidebar'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import WeightContent from './modules/weight/WeightContent'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

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
				// {
				// type: 'hint',
				// text: 'TEST',
				// },
			],
			persons: null,
		}
	},
	mounted: function() {
		this.axGetPersons()
	},
	methods: {
		getUrl: function(path) {
			const url = `/apps/health${path}`
			return generateUrl(url)
		},
		axGetPersons: function() {
			return axios.get(this.getUrl('/persons'))
				.then(
					(response) => {
						// console.debug('debug axGetPersons SUCCESS-------------')
						// console.debug(response)
						this.persons = response.data
					},
					(err) => {
						console.debug('debug axGetPersons ERROR-------------')
						console.debug(err)
					}
				)
				.catch((err) => {
					// return Promise.reject(err)
					console.debug('error detected')
					console.debug(err)
				})
		},
	},
}
</script>
<style lang="scss">
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
	h3 {
	    font-size: 20px;
	    margin-top: 40px;
	}
	.content-wrapper h3:first-child {
		margin-top: 2px;
	}
	span {
		opacity: .7;
		font-size: 0.8em;
		margin-left: 5px;
	}
</style>
