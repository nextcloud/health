import { Line, mixins } from 'vue-chartjs'
const { reactiveProp } = mixins

export default {
	extends: Line,
	mixins: [reactiveProp],
	props: {
		options: {
			type: Object,
			default: null,
		},
	},
	mounted() {
		this.renderChart(this.chartData, this.options)
	},
	// eslint-disable-next-line no-tabs
/*	watch: {
		options: function() {
			console.debug('change chart options', this.options)
			this.$data._chart.destroy()
			this.renderChart(this.chartData, this.options)
		},
	}, */
}
