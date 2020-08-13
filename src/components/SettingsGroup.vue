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
	<div class="settings-group">
		<h3>SettingsGroup {{ test }}</h3><br>
		<button
			@click="$emit('update:test', 1)">
			Test parent
		</button>
	</div>
</template>

<script>
import moment from '@nextcloud/moment'

export default {
	name: 'SettingsGroup',
	components: {
	},
	props: {
		test: {
			type: Number,
			default: 0,
		},
		title: {
			type: String,
			default: 'SettingsGroup Title',
		},
		fields: {
			type: Object,
			default: null,
		},
	},
	computed: {
		time: function() {
			return moment()
		},
	},
	methods: {

	},
}
</script>
<style>
</style>
