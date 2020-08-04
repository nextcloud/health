<template>
	<Content :class="{'icon-loading': loading}" app-name="health">
		<AppNavigation>
			<ul id="app-vueexample-navigation">
				<AppNavigationItem v-for="(person, index) in persons"
					:key="person.id"
					:title="person.name"
					:allow-collapse="true"
					:open="(index === 0)?true:false"
					icon="icon-user">
					<template>
						<AppNavigationItem title="Weight" icon="icon-quota" />
					</template>
				</AppNavigationItem>
				<AppNavigationItem
					title="New person"
					icon="icon-add"
					:pinned="true"
					@click.prevent.stop="showNewPersonForm = !showNewPersonForm">
					<div v-show="showNewPersonForm" class="person-create">
						<form
							@submit.prevent.stop="createPerson">
							<input :placeholder="'Name'" type="text" required>
							<input type="submit" value="" class="icon-confirm">
							<Actions>
								<ActionButton :class="ab-integrated" icon="icon-close" @click.stop.prevent="createPersonAbbord" />
							</Actions>
						</form>
					</div>
				</AppNavigationItem>
			</ul>
		</AppNavigation>
		<AppContent>
			<span>This is the content</span>
			<button @click="show = !show">
				Toggle sidebar
			</button>
			<div style="background-color: red;">
				<button @click="alert();">
					Toggle sidebar 2
				</button>
			</div>
		</AppContent>
		<AppSidebar v-show="show"
			:title="persons[1].name"
			subtitle="4,3 MB, last edited 41 days ago"
			:starred.sync="starred"
			@close="show=false">
			<template #primary-actions>
				<button class="primary">
					Button 1
				</button>
				<input id="link-checkbox"
					name="link-checkbox"
					class="checkbox link-checkbox"
					type="checkbox">
				<label for="link-checkbox" class="link-checkbox-label">Do something</label>
			</template>
			<template #secondary-actions>
				<ActionButton icon="icon-edit" @click="alert('Edit')">
					Edit
				</ActionButton>
				<ActionButton icon="icon-delete" @click="alert('Delete')">
					Delete
				</ActionButton>
				<ActionLink icon="icon-external" title="Link" href="https://nextcloud.com" />
			</template>
			<AppSidebarTab id="vueexample" name="Vueexample" icon="icon-vueexample">
				this is the vueexample tab
			</AppSidebarTab>
			<AppSidebarTab id="activity" name="Activity" icon="icon-activity">
				this is the activity tab
			</AppSidebarTab>
			<AppSidebarTab id="comments" name="Comments" icon="icon-comment">
				this is the comments tab
			</AppSidebarTab>
			<AppSidebarTab id="sharing" name="Sharing" icon="icon-shared">
				this is the sharing tab
			</AppSidebarTab>
			<AppSidebarTab id="versions" name="Versions" icon="icon-history">
				this is the versions tab
			</AppSidebarTab>
		</AppSidebar>
	</Content>
</template>

<script>
import Content from '@nextcloud/vue/dist/Components/Content'
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppSidebar from '@nextcloud/vue/dist/Components/AppSidebar'
import AppSidebarTab from '@nextcloud/vue/dist/Components/AppSidebarTab'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ActionLink from '@nextcloud/vue/dist/Components/ActionLink'
import Actions from '@nextcloud/vue/dist/Components/Actions'

export default {
	name: 'App',
	components: {
		Content,
		AppContent,
		AppNavigation,
		AppNavigationItem,
		AppSidebar,
		AppSidebarTab,
		ActionButton,
		ActionLink,
		Actions,
	},
	data: function() {
		return {
			loading: false,
			date: Date.now() + 86400000 * 3,
			date2: Date.now() + 86400000 * 3 + Math.floor(Math.random() * 86400000 / 2),
			show: true,
			showNewPersonForm: false,
			starred: false,
			persons: [
				{
					id: 1,
					name: 'Me, Florian',
				},
				{
					id: 2,
					name: 'Madita',
				},
			],
		}
	},
	methods: {
		addOption(val) {
			this.options.push(val)
			this.select.push(val)
		},
		previous(data) {
			console.debug(data)
		},
		next(data) {
			console.debug(data)
		},
		close(data) {
			console.debug(data)
		},
		newButtonAction(e) {
			console.debug(e)
		},
		log(e) {
			console.debug(e)
		},
		alert(e) {
			alert('Alert: ' + this.show)
		},
		createPerson(e) {
			const newId = this.persons.length + 1
			this.persons.push({ id: newId, name: e.currentTarget.childNodes[0].value })
			this.showNewPersonForm = !this.showNewPersonForm
			e.currentTarget.childNodes[0].value = ''
			window.console.log(this.persons)
		},
		createPersonAbbord(e) {
			this.showNewPersonForm = !this.showNewPersonForm
			e.currentTarget.childNodes[0].value = ''
		},
	},
}
</script>
<style lang="scss" scoped>
	.person-create {
		order: 1;
		display: flex;
		height: 44px;
		padding-left: 10px;
		form {
			display: flex;
			flex-grow: 1;
			input[type='text'] {
				flex-grow: 1;
			}
		}
	}
</style>
