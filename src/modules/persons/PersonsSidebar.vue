<!--
	- @copyright Copyright (c) 2019 Florian Steffens
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
		<li><h4>Age</h4></li>
		<ActionInput
			type="number"
			:value="personAge"
			icon="icon-user"
			@submit="updateAge" />
		<li><h4>Size<span>in cm</span></h4></li>
		<ActionInput
			type="number"
			:value="personSize"
			icon="icon-fullscreen"
			@submit="updateSize" />
		<li><h4>Sex</h4></li>
		<ActionRadio
			name="sex"
			value="female"
			:checked="personSex === 'female'"
			@change="updateSex('female')">
			female
		</ActionRadio>
		<ActionRadio
			name="sex"
			value="male"
			:checked="personSex === 'male'"
			@change="updateSex('male')">
			male
		</ActionRadio>
		<li><h4>Manage modules</h4></li>
		<ActionCheckbox
			:checked="personModuleWeight"
			value="weight"
			@change="updateEnabledModules">
			Weight
		</ActionCheckbox>
	</ul>
</template>

<script>
import ActionRadio from '@nextcloud/vue/dist/Components/ActionRadio'
import ActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox'
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'PersonsSidebar',
	components: {
		ActionCheckbox,
		ActionRadio,
		ActionInput,
	},
	computed: {
		...mapState(['activeModule', 'showSidebar', 'persons']),
		...mapGetters(['person', 'personModuleWeight', 'personName', 'personAge', 'personSex', 'personSize']),
	},
	methods: {
		updateAge: function(e) {
			this.$store.commit('updatePersonAge', e.target[1].value)
		},
		updateSize: function(e) {
			this.$store.commit('updatePersonSize', e.target[1].value)
		},
		updateSex: function(e) {
			this.$store.commit('updatePersonSex', e)
		},
		updateEnabledModules: function(e) {
			if (e.target.value === 'weight') {
				this.$store.commit('updatePersonEnabledModuleWeight', !this.personModuleWeight)
			}
		},
	},
}
</script>
<style lang="scss">
	li h4 {
		margin-top: 10px;
	}
	h4 span {
		opacity: .7;
		font-size: 0.8em;
		margin-left: 5px;
	}
</style>
