/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import { test } from 'node:test'

test('keeps the Kilocalories total in the aggregate and reuses Journal detail separators', async () => {
	const source = await readFile(new URL('../../src/components/MeasurementsSection.vue', import.meta.url), 'utf8')
	assert.match(source, /<template #aggregate>/)
	assert.match(source, /hasSumAggregation\(metricKey\)/)
	assert.match(source, /Total: \{value\}/)
	assert.doesNotMatch(source, /measurements-section__total-row/)
	assert.match(source, /\.measurements-section__item \+ \.measurements-section__item \{ border-top: 1px solid var\(--health-journal-separator, var\(--color-border-dark\)\); \}/)
	assert.match(source, /<NcTextField v-model="note"/)
	assert.doesNotMatch(source, /NcTextArea/)
})

test('offers enabled Kilocalories in the shared Statistics configuration selector', async () => {
	const source = await readFile(new URL('../../src/components/statistics/StatisticsConfigurationFields.vue', import.meta.url), 'utf8')
	assert.match(source, /'blood_pressure', 'allergies', 'kilocalories'/)
	assert.match(source, /getEnabledMetricKeys\(props\.configuration, ALL_METRIC_KEYS\)/)
})

test('offers enabled Allergies in Journal Measurements and the shared Statistics selector', async () => {
	const [measurements, statistics] = await Promise.all([
		readFile(new URL('../../src/components/MeasurementsSection.vue', import.meta.url), 'utf8'),
		readFile(new URL('../../src/components/statistics/StatisticsConfigurationFields.vue', import.meta.url), 'utf8'),
	])
	assert.match(measurements, /activeMetricKey === 'allergies'/)
	assert.match(measurements, /getOptionLabel\('allergies', measurement\.optionValue\)/)
	assert.match(measurements, /getAllergySymptomGroupLabel/)
	assert.match(statistics, /'blood_pressure', 'allergies', 'kilocalories'/)
})
