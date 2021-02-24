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
		<p v-if="sum > 0">
			{{ t('health', 'Great, you saved {sum} yet.', { sum: '' + sum + currency }) }}
		</p>
	</div>
</template>

<script>

export default {
	name: 'SmokingSavedMoney',
	props: {
		datasets: {
			type: Array,
			default: null,
		},
		price: {
			type: Number,
			default: null,
		},
		startValue: {
			type: Number,
			default: null,
		},
		currency: {
			type: String,
			default: '',
		},
	},
	computed: {
		sum() {
			if (!this.price || !this.startValue) {
				return null
			} else {
				let s = 0
				this.datasets.forEach(item => {
					if ('cigarettes' in item) {
						const count = this.startValue - item.cigarettes
						s = s + Math.round(count * this.price * 100) / 100
					}
				})
				return s
			}
		},
	},
}
</script>
