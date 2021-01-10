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
	<div>
		<h2>{{ t('health', 'Welcome to your health center') }}</h2>
		<div class="widgets">
			<div v-if="person && personData && personData.lastWeight" class="widget">
				<h3>{{ t('health', 'Weight') }}</h3>
				<div class="date">
					{{ personData.lastWeight.date | formatMyDate }}
				</div>
				<div class="firstNumber">
					{{ personData.lastWeight.weight }}<span>{{ person.weightUnit }}</span>
				</div>
				<div v-if="person.weightTarget" class="secondNumber">
					<span>{{ t('health', 'Target') }}</span>{{ person.weightTarget }}<span>{{ person.weightUnit }}</span>
				</div>
			</div>
		</div>
		<div class="clear" />
		<p>{{ t('health', 'You can start here with giving you yourself a personal mission. Maybe you have a special target, a medical specification or a bet with your friends or partner. It could help you to describe it here. Giving yourself a bounty if you reach the targets or think about an emergency plan, if the things getting worth is also a good idea...') }}</p>
		<textarea ref="mission" class="textarea-mission" :value="person.personalMission" />
		<br>
		<button
			@click="updateMission">
			{{ t('health', 'Save ') }}
		</button>
		<LineChart2 :chartdata="data" :options="options" />
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import moment from '@nextcloud/moment'
import LineChart2 from '../generic/charts/LineChart2'

export default {
	name: 'PersonsContent',
	filters: {
		formatMyDate: function(v) {
			return moment(v).format('DD.MM.YYYY')
		},
	},
	components: {
		LineChart2,
	},
	data: function() {
		return {
			data: {
				labels: ['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange'],
				datasets: [{
					label: '# of Votes',
					data: [12, 19, 3, 5, 2, 3],
					backgroundColor: [
						'rgba(255, 99, 132, 0.2)',
						'rgba(54, 162, 235, 0.2)',
						'rgba(255, 206, 86, 0.2)',
						'rgba(75, 192, 192, 0.2)',
						'rgba(153, 102, 255, 0.2)',
						'rgba(255, 159, 64, 0.2)',
					],
					borderColor: [
						'rgba(255, 99, 132, 1)',
						'rgba(54, 162, 235, 1)',
						'rgba(255, 206, 86, 1)',
						'rgba(75, 192, 192, 1)',
						'rgba(153, 102, 255, 1)',
						'rgba(255, 159, 64, 1)',
					],
					borderWidth: 1,
				}],
			},
			options: {},
		}
	},
	computed: {
		...mapState(['personData']),
		...mapGetters(['person']),
	},
	methods: {
		updateMission: function() {
			this.$store.dispatch('setValue', { key: 'personalMission', value: this.$refs.mission.value })
		},
		getRandomInt() {
			return Math.floor(Math.random() * (50 - 5 + 1)) + 5
		},
	},
}
</script>
<style lang="scss" scoped>
	.textarea-mission {
		width: 67%;
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
