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
	<ul>
		<div v-if="loading" class="icon-loading" />
		<div v-if="!loading">
			<li><h3>{{ t('health', 'Data connection', {}) }}</h3></li>
			<li><h4>{{ t('health', 'Data source', {}) }}<span><br>{{ t('tables', 'Set the path within your cloud to the exported file from gadgetbridge. For example: /Gadgetbridge/backup.sqlite') }}</span></h4></li>
			<ActionInput
				:value="sourcePath"
				type="text"
				icon="icon-category-customization"
				@submit="setSourcePathValue" />
			<li><h4>{{ t('health', 'Background import', {}) }}<span><br>{{ t('tables', 'Import data source automatically in the background every hour.') }}</span></h4></li>
			<ActionCheckbox :checked="backgroundImport" @change="setCheckboxValue($event.target.checked)">
				{{ t('health', 'Auto import') }}
			</ActionCheckbox>
		</div>
	</ul>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'
import ActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { showError, showSuccess } from '@nextcloud/dialogs'

export default {
	name: 'GadgetbridgeSidebar',
	components: {
		ActionInput,
		ActionCheckbox,
	},
	data() {
		return {
			sourcePath: null,
			backgroundImport: false,
			loading: false,
		}
	},
	computed: {
		...mapState(['activeModule', 'showSidebar']),
		...mapGetters(['person']),
	},
	watch: {
		person() {
			this.triggerLoadSettings()
		},
	},
	async beforeMount() {
		await this.triggerLoadSettings()
	},
	methods: {
		async triggerLoadSettings() {
			this.loading = true
			await this.loadSettingsFromBE()
			this.loading = false
		},
		async loadSettingsFromBE() {
			try {
				const response = await axios.get(generateUrl('/apps/health/gadgetbridge/settings/person/' + this.person.id))
				console.debug('settings loaded: ', response)
				this.sourcePath = response.data.sqliteSourcePath
				this.backgroundImport = response.data.backgroundImport
			} catch (e) {
				console.error(e)
				showError(t('health', 'Could not load gadgetbridge settings.'))
			}
		},
		setCheckboxValue(value) {
			this.backgroundImport = !!value
			this.sendSettingsToBE()
		},
		setSourcePathValue(e) {
			this.sourcePath = e.target[1].value
			this.sendSettingsToBE()
		},
		async sendSettingsToBE() {
			try {
				const data = {
					personId: this.person.id,
					sqliteSourcePath: this.sourcePath,
					backgroundImport: this.backgroundImport,
				}
				const response = await axios.post(generateUrl('/apps/health/gadgetbridge/settings'), data)
				console.debug('settings saved: ', response)
				showSuccess(t('health', 'Gadgetbridge settings saved successfully.'))
			} catch (e) {
				console.error(e)
				showError(t('health', 'Could not save gadgetbridge settings.'))
			}
		},
	},
}
</script>
<style lang="scss" scoped>

	.input-hint {
		color: var(--color-text-lighter);
		margin-left: 45px;
		font-weight: 200;
	}

</style>
