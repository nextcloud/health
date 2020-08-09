import { Line, mixins } from 'vue-chartjs'
const { reactiveProp } = mixins

export default {
	extends: Line,
	mixins: [reactiveProp],
	props: ['options'],
	mounted() {
		// this.chartData is created in the mixin.
		// If you want to pass options please create a local options object
		const timeFormat = 'DD/MM/YYYY'
		const options = {
			title: {
				text: 'Chart.js Time Scale',
			},
			scales: {
				xAxes: [
					{
						type: 'time',
						time: {
							parser: timeFormat,
							// round: 'day'
							// tooltipFormat: 'll HH:mm',
							// unit: 'month',
						},
						scaleLabel: {
							display: false,
							// labelString: 'Date',
						},
					},
				],
				yAxes: [
					{
						scaleLabel: {
							display: true,
							labelString: 'weight',
						},
					},
				],
			},
			tooltips: {
				enabled: false,
			},
			layout: {
			   padding: {
					right: 20,
				},
			},
		}
		this.renderChart(this.chartData, options)
	},
}
