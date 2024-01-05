<!--
	- @copyright Copyright (c) 2019 Florian Steffens <flost-dev@mailbox.org>
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
	<div class="inner-content">
		<div class="row first-row">
			<div class="col-4">
				<h2>{{ t('health', 'Welcome {name} to your health center', {name: person.name}) }}</h2>
			</div>
		</div>
		<div class="row">
			<div class="col-4">
				<p>
					{{ t('health', 'You can start here with giving you yourself a personal mission. Maybe you have a special target, a medical specification or a bet with your friends or partner. It could help you to describe it here. Giving yourself a bounty if you reach the targets or think about an emergency plan, if things getting worse is also a good idea …') }}
				</p>
			</div>
		</div>
		<div class="row">
			<div class="col-4">
				<h3>{{ t('health', 'Mission') }}</h3>
				<TextEditor :text.sync="person.personalMission" />
				<button v-if="canEdit"
					:aria-label="t('health', 'save')"
					@click="updateMission"
				>
					{{ t('health', 'Save ') }}
				</button>
			</div>
		</div>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import moment from '@nextcloud/moment'
import TextEditor from '../../shared/TextEditor'

export default {

	name: 'PersonsContent',

	components: {
		TextEditor,
	},

	filters: {
		formatMyDate(v) {
			return moment(v).format('DD.MM.YYYY')
		},
	},

	computed: {
		...mapState([]),
		...mapGetters(['person', 'canEdit']),
	},

	methods: {
		updateMission() {
			this.$store.dispatch('setValue', { key: 'personalMission', value: this.person.personalMission })
		},
	},
}
</script>
<style lang="scss" scoped>

	.inner-content {
		width: 50%;
		max-width: 900px;
		min-width: 600px;
		margin-left: auto;
		margin-right: auto;
	}

	@media only screen and (max-width: 1025px) {
		.inner-content {
			width: 100%;
			min-width: auto;
			max-width: none;
		}
	}

	.textarea-mission {
		// width: 67%;
		min-height: 200px;
	}

	.content-wrapper-health {
		width: 98%;
	}

	.widget {
		border: 1px solid gray;
		border-radius: 4px;
		background-color: #80808026;
		padding: 4px;
		width: 100px;
		margin: 10px;
		float: left;
	}

	.widget h3 {
		margin-top: 5px;
		margin-bottom: 2px;
		font-size: large;
	}

	.widget .date {
		color: gray;
		font-size: 0.8em;
		text-align: right;
	}

	.widget span {
		padding-left: 2px;
		padding-right: 2px;
	}

	.widget .firstNumber {
		font-weight: bold;
		text-align: right;
	}

	.widget .secondNumber {
		text-align: right;
	}

	.clear {
		clear: both;
	}
</style>
