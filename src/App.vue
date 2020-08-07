<template>
	<Content :class="{'icon-loading': loading}" app-name="health">
		<AppNavigation>
			<ul id="app-vueexample-navigation">
				<AppNavigationItem v-for="(person, index) in persons"
					:key="person.id"
					:title="person.name"
					:allow-collapse="true"
					:open="(index === 0)?true:false"
					icon="icon-user"
					:editable="true"
					edit-label="edit name"
					@update:menuOpen="menuOpenPersonId = index"
					@update:title="personUpdateName"
					@click="activePersonId = index">
					<template slot="actions">
						<ActionButton :close-after-click="true" icon="icon-detail" @click="activePersonId = index; showDetails = true">
							Show details
						</ActionButton>
						<ActionButton icon="icon-delete" @click="personDelete">
							Delete
						</ActionButton>
					</template>
					<template>
						<AppNavigationItem title="Weight" icon="icon-quota" />
					</template>
				</AppNavigationItem>
				<AppNavigationItem
					title="New person"
					icon="icon-add"
					:pinned="true"
					@click.prevent.stop="createPersonOpen">
					<div v-show="showNewPersonForm" class="person-create">
						<form
							@submit.prevent.stop="createPerson">
							<input
								ref="newPersonName"
								:placeholder="'Name'"
								type="text"
								required>
							<input type="submit" value="" class="icon-confirm">
							<Actions>
								<ActionButton class="ab-integrated" icon="icon-close" @click.stop.prevent="createPersonAbbord" />
							</Actions>
						</form>
					</div>
				</AppNavigationItem>
			</ul>
		</AppNavigation>
		<AppContent>
			<div class="content-wrapper">
				<div class="messages">
					<div v-for="(message, index) in messages" :key="index" :class="{'message':true, 'error': message.type == 'error', 'warn': message.type == 'warn', 'hint': message.type == 'hint' }">
						{{ message.message }}
						<span>
							<button @click="messagesDelete(index)">Ok, git it.</button>
						</span>
					</div>
				</div>
				<div>
					menuOpenPersonId: {{ menuOpenPersonId }}<br>
					activePersonId: {{ activePersonId }}<br>
					showNewPersonForm: {{ showNewPersonForm }}
				</div>
			</div>
		</AppContent>
		<AppSidebar v-show="showDetails"
			:title="persons[activePersonId].name"
			subtitle="created 41 days ago"
			@close="showDetails=false">
			<template #primary-actions>
				<div class="detailsMainInfo">
					To go for weight target: 5kg
				</div>
				<div class="detailsMainInfo">
					Drink some water
				</div>
			</template>
			<AppSidebarTab id="person" name="Person" icon="icon-user">
				this is the activity tab
			</AppSidebarTab>
			<AppSidebarTab id="weight" name="Weight" icon="icon-quota">
				this is the activity tab
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
		Actions,
	},
	data: function() {
		return {
			loading: false,
			showDetails: true,
			showNewPersonForm: false,
			menuOpenPersonId: 0,
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
			activePersonId: 0,
			messages: [
				{
					type: 'hint',
					message: 'succesfully loaded',
				},
				{
					type: 'error',
					message: 'test error',
				},
			],
		}
	},
	methods: {
		log(e) {
			console.debug(e)
		},
		createPerson(e) {
			const newId = this.persons.length + 1
			this.persons.push({ id: newId, name: e.currentTarget.childNodes[0].value })
			this.showNewPersonForm = false
			this.showDetails = true
			this.activePersonId = this.persons.length - 1
			e.currentTarget.childNodes[0].value = ''
			this.log('createPerson new person added')
			this.log(this.persons)
		},
		createPersonAbbord(e) {
			this.showNewPersonForm = !this.showNewPersonForm
			e.currentTarget.childNodes[0].value = ''
			this.log('createPersonAbbord close form')
		},
		createPersonOpen(e) {
			this.showNewPersonForm = !this.showNewPersonForm
			this.$refs.newPersonName.focus()
			this.log('createPerson open form')
		},
		personUpdateName(e) {
			this.persons[this.menuOpenPersonId].name = e
			this.log('update name => id: ' + this.menuOpenPersonId + 'new name: ' + e)
		},
		personDelete(e) {
			this.log('try to delete person: ' + this.persons[this.menuOpenPersonId].name)
			if (this.persons.length === 1) {
				this.messageAdd('warn', 'You can not delete the last person.')
				this.log('could not delete person, can not last person')
			} else {
				this.persons.splice(this.menuOpenPersonId, 1)
				this.activePersonId = 0
				this.log(this.persons)
			}
		},
		messageAdd(type, text) {
			this.messages.push({
				type: type,
				message: text,
			})
		},
		messagesDelete(index) {
			this.messages.splice(index, 1)
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
	.detailsMainInfo {
		padding: 10px;
	}
	.message {
		margin-bottom: 5px;
	}
	.error {
		border: 1px solid red;
	}
	.hint {
		border: 1px solid green;
	}
	.warn {
		border: 1px solid orange;
	}
	.content-wrapper {
		padding: 35px 10px 10px 10px;
	}
</style>
