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
		<li><h3>{{ t('health', 'General settings', {}) }}</h3></li>
		<li><h4>{{ t('health', 'Price for a cigarette') }}{{ person.currency ? ' [' + person.currency + ']' : '' }}</h4></li>
		<ActionInput
			type="number"
			step="0.1"
			:value="person.smokingPrice"
			icon="icon-category-customization"
			@submit="$store.dispatch('setValue', { key: 'smokingPrice', value: $event.target[1].value })" />
		<li><h4>{{ t('health', 'Starting with how many cigarettes a day') }}</h4></li>
		<ActionInput
			type="number"
			:value="person.smokingStartValue"
			icon="icon-category-customization"
			@submit="$store.dispatch('setValue', { key: 'smokingStartValue', value: $event.target[1].value })" />
		<li><h4>{{ t('health', 'Goal cigarettes per day') }}</h4></li>
		<ActionInput
			type="number"
			:value="person.smokingGoal"
			icon="icon-category-customization"
			@submit="$store.dispatch('setValue', { key: 'smokingGoal', value: $event.target[1].value })" />

		<li><h3>{{ t('health', 'Column selection', {}) }}</h3></li>
		<ActionCheckbox
			:checked="person.smokingColumnCigarettes"
			@change="$store.dispatch('setValue', { key: 'smokingColumnCigarettes', value: $event.target.checked })">
			{{ t('health', 'Cigarettes', {}) }}
		</ActionCheckbox>
		<ActionCheckbox
			:checked="person.smokingColumnDesireLevel"
			@change="$store.dispatch('setValue', { key: 'smokingColumnDesireLevel', value: $event.target.checked })">
			{{ t('health', 'Desire level', {}) }}
		</ActionCheckbox>
		<ActionCheckbox
			:checked="person.smokingColumnSavedMoney"
			@change="$store.dispatch('setValue', { key: 'smokingColumnSavedMoney', value: $event.target.checked })">
			{{ t('health', 'Saved money', {}) }}
		</ActionCheckbox>
		<div v-if="!person.smokingPrice" class="input-hint">
			{{ t('health', 'Please set a price to calculate saved money.', {}) }}
		</div>
		<div v-if="!person.smokingStartValue" class="input-hint">
			{{ t('health', 'Please set the number of cigarettes a day you are starting with to calculate saved money.', {}) }}
		</div>
	</ul>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import ActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox'
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'

export default {
	name: 'SmokingSidebar',
	components: {
		ActionCheckbox,
		ActionInput,
	},
	computed: {
		...mapState(['activeModule', 'showSidebar']),
		...mapGetters(['person']),
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
