import { Line } from 'vue-chartjs'

export default {
	extends: Line,
	props: {
		chartdata: {
			type: Object,
			default: null,
		},
		options: {
			type: Object,
			default: null,
		},
	},
	mounted() {
		// test
		this.renderChart(this.chartdata, this.options)
	},
}
