import { mapGetters } from 'vuex'

export default {
	components: {
		...mapGetters(['person']),
	},
	methods: {
		bmi(person, weight) {
			if (person.size === null || person.age === null || weight === null) {
				return null
			}
			const s = person.size / 100
			const bmi = weight / (s * s)
			// console.debug('bmi: ' + bmi)
			return Math.round(bmi)
		},
		status(bmi) {
			if (bmi === null) {
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
		statusClass(bmi) {
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
		bodyfat(weight, age, sex) {
			if (!weight || !age || !sex) {
				return null
			}

			const bmi = this.bmi(this.person, weight)
			const sexNumber = sex === 'female' ? 0 : 1
			if (age <= 15) {
				return (1.51 * bmi) - (0.7 * age) - (3.6 * sexNumber) + 1.4
			} else {
				return (1.39 * bmi) + (0.16 * age) - (10.34 * sexNumber) - 9
			}
		},
	},
}
