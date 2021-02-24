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
		<div v-if="bmi">
			<div class="bmi" :class="statusClass(bmi(person, weight))">
				<div class="number" :class="statusClass(bmi(person, weight))">
					{{ bmi(person, weight) }}
				</div>
				{{ status(bmi(person, weight)) }}
				<div class="info">
					{{ t('health', 'Weight', {}) }} {{ weight + person.weightUnit }}
				</div>
				<div class="info">
					{{ t('health', 'Date', {}) }} {{ date | formatMyDate }}
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import MixinBmi from './MixinBmi'

export default {
	name: 'WeightBmi',
	filters: {
		formatMyDate(v) {
			return new Date(v).toLocaleDateString()
		},
	},
	mixins: [MixinBmi],
	props: {
		weight: {
			type: Number,
			default: null,
		},
		date: {
			type: String,
			default: null,
		},
		person: {
			type: Object,
			default: null,
		},
	},
}
</script>
<style lang="scss" scoped>
	.hint {
		font-size: 0.8em;
	}

	.bmi {
		background-color: var(--color-background-dark);
		border-radius: 10px;
		padding: 10px;
		// width: fit-content;
	}

	.bmi .info {
		font-size: 0.8em;
		font-weight: normal;
		color: gray;
	}

	.bmi .number {
		padding: 6px 12px 6px 12px;
		border-radius: 20px;
		width: fit-content;
		color: var(--color-primary-text);
		font-size: 1.5em;
		font-weight: 100;
	}

	.bmi.good {
		color: var(--color-success);
	}

	.bmi.okay {
		color: var(--color-primary-element);
	}

	.bmi.warn {
		color: var(--color-warning);
	}

	.bmi.alert {
		color: var(--color-error);
	}

	.bmi.danger {
		color: var(--color-error);
		font-weight: bolder;
	}

	.number.good {
		background-color: var(--color-success);
	}

	.number.okay {
		background-color: var(--color-primary-element);
	}

	.number.warn {
		background-color: var(--color-warning);
	}

	.number.alert {
		background-color: var(--color-error);
	}

	.number.danger {
		background-color: var(--color-error);
		font-weight: bolder;
	}
</style>
