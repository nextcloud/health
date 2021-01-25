// import { mapGetters } from 'vuex'

export default {
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
	},
}
