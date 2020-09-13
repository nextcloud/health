import { Line, mixins } from 'vue-chartjs'
const { reactiveProp } = mixins

export default {
	extends: Line,
	mixins: [reactiveProp],
	props: ['options', 'range'],
	mounted() {
		// this.chartData is created in the mixin.
		// If you want to pass options please create a local options object
		// const timeFormat = 'DD/MM/YYYY'
		const options = {
			title: {
				text: 'Weight Chart',
			},
			responsive: true,
			scales: {
				xAxes: [
					{
						gridLines: {
							// display: true,
							// color: 'gray',
							// z: 100,
							// lineWidth: 1,
							// drawOnChartArea: true,
							// drawTicks: true,
						},
						type: 'time',
						time: {
						// parser: timeFormat,
						// round: 'day',
						// tooltipFormat: 'll DD.MM.',
							minUnit: 'day',
						// stepsize: 50,
						},
						// scaleLabel: {
						// display: false,
						// labelString: 'Date',
						// },
						ticks: {
							// stepSize: 7,
						},
					},
				],
				yAxes: [
					{
						id: 'weight',
						gridLines: {
							display: true,
							color: 'gray',
							z: 100,
							lineWidth: 1,
							// drawOnChartArea: true,
							// drawTicks: true,
							// tickMarkLength: 100,
							// offsetGridLines: true,
						},
						scaleLabel: {
							display: true,
							labelString: 'weight',
						},
					},
					{
						id: 'percent',
						position: 'right',
						gridLines: {
							// display: true,
							// lineWidth: 1,
							// drawOnChartArea: true,
							// drawTicks: true,
						},
						scaleLabel: {
							display: true,
							labelString: 'Custom values',
						},
						ticks: {
							// sampleSize: 10,
							// beginAtZero: true,
						},
					},
				],
			},
			tooltips: {
				enabled: false,
				// position: 'nearest',
				// mode: 'nearest',
				// intersect: false,
			},
			layout: {
				padding: {
					right: 20,
					left: 20,
				},
			},
		}
		this.renderChart(this.chartData, options)
	},
	computed: {
		getTicksUnit: function() {
			return (this.range === 'year') ? 'month' : 'day'
		},
	},
}
