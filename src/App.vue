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
	<Content :class="{'icon-loading': loading}" app-name="health">
		<PersonsNavigation />
		<AppContent>
			<div class="top-bar" />
			<div class="content-menu-topright">
				<Actions :title="'Details'">
					<ActionButton icon="icon-menu-sidebar" @click="$store.commit('showSidebar', !showSidebar)" />
				</Actions>
			</div>
			<PersonsContent v-if="person && activeModule === 'person'" />
			<WeightContent v-if="person && activeModule === 'weight' && person.enabledModuleWeight" />
		</AppContent>
		<AppSidebar
			v-show="showSidebar"
			v-if="persons !== null"
			:title="person.name"
			@close="$store.commit('showSidebar', false)">
			<template #primary-actions />
			<AppSidebarTab id="person" name="Person" icon="icon-user">
				<PersonsSidebar />
			</AppSidebarTab>
			<AppSidebarTab
				v-if="person"
				id="weight"
				name="Weight"
				icon="icon-quota">
				<WeightSidebar />
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
// import Notifications from './Notifications'
import PersonsSidebar from './modules/persons/PersonsSidebar'
import WeightSidebar from './modules/weight/WeightSidebar'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import WeightContent from './modules/weight/WeightContent'
import { mapState, mapGetters } from 'vuex'
import PersonsContent from './modules/persons/PersonsContent'

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
		// Notifications,
		WeightSidebar,
		PersonsSidebar,
		WeightContent,
		PersonsContent,
	},
	data: function() {
		return {
			loading: false,
		}
	},
	computed: {
		...mapState(['activePersonId', 'activeModule', 'showSidebar', 'persons']),
		...mapGetters(['person']),
	},
	created() {
		return this.$store.dispatch('loadPersons')
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
	h2 {
		margin-top: 40px;
	}
	h3 {
		font-size: 20px;
		margin-top: 40px;
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
	}
</style>
