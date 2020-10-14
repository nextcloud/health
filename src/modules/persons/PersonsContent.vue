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
	<div class="content-wrapper-health">
		<h2>{{ t('health', 'Welcome to your health center') }}</h2>
		<p>{{ t('health', 'You can start here with giving you yourself a personal mission. Maybe you have a special target, a medical specification or a bet with your friends or partner. It could help you to describe it here. Giving yourself a bounty if you reach the targets or think about an emergency plan, if the things getting worth is also a good idea...') }}</p>
		<textarea ref="mission" class="textarea-mission" :value="person.personalMission" />
		<br>
		<button
			@click="updateMission">
			{{ t('health', 'Save') }}
		</button>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'PersonsContent',
	components: {
	},
	computed: {
		...mapState([]),
		...mapGetters(['person']),
	},
	methods: {
		updateMission: function() {
			this.$store.dispatch('setValue', { key: 'personalMission', value: this.$refs.mission.value })
		},
	},
}
</script>
<style lang="scss">
	.textarea-mission {
		width: 67%;
	    min-height: 200px;
	}
	.content-wrapper-health {
		width: 98%;
	}
</style>
