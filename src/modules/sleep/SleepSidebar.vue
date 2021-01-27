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
		<li><h3>{{ t('health', 'Column selection', {}) }}</h3></li>
		<ActionCheckbox
			:checked="person.sleepColumnQuality"
			@change="$store.dispatch('setValue', { key: 'sleepColumnQuality', value: $event.target.checked })">
			{{ t('health', 'Quality', {}) }}
		</ActionCheckbox>
		<ActionCheckbox
			:checked="person.sleepColumnWakeups"
			@change="$store.dispatch('setValue', { key: 'sleepColumnWakeups', value: $event.target.checked })">
			{{ t('health', 'Wakeups', {}) }}
		</ActionCheckbox>
	</ul>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import ActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox'

export default {
	name: 'SleepSidebar',
	components: {
		ActionCheckbox,
	},
	computed: {
		...mapState(['activeModule', 'showSidebar']),
		...mapGetters(['person']),
	},
}
</script>
