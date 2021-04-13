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
			:checked="person.activitiesColumnCalories"
			@change="$store.dispatch('setValue', { key: 'activitiesColumnCalories', value: $event.target.checked })">
			{{ t('health', 'Burned calories', {}) }}
		</ActionCheckbox>
		<ActionCheckbox
			:checked="person.activitiesColumnDuration"
			@change="$store.dispatch('setValue', { key: 'activitiesColumnDuration', value: $event.target.checked })">
			{{ t('health', 'Activity duration [minutes]', {}) }}
		</ActionCheckbox>
		<ActionCheckbox
			:checked="person.activitiesColumnCategory"
			@change="$store.dispatch('setValue', { key: 'activitiesColumnCategory', value: $event.target.checked })">
			{{ t('health', 'Category', {}) }}
		</ActionCheckbox>
		<ActionCheckbox
			:checked="person.activitiesColumnFeeling"
			@change="$store.dispatch('setValue', { key: 'activitiesColumnFeeling', value: $event.target.checked })">
			{{ t('health', 'Feeling after the activity', {}) }}
		</ActionCheckbox>
		<ActionCheckbox
			:checked="person.activitiesColumnIntensity"
			@change="$store.dispatch('setValue', { key: 'activitiesColumnIntensity', value: $event.target.checked })">
			{{ t('health', 'Activity intensity', {}) }}
		</ActionCheckbox>
		<ActionCheckbox
			:checked="person.activitiesColumnDistance"
			@change="$store.dispatch('setValue', { key: 'activitiesColumnDistance', value: $event.target.checked })">
			{{ t('health', 'Distance', {}) }}
		</ActionCheckbox>
		<li><h4>{{ t('health', 'Unit for distance', {}) }}</h4></li>
		<ActionInput
			type="text"
			:value="person.activitiesDistanceUnit"
			icon="icon-category-customization"
			@submit="updateDistanceUnit" />
	</ul>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import ActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox'
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'

export default {
	name: 'ActivitiesSidebar',
	components: {
		ActionCheckbox,
		ActionInput,
	},
	computed: {
		...mapState(['activeModule', 'showSidebar']),
		...mapGetters(['person']),
	},
	methods: {
		updateDistanceUnit(e) {
			this.$store.dispatch('setValue', { key: 'activitiesDistanceUnit', value: e.target[1].value })
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
