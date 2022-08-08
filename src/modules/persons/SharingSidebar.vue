<!--
	- @copyright Copyright (c) 2020 Florian Steffens <flost-dev@mailbox.org>
	-
	- @author Marcus Nitzschke <mail@kendix.org>
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
	<div>
		<Multiselect
			v-if="isCurrentUser(person.userId)"
			class="user-group-multiselect"
			v-model="addAcl"
			:placeholder="t('health', 'Share person with a user or group …')"
			:options="formatedSharees"
			:user-select="true"
			label="displayName"
			:loading="isLoading || !!isSearching"
			:disabled="isLoading"
			track-by="multiselectKey"
			:internal-search="false"
			@input="clickAddAcl"
			@search-change="asyncFind"
		>
			<template #noOptions>
				{{ isSearching ? t('health', 'Searching for users and groups …') : t('health', 'No participants found') }}
			</template>
			<template #noResult>
				{{ isSearching ? t('health', 'Searching for users and groups …') : t('health', 'No participants found') }}
			</template>
		</Multiselect>

		<ul
			id="shareWithList"
			class="shareWithList"
		>
			<li>
				<Avatar :user="person.userId" />
				<span class="has-tooltip username">
					{{ person.userId }}
					<span v-if="!isCurrentUser(person.userId)" class="person-originator-label">
						{{ t('health', 'Originator') }}
					</span>
				</span>
			</li>
			<li v-for="acl in person.acl" :key="acl.id">
				<Avatar v-if="acl.type===0" :user="acl.participant.uid" />
				<div v-if="acl.type===1" class="avatardiv icon icon-group" />
				<div v-if="acl.type===7" class="avatardiv icon icon-circles" />
				<span class="has-tooltip username">
					{{ acl.participant.displayname }}
					<span v-if="acl.type===1">({{ t('health', 'Group') }})</span>
				</span>

				<ActionCheckbox v-if="isCurrentUser(acl.participant.uid) || canManage" :checked="acl.permissionEdit" @change="clickEditAcl(acl)">
					{{ t('health', 'Can edit') }}
				</ActionCheckbox>
				<Actions v-if="isCurrentUser(acl.participant.uid) || canManage" :force-menu="true">
					<ActionCheckbox :checked="acl.permissionManage" @change="clickManageAcl(acl)">
						{{ t('health', 'Can manage') }}
					</ActionCheckbox>
					<ActionButton icon="icon-delete" @click="clickDeleteAcl(acl)">
						{{ t('health', 'Delete') }}
					</ActionButton>
				</Actions>
			</li>
		</ul>
	</div>
</template>

<script>
import { Avatar, Multiselect, Actions, ActionButton, ActionCheckbox } from '@nextcloud/vue'
import { mapGetters, mapState } from 'vuex'
import { getCurrentUser } from '@nextcloud/auth'
import { showError } from '@nextcloud/dialogs'
import debounce from 'lodash.debounce'

export default {
	name: 'SharingSidebar',
	components: {
		Avatar,
		Actions,
		ActionButton,
		ActionCheckbox,
		Multiselect,
	},
	data() {
		return {
			isLoading: false,
			isSearching: false,
			addAcl: null,
			addAclForAPI: null,
			newOwner: null,
		}
	},
	computed: {
		...mapState([
			'sharees',
		]),
		...mapGetters([
			'canEdit',
			'canManage',
			'person',
		]),
		isCurrentUser() {
			return (uid) => uid === getCurrentUser().uid
		},
		formatedSharees() {
			return this.unallocatedSharees.map(item => {
				const sharee = {
					user: item.value.shareWith,
					displayName: item.label,
					icon: 'icon-user',
					multiselectKey: item.shareType + ':' + item.primaryKey,
				}

				if (item.value.shareType === 1) {
					sharee.icon = 'icon-group'
					sharee.isNoUser = true
				}
				if (item.value.shareType === 7) {
					sharee.icon = 'icon-circles'
					sharee.isNoUser = true
				}

				sharee.value = item.value
				return sharee
			}).slice(0, 10)
		},
		unallocatedSharees() {
			return this.sharees.filter((sharee) => {
				const foundIndex = this.person.acl && this.person.acl.findIndex((acl) => {
					return acl.participant.uid === sharee.value.shareWith && acl.participant.type === sharee.value.shareType
				})
				if (foundIndex === -1) {
					return true
				}
				return false
			})
		},
	},
	mounted() {
		this.asyncFind('')
	},
	methods: {
		debouncedFind: debounce(async function(query) {
			this.isSearching = true
			await this.$store.dispatch('loadSharees', query)
			this.isSearching = false
		}, 300),
		async asyncFind(query) {
			await this.debouncedFind(query)
		},
		async clickAddAcl() {
			this.addAclForAPI = {
				type: this.addAcl.value.shareType,
				participant: this.addAcl.value.shareWith,
				permissionEdit: false,
				permissionManage: false,
			}
			this.isLoading = true
			try {
				await this.$store.dispatch('addAclToCurrentPerson', this.addAclForAPI)
			} catch (e) {
				const errorMessage = t('health', 'Failed to create share with {displayName}', { displayName: this.addAcl.displayName })
				console.error(errorMessage, e)
				showError(errorMessage)
			}
			this.addAcl = null
			this.isLoading = false
		},
		clickEditAcl(acl) {
			this.addAclForAPI = Object.assign({}, acl)
			this.addAclForAPI.permissionEdit = !acl.permissionEdit
			this.$store.dispatch('updateAclFromCurrentPerson', this.addAclForAPI)
		},
		clickManageAcl(acl) {
			this.addAclForAPI = Object.assign({}, acl)
			this.addAclForAPI.permissionManage = !acl.permissionManage
			this.$store.dispatch('updateAclFromCurrentPerson', this.addAclForAPI)
		},
		clickDeleteAcl(acl) {
			this.$store.dispatch('deleteAclFromCurrentPerson', acl)
		},
	},
}
</script>
<style scoped>
	#shareWithList {
		margin-bottom: 20px;
	}

	#shareWithList li {
		display: flex;
		align-items: center;
	}

	.username {
		padding: 12px 9px;
		flex-grow: 1;
	}

	.person-originator-label {
		opacity: .7;
	}

	.avatardiv {
		background-color: var(--color-background-dark);
		border-radius: 16px;
		width: 32px;
		height: 32px;
	}

	.multiselect.user-group-multiselect {
		display: block;
	}

</style>
