import { Line, mixins } from 'vue-chartjs'
const { reactiveProp } = mixins

export default {
	extends: Line,
	mixins: [reactiveProp],
	props: ['options'],
	mounted() {
		// this.chartData is created in the mixin.
		// If you want to pass options please create a local options object
		// const timeFormat = 'DD/MM/YYYY'
		const options = {
			title: {
				text: 'Weight Chart',
			},
			scales: {
				xAxes: [
					{
						gridLines: {
							display: true,
							lineWidth: 1,
						},
						type: 'time',
						// time: {
						// parser: timeFormat,
						// round: 'day',
						// tooltipFormat: 'll DD.MM.',
						// unit: 'month',
						// },
						// scaleLabel: {
						// display: false,
						// labelString: 'Date',
						// },
					},
				],
				yAxes: [
					{
						gridLines: {
							display: true,
							lineWidth: 1,
						},
						scaleLabel: {
							display: true,
							labelString: 'weight',
						},
						ticks: {
							beginAtZero: true,
							min: 1,
							sampleSize: 10,
						},
					},
				],
			},
			tooltips: {
				enabled: true,
				position: 'nearest',
				mode: 'nearest',
				intersect: false,
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
