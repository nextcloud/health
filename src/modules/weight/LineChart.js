/**
 * @copyright Copyright (c) 2020 Florian Steffens <flost-dev@mailbox.org>
 *
 * @author Florian Steffens <flost-dev@mailbox.org>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

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
				text: t('health', 'Weight Chart'),
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
							labelString: t('health', 'weight'),
						},
					},
					{
						id: 'percent',
						position: 'right',
						gridLines: {
							display: false,
							// lineWidth: 1,
							// drawOnChartArea: true,
							// drawTicks: true,
						},
						scaleLabel: {
							display: true,
							labelString: t('health', 'Custom values'),
						},
						ticks: {
							// sampleSize: 10,
							// beginAtZero: true,
						},
					},
				],
			},
			tooltips: {
				enabled: true,
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
