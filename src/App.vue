<template>
	<Content :class="{'icon-loading': loading}" app-name="health">
		<AppNavigation>
			<ul id="app-vueexample-navigation">
				<AppNavigationItem v-for="(person, index) in persons"
					:key="index"
					:title="person.name"
					:allow-collapse="true"
					:open="(index === 0)?true:false"
					icon="icon-user"
					:editable="true"
					edit-label="edit name"
					@update:menuOpen="menuOpenPersonId = index"
					@update:title="personUpdateName"
					@click="activePersonId = index; activeModule = 'person'">
					<template slot="actions">
						<ActionButton
							:close-after-click="true"
							icon="icon-detail"
							@click="activePersonId = index; showDetails = true">
							Show details
						</ActionButton>
						<ActionButton icon="icon-delete" :close-after-click="true" @click="personDelete">
							Delete
						</ActionButton>
					</template>
					<template>
						<AppNavigationItem
							v-if="persons[index].enabledModules.weight"
							title="Weight"
							icon="icon-quota"
							@click="activePersonId = index; activeModule = 'weight'" />
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
							<button @click="messagesDelete(index)">Ok, got it.</button>
						</span>
					</div>
				</div>
				<div>
					menuOpenPersonId: {{ menuOpenPersonId }}<br>
					activePersonId: {{ activePersonId }}<br>
					showNewPersonForm: {{ showNewPersonForm }}<br>
					module weight: {{ persons[activePersonId].enabledModules.weight }}<br>
					active module: {{ activeModule }}
				</div>
				<div
					v-if="activeModule === 'weight' && persons[activePersonId].enabledModules.weight">
					<h2>Weight<span>for {{ persons[activePersonId].name }}</span></h2>
					<div v-if="persons[activePersonId].weight.target != ''">
						<h3>Target</h3>
						<p>Your actual weight is {{ persons[activePersonId].weight.data[(persons[activePersonId].weight.data.length - 1)].weight }} {{ persons[activePersonId].weight.target }} and your target values {{ persons[activePersonId].weight.target }} {{ persons[activePersonId].weight.unit }}.</p>
						<p
							v-if="persons[activePersonId].weight.data[(persons[activePersonId].weight.data.length - 1)].weight > persons[activePersonId].weight.target">
							There are {{ persons[activePersonId].weight.data[(persons[activePersonId].weight.data.length - 1)].weight > persons[activePersonId].weight.target }} {{ persons[activePersonId].weight.unit }} to go.
							<ProgressBar value="10" />
						</p>
						<p
							v-else>
							Good, you reached your target!
						</p>
					</div>
					<h3>Chart</h3>
					<h3>Data</h3>
				</div>
				<div v-else>
					else
				</div>
			</div>
		</AppContent>
		<AppSidebar v-show="showDetails"
			:title="persons[activePersonId].name"
			subtitle="created 41 days ago"
			@close="showDetails=false">
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
					<li><h4>Weight target<span>in {{ persons[activePersonId].weight.unit }}<br>blank for none</span></h4></li>
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
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppSidebar from '@nextcloud/vue/dist/Components/AppSidebar'
import AppSidebarTab from '@nextcloud/vue/dist/Components/AppSidebarTab'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'
import ActionRadio from '@nextcloud/vue/dist/Components/ActionRadio'
import ActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox'
import ProgressBar from '@nextcloud/vue/dist/Components/ProgressBar'

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
		ActionInput,
		ActionRadio,
		ActionCheckbox,
		ProgressBar,
	},
	data: function() {
		return {
			loading: false,
			showDetails: true,
			showNewPersonForm: false,
			menuOpenPersonId: 0,
			activePersonId: 0,
			activeModule: 'person',
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
			persons: [
				{
					id: 1,
					name: 'Me, Florian',
					notifications: [
						{
							type: 'green',
							message: 'test notofication',
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
						target: '70',
						data: [
							{
								date: '',
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
								date: '',
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
							type: 'green',
							message: 'test notofication',
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
						target: '70',
						data: [
							{
								date: '',
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
				this.notificationAdd('B', 'warn', 'You can not delete the last person.')
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
		notificationAdd(client, type, text) {
			if (client === 'F' || client === 'FB') {
				this.persons[this.activePersonId].notifications.push(
					{
						type: type,
						message: text,
					}
				)
				this.log('notification added F or FB')
			} else if (client === 'B' || client === 'FB') {
				let t
				if (type === 'green') {
					t = 'hint'
				} else if (type === 'orange') {
					t = 'warn'
				} else {
					t = 'error'
				}
				this.messageAdd(t, text)
				this.log('notification added B or FB')
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
	button, .button, input[type='button'], input[type='submit'], input[type='reset'] {
		min-height: auto;
		border-radius: var(--border-radius);
		padding: 4px;
	}
	.message {
		padding: 7px;
	}
	.message span {
		position: relative;
		right: -20px;
	}
	.green {
		color: green;
	}
	.orange {
		color: orange;
	}
	.red {
		color: red;
	}
	li h4 {
		margin-top: 10px;
	}
	span {
		opacity: .7;
		font-size: 0.8em;
		margin-left: 5px;
	}
</style>
