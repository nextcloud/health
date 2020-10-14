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
	<div>
		<h3>
			{{ t('health', 'Body mass index (BMI)') }}
		</h3>
		<div v-if="bmi">
			<div class="bmi" :class="statusClass">
				<div class="number" :class="statusClass">
					{{ bmi }}
				</div>
				{{ status }}
			</div>
			<p class="hint">
				{{ t('health', 'The calculated value is valid only for adults. Its base are the tables from the WHO.') }}
			</p>
		</div>
		<p v-if="!bmi">
			{{ t('health', 'To calculate your BMI, please set your weight in the table below and you age and size in the person settings.') }}
		</p>
	</div>
</template>

<script>
import moment from '@nextcloud/moment'

export default {
	name: 'WeightBmi',
	filters: {
		formatMyDate: function(v) {
			return moment(v).format('DD.MM.YYYY')
		},
	},
	props: {
		size: {
			type: Number,
			default: null,
		},
		age: {
			type: Number,
			default: null,
		},
		weight: {
			type: Number,
			default: null,
		},
	},
	computed: {
		bmi: function() {
			if (this.size === null || this.age === null || this.weight === null) {
				return null
			}
			const s = this.size / 100
			const bmi = this.weight / (s * s)
			console.debug('bmi: ' + bmi)
			return Math.round(bmi)
		},
		status: function() {
			const bmi = this.bmi
			if (this.bmi === null) {
				return null
			}

			if (bmi < 18.5) {
				return t('health', 'Underweight')
			} else if (bmi >= 18.5 && bmi < 25) {
				return t('health', 'Normal weight')
			} else if (bmi >= 25 && bmi < 30) {
				return t('health', 'Pre-obesity')
			} else if (bmi >= 30 && bmi < 35) {
				return t('health', 'Obesity class I')
			} else if (bmi >= 35 && bmi < 40) {
				return t('health', 'Obesity class II')
			} else {
				return t('health', 'Obesity class III')
			}
		},
		statusClass: function() {
			const bmi = this.bmi
			const data = {
				okay: false,
				good: false,
				warning: false,
				alert: false,
				danger: false,
			}

			if (bmi < 18.5) {
				data.okay = true
			} else if (bmi >= 18.5 && bmi < 25) {
				data.good = true
			} else if (bmi >= 25 && bmi < 30) {
				data.okay = true
			} else if (bmi >= 30 && bmi < 35) {
				data.warn = true
			} else if (bmi >= 35 && bmi < 40) {
				data.alert = true
			} else {
				data.danger = true
			}
			return data
		},
	},
	methods: {
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
	width: fit-content;
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
