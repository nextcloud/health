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
			:checked="person.feelingColumnMood"
			@change="$store.dispatch('setValue', { key: 'feelingColumnMood', value: $event.target.checked })">
			{{ t('health', 'Mood', {}) }}
		</ActionCheckbox>
		<ActionCheckbox
			:checked="person.feelingColumnSadnessLevel"
			@change="$store.dispatch('setValue', { key: 'feelingColumnSadnessLevel', value: $event.target.checked })">
			{{ t('health', 'Sadness level', {}) }}
		</ActionCheckbox>
		<ActionCheckbox
			:checked="person.feelingColumnSymptoms"
			@change="$store.dispatch('setValue', { key: 'feelingColumnSymptoms', value: $event.target.checked })">
			{{ t('health', 'Symptoms') }}
		</ActionCheckbox>
		<ActionCheckbox
			:checked="person.feelingColumnAttacks"
			@change="$store.dispatch('setValue', { key: 'feelingColumnAttacks', value: $event.target.checked })">
			{{ t('health', 'Attacks') }}
		</ActionCheckbox>
		<ActionCheckbox
			:checked="person.feelingColumnMedication"
			@change="$store.dispatch('setValue', { key: 'feelingColumnMedication', value: $event.target.checked })">
			{{ t('health', 'Medication') }}
		</ActionCheckbox>
		<ActionCheckbox
			:checked="person.feelingColumnPain"
			@change="$store.dispatch('setValue', { key: 'feelingColumnPain', value: $event.target.checked })">
			{{ t('health', 'Pain') }}
		</ActionCheckbox>
		<ActionCheckbox
			:checked="person.feelingColumnEnergy"
			@change="$store.dispatch('setValue', { key: 'feelingColumnEnergy', value: $event.target.checked })">
			{{ t('health', 'Energy') }}
		</ActionCheckbox>
	</ul>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import ActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox'

export default {
	name: 'FeelingSidebar',
	components: {
		ActionCheckbox,
	},
	computed: {
		...mapState(['activeModule', 'showSidebar']),
		...mapGetters(['person']),
	},
}
</script>
