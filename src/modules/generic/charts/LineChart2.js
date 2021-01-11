import { Line } from 'vue-chartjs'
import { mapGetters, mapState } from 'vuex'
import moment from '@nextcloud/moment'

export default {
	extends: Line,
	props: {
		options: {
			type: Object,
			default: null,
		},
		contextFilter: {
			type: Object,
			default: null,
		},
		setDefinitions: {
			type: Array,
			default: null,
		},
		range: {
			type: String,
			default: 'month',
		},
	},
	mounted() {
		this.renderChart(this.chartData, this.options)
	},
	watch: {
		datasets: function() {
			this.renderChart(this.chartData, this.options)
		},
		range: function() {
			this.renderChart(this.chartData, this.options)
		},
	},
	computed: {
		...mapState(['activePersonId', 'moduleSettings', 'moduleData']),
		...mapGetters(['person']),
		datasets: function() {
			if (this.contextFilter && this.moduleData[this.contextFilter.module] && this.moduleData[this.contextFilter.module][this.contextFilter.type]) {
				return this.moduleData[this.contextFilter.module][this.contextFilter.type]
			} else {
				return []
			}
		},
		chartData: function() {
			if (!this.setDefinitions || !this.datasets) {
				return null
			}

			const data = {}
			this.datasets.forEach(item => {
				const d = JSON.parse(item.data)
				this.setDefinitions.forEach(set => {
					if (!data[set.columnId]) {
						data[set.columnId] = []
					}
					if (d[set.timeId] && d[set.valueId] && this.isInTimeRange(d[set.timeId])) {
						data[set.columnId].push({
							t: moment(d[set.timeId]),
							y: set.getValueY(d[set.valueId]),
						})
					}
				})
			})

			const result = {
				datasets: [],
			}
			this.setDefinitions.forEach(set => {
				const push = {
					label: set.title,
					data: data[set.columnId],
				}
				if ('backgroundColor' in set) { push.backgroundColor = set.backgroundColor }
				if ('borderColor' in set) { push.borderColor = set.borderColor }
				if ('borderWidth' in set) { push.borderWidth = set.borderWidth }
				if ('fill' in set) { push.fill = set.fill }
				if ('type' in set) { push.type = set.type }

				result.datasets.push(push)
			})

			console.debug('chartData', result)
			return result
		},
		rangeDays: function() {
			if (this.range === 'week') {
				return 7
			} else if (this.range === 'month') {
				return 31
			} else if (this.range === 'year') {
				return 365
			} else {
				return -1
			}
		},
	},
	methods: {
		isInTimeRange: function(date) {
			if (this.rangeDays === -1) {
				return true
			}
			console.debug('isInTimeRange', {
				ohneAbs: moment(date).diff(moment(), 'days'),
				result: Math.abs(moment(date).diff(moment(), 'days')),
			})
			return Math.abs(moment(date).diff(moment(), 'days')) <= this.rangeDays
		},
	},
}
