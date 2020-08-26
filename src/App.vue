<template>
	<Content :class="{'icon-loading': loading}" app-name="health">
		<PersonsNavigation />
	</Content>
</template>

<script>
import Content from '@nextcloud/vue/dist/Components/Content'
// import AppContent from '@nextcloud/vue/dist/Components/AppContent'
// import AppSidebar from '@nextcloud/vue/dist/Components/AppSidebar'
// import AppSidebarTab from '@nextcloud/vue/dist/Components/AppSidebarTab'
import PersonsNavigation from './modules/persons/PersonsNavigation'
// import Notifications from './Notifications'
// import PersonsSidebar from './modules/persons/PersonsSidebar'
// import WeightSidebar from './modules/weight/WeightSidebar'
// import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
// import Actions from '@nextcloud/vue/dist/Components/Actions'
// import WeightContent from './modules/weight/WeightContent'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'App',
	components: {
		Content,
		// AppContent,
		// AppSidebar,
		// AppSidebarTab,
		// ActionButton,
		// Actions,
		PersonsNavigation,
		// Notifications,
		// WeightSidebar,
		// PersonsSidebar,
		// WeightContent,
	},
	data: function() {
		return {
			loading: false,
			showSidebar: false,
		}
	},
	computed: {
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
						this.$store.commit('persons', response.data)
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

</style>
