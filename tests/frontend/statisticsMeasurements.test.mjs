/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import { test } from 'node:test'

test('Statistics renders translated, grouped source measurements below its summary', async () => {
	const [view, measurements] = await Promise.all([
		readFile(new URL('../../src/views/StatisticsView.vue', import.meta.url), 'utf8'),
		readFile(new URL('../../src/components/statistics/StatisticsMeasurements.vue', import.meta.url), 'utf8'),
	])
	assert.match(view, /<StatisticsMeasurements/)
	assert.match(view, /:records="response\.sourceRecords"/)
	assert.match(measurements, /t\('health', 'Measurements'\)/)
	assert.match(measurements, /t\('health', 'No measurements in this period\.'\)/)
	assert.match(measurements, /getOptionLabel\(record\.metricKey, record\.optionValue\)/)
	assert.match(measurements, /v-for="group in groups"/)
	assert.match(measurements, /grid-template-columns: max-content minmax\(0, 1fr\) max-content/)
	assert.match(measurements, /<strong>\{\{ getMetricLabel\(record\.metricKey\) \}\}<\/strong>\s*<span class="statistics-measurements__value">\{\{ formatValue\(record\) \}\}<\/span>\s*<time v-if="record\.recordedAt !== null"/)
	assert.match(measurements, /@media \(max-width: 360px\)/)
	assert.match(measurements, /border-bottom: 1px solid var\(--health-journal-separator, var\(--color-border-dark\)\)/)
	assert.doesNotMatch(measurements, /note/)
})
