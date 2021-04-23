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
	<div>
		<p v-if="initialWeight">
			{{ t('health', 'You started with {initialWeight}{unit} for your target.', transValues) }}
		</p>
		<p v-if="weight && target">
			{{ t('health', 'Your actual weight is now {weight}{unit} and your target values {target}{unit}.', transValues) }}
		</p>
		<p v-if="weight && !target">
			{{ t('health', 'Your actual weight is now {weight}{unit}.', transValues) }}
		</p>
		<p v-if="weight < target && weight >= initialWeight">
			{{ t('health', 'You gained already {diff}{unit} and you have {toGo}{unit} to go.', transValues) }}
			<br>
			<br>
			{{ t('health', 'Go on and eliminate the blue bar:', {}) }}
			<ProgressBar
				:value="getProgressbarValue"
				:class="{'small':true}" />
		</p>
		<p v-if="weight < initialWeight" class="alert red">
			{{ t('health', 'Oops, you are losing more and more. Be careful!', {}) }}
		</p>
		<p v-if="weight >= target" class="green alert">
			{{ t('health', 'Good, you reached your target!', {}) }}
		</p>
		<p v-if="weight >= target && bounty" class="bountyBox green">
			{{ bounty }}
		</p>
	</div>
</template>

<script>
import ProgressBar from '@nextcloud/vue/dist/Components/ProgressBar'
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'TextResultsGainWeight',
	components: {
		ProgressBar,
	},
	props: {
		weight: {
			type: Number,
			default: null,
		},
		target: {
			type: Number,
			default: null,
		},
		initialWeight: {
			type: Number,
			default: null,
		},
		unit: {
			type: String,
			default: null,
		},
		bounty: {
			type: String,
			default: '',
		},
	},
	data() {
		return {
		}
	},
	computed: {
		...mapState(['activeModule', 'showSidebar', 'weightData']),
		...mapGetters(['person']),
		getProgressbarValue() {
			if (!this.weight || !this.initialWeight) {
				return null
			}
			const wantToAdd = this.target - this.initialWeight
			const result = Math.round((100 - ((this.diff / wantToAdd)) * 100) * 100) / 100
			return (result > 100) ? 100 : result
		},
		diff() {
			return Math.round((this.weight - this.initialWeight) * 100) / 100
		},
		toGo() {
			return Math.round((this.target - this.weight) * 100) / 100
		},
		transValues() {
			return {
				unit: this.unit,
				weight: this.weight,
				diff: this.diff,
				toGo: this.toGo,
				initialWeight: this.initialWeight,
				target: this.target,
			}
		},
	},
}
</script>
<style lang="scss" scoped>
	.progress-bar.small {
		width: 35%;
		height: 6px !important;
	}

	.alert {
		font-weight: 500;
		padding-left: 20px;
		padding-right: 20px;
		padding-top: 20px;
	}

	.bountyBox {
		padding: 10px;
		border: 1px solid #80808073;
		margin: 20px;
		width: fit-content;
	}
</style>
