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
		<li><h3>{{ t('health', 'General settings') }}</h3></li>
		<li><h4>{{ t('health', 'Age') }}</h4></li>
		<NcActionInput
			type="number"
			:value="person.age"
			icon="icon-user"
			@submit="updateAge"
		/>
		<li><h4>{{ t('health', 'Height') }}<span>{{ t('health', 'in cm') }}</span></h4></li>
		<NcActionInput
			type="number"
			:value="person.size"
			icon="icon-fullscreen"
			@submit="updateSize"
		/>
		<li><h4>{{ t('health', 'Sex') }}</h4></li>
		<NcActionRadio
			name="sex"
			value="female"
			:checked="person.sex === 'female'"
			@change="updateSex('female')"
		>
			{{ t('health', 'female') }}
		</NcActionRadio>
		<NcActionRadio
			name="sex"
			value="male"
			:checked="person.sex === 'male'"
			@change="updateSex('male')"
		>
			{{ t('health', 'male') }}
		</NcActionRadio>
		<li><h4>{{ t('health', 'Currency') }}</h4></li>
		<NcActionInput
			:value="person.currency"
			icon="icon-rename"
			@submit="$store.dispatch('setValue', { key: 'currency', value: $event.target[1].value })"
		/>
		<li><h3>{{ t('health', 'Manage modules') }}</h3></li>
		<NcActionCheckbox
			v-if="person"
			:checked="person.enabledModuleWeight"
			value="weight"
			@change="$store.dispatch('setValue', { key: 'enabledModuleWeight', value: $event.target.checked })"
		>
			{{ t('health', 'Weight') }}
		</NcActionCheckbox>
		<NcActionCheckbox
			v-if="person"
			:checked="person.enabledModuleFeeling"
			value="feeling"
			@change="$store.dispatch('setValue', { key: 'enabledModuleFeeling', value: $event.target.checked })"
		>
			{{ t('health', 'Feeling') }}
		</NcActionCheckbox>
		<NcActionCheckbox
			v-if="person"
			:checked="person.enabledModuleMeasurement"
			value="measurement"
			@change="$store.dispatch('setValue', { key: 'enabledModuleMeasurement', value: $event.target.checked })"
		>
			{{ t('health', 'Measurement') }}
		</NcActionCheckbox>
		<NcActionCheckbox
			v-if="person"
			:checked="person.enabledModuleActivities"
			value="activities"
			@change="$store.dispatch('setValue', { key: 'enabledModuleActivities', value: $event.target.checked })"
		>
			{{ t('health', 'Activities') }}
		</NcActionCheckbox>
		<NcActionCheckbox
			v-if="person && false"
			:checked="person.enabledModuleBreaks"
			value="breaks"
			@change="$store.dispatch('setValue', { key: 'enabledModuleBreaks', value: $event.target.checked })"
		>
			{{ t('health', 'Breaks') }}
		</NcActionCheckbox>
		<NcActionCheckbox
			v-if="person"
			:checked="person.enabledModuleMedicine"
			value="medicine"
			@change="$store.dispatch('setValue', { key: 'enabledModuleMedicine', value: $event.target.checked })"
		>
			{{ t('health', 'Medication') }}
		</NcActionCheckbox>
		<NcActionCheckbox
			v-if="person && false"
			:checked="person.enabledModuleActivities"
			value="activities"
			@change="$store.dispatch('setValue', { key: 'enabledModuleActivities', value: $event.target.checked })"
		>
			{{ t('health', 'Activities') }}
		</NcActionCheckbox>
		<NcActionCheckbox
			v-if="person"
			:checked="person.enabledModuleSleep"
			value="sleep"
			@change="$store.dispatch('setValue', { key: 'enabledModuleSleep', value: $event.target.checked })"
		>
			{{ t('health', 'Sleep') }}
		</NcActionCheckbox>
		<NcActionCheckbox
			v-if="person && false"
			:checked="person.enabledModuleNutrition"
			value="nutrition"
			@change="$store.dispatch('setValue', { key: 'enabledModuleNutrition', value: $event.target.checked })"
		>
			{{ t('health', 'Nutrition') }}
		</NcActionCheckbox>
		<NcActionCheckbox
			v-if="person"
			:checked="person.enabledModuleSmoking"
			value="smoking"
			@change="$store.dispatch('setValue', { key: 'enabledModuleSmoking', value: $event.target.checked })"
		>
			{{ t('health', 'Smoking') }}
		</NcActionCheckbox>
	</ul>
</template>

<script>
import NcActionRadio from '@nextcloud/vue/dist/Components/NcActionRadio'
import NcActionCheckbox from '@nextcloud/vue/dist/Components/NcActionCheckbox'
import NcActionInput from '@nextcloud/vue/dist/Components/NcActionInput'
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'PersonsSidebar',
	components: {
		NcActionCheckbox,
		NcActionRadio,
		NcActionInput,
	},
	computed: {
		...mapState(['activeModule', 'showSidebar', 'persons']),
		...mapGetters(['person']),
	},
	methods: {
		updateAge(e) {
			this.$store.dispatch('setValue', { key: 'age', value: e.target[1].value })
		},
		updateSize(e) {
			this.$store.dispatch('setValue', { key: 'size', value: e.target[1].value })
		},
		updateSex(sex) {
			this.$store.dispatch('setValue', { key: 'sex', value: sex })
		},
	},
}
</script>
<style lang="scss">
	.textarea-sidebar {
		padding-right: 14px;
	}

	textarea {
		height: 100px;
		width: 100%;
	}

	.subli {
		border-left: 5px solid #8080804a;
		border-top: 5px solid #8080804a;
		padding-left: 8px;
	}

	li h3 {
		border-bottom: 1px solid #80808057;
	}

	li h3, li h4 {
		margin-top: 20px;
	}

	h4 span {
		opacity: .7;
		font-size: 0.8em;
		margin-left: 5px;
	}
</style>
