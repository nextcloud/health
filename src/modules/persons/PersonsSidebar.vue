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
		<li><h3>General settings</h3></li>
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
		<li><h3>Manage modules</h3></li>
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
			this.$store.dispatch('updatePerson', { key: 'age', value: e.target[1].value })
		},
		updateSize: function(e) {
			this.$store.dispatch('updatePerson', { key: 'size', value: e.target[1].value })
		},
		updateSex: function(sex) {
			this.$store.dispatch('updatePerson', { key: 'sex', value: sex })
		},
		updateEnabledModules: function(e) {
			console.debug(e)
			if (e.target.value === 'weight') {
				this.$store.dispatch('updatePerson', { key: 'enabledModuleWeight', value: e.target.checked })
			}
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
