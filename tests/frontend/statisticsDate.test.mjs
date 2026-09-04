/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import assert from 'node:assert/strict'
import { after, test } from 'node:test'

import { getLocale, setLocale } from '@nextcloud/l10n'
import { createStatisticsGoalChartDataset, formatStatisticsDate, getPaddedStatisticsScaleRange, getStatisticsGoalChartSeriesValues } from '../../src/statistics.ts'

const originalLocale = getLocale()

after(() => {
	setLocale(originalLocale)
})

test('formats Statistics chart dates for Nextcloud locales with underscores', () => {
	setLocale('de_DE')

	assert.equal(
		formatStatisticsDate('2026-08-31'),
		new Intl.DateTimeFormat('de-DE', { month: 'short', day: 'numeric' }).format(new Date('2026-08-31T12:00:00')),
	)
})

test('adds rounded visual padding to Statistics chart ranges', () => {
	assert.deepEqual(getPaddedStatisticsScaleRange([1, 2, 3]), { minimum: 0, maximum: 4 })
	assert.deepEqual(getPaddedStatisticsScaleRange([70, 80]), { minimum: 60, maximum: 90 })
	assert.deepEqual(getPaddedStatisticsScaleRange([75]), { minimum: 60, maximum: 90 })
})

test('keeps Statistics chart ranges non-negative and usable for zero and decimals', () => {
	assert.deepEqual(getPaddedStatisticsScaleRange([0]), { minimum: 0, maximum: 1 })
	assert.deepEqual(getPaddedStatisticsScaleRange([0.21, 0.23]), { minimum: 0.2, maximum: 0.24 })
	assert.deepEqual(getPaddedStatisticsScaleRange([]), { minimum: 0, maximum: 1 })
})

test('builds drawable, category-aligned chart datasets for open-ended and revised Statistics goals', () => {
	const dates = ['2026-08-01', '2026-08-15', '2026-08-16', '2026-08-31']
	const goal = (effectiveFrom, effectiveTo, targetValue, metricKey = 'weight') => ({
		goalId: 1,
		targetKey: metricKey,
		metricKey,
		kind: 'latest_value',
		seriesKey: 'value',
		comparator: 'lte',
		targetValue,
		options: null,
		effectiveFrom,
		effectiveTo,
	})

	const openEndedSegment = getStatisticsGoalChartSeriesValues('weight', goal('2026-01-01', null, 75), dates)
	assert.equal(openEndedSegment[0], 75)
	assert.equal(openEndedSegment.at(-1), 75)
	assert.deepEqual(
		openEndedSegment,
		[75, 75, 75, 75],
	)
	assert.deepEqual(
		getStatisticsGoalChartSeriesValues('weight', goal('2026-08-16', null, 75), dates),
		[null, null, 75, 75],
	)
	assert.deepEqual(
		getStatisticsGoalChartSeriesValues('weight', goal('2026-01-01', '2026-08-15', 80), dates),
		[80, 80, null, null],
	)
	assert.deepEqual(
		getStatisticsGoalChartSeriesValues('weight', goal('2026-01-01', null, 10, 'steps'), dates),
		[null, null, null, null],
	)

	const dataset = createStatisticsGoalChartDataset({
		label: 'Weight target',
		data: openEndedSegment,
		color: '#546E7A',
		yAxisID: 'metric-weight',
		stack: 'goal:1:value:2026-01-01',
	})
	assert.deepEqual(dataset, {
		label: 'Weight target',
		data: [75, 75, 75, 75],
		color: '#546E7A',
		yAxisID: 'metric-weight',
		stack: 'goal:1:value:2026-01-01',
		type: 'line',
		parsing: true,
		xAxisID: 'x',
		backgroundColor: 'transparent',
		borderColor: '#546E7A',
		borderWidth: 2,
		borderDash: [6, 4],
		fill: false,
		pointRadius: 0,
		pointHoverRadius: 0,
		pointStyle: 'circle',
		stepped: false,
		spanGaps: false,
		showLine: true,
		hidden: false,
		order: -1,
	})
})
