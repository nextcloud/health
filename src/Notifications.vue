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
	<div class="notifications">
		<div v-for="(message, index) in notifications" :key="'public' + index" :class="{ 'notification': true, 'error': message.type == 'error', 'warn': message.type == 'warn', 'hint': message.type == 'hint', 'good': message.type == 'good' }">
			{{ message.text }}
			<span>
				<button @click="deleteNotification(index)">Ok, got it.</button>
			</span>
		</div>
		<div v-for="(message, index) in persons[activePersonId].notifications" :key="'private' + index" :class="{ 'notification': true, 'error': message.type == 'error', 'warn': message.type == 'warn', 'hint': message.type == 'hint', 'good': message.type == 'good' }">
			{{ message.text }}
			<span>
				<button @click="deletePrivateNotification(index)">Ok, got it.</button>
			</span>
		</div>
	</div>
</template>

<script>

export default {
	name: 'Notifications',
	components: {
	},
	props: {
		persons: {
			type: Array,
			default: null,
		},
		activePersonId: {
			type: Number,
			default: null,
		},
		notifications: {
			type: Array,
			default: null,
		},
	},
	data: function() {
		return {
		}
	},
	methods: {
		deleteNotification: function(i) {
			const n = this.notifications
			n.splice(i, 1)
			this.$emit('update:notifications', n)
		},
		deletePrivateNotification: function(i) {
			const p = this.persons
			p[this.activePersonId].notifications.splice(i, 1)
			this.$emit('update:persons', p)
		},
	},
}
</script>
<style lang="scss" scoped>
	.notification {
		padding: 7px;
		margin-bottom: 5px;
	}
	.notification span {
		position: relative;
		right: -20px;
	}
	.error {
		border: 1px solid red;
	}
	.hint {
		border: 1px solid blue;
	}
	.warn {
		border: 1px solid orange;
	}
	.good {
		border: 1px solid green;
	}
	button, .button, input[type='button'], input[type='submit'], input[type='reset'] {
		min-height: auto;
		border-radius: var(--border-radius);
		padding: 4px;
	}
</style>
