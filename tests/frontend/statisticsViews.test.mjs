/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import assert from 'node:assert/strict'
import { test } from 'node:test'

import { savedStatisticsViewConfiguration, safeSavedStatisticsViewIcon, statisticsViewMode } from '../../src/statisticsViews.ts'

test('copies saved Statistics configuration without mutating its source', () => {
	const source = {
		id: 7,
		title: 'Source',
		icon: '📈',
		metricKeys: ['stress', 'energy'],
		period: 'last_30_days',
		createdAt: '2026-09-02T10:00:00Z',
		updatedAt: '2026-09-02T10:00:00Z',
	}

	const copy = savedStatisticsViewConfiguration(source)
	copy.metricKeys.push('mood')

	assert.deepEqual(source.metricKeys, ['stress', 'energy'])
	assert.deepEqual(copy.metricKeys, ['stress', 'energy', 'mood'])
	assert.equal(copy.period, source.period)
})

test('distinguishes read-only saved Statistics routes and safely displays invalid legacy icons', () => {
	assert.equal(statisticsViewMode('statistics'), 'main')
	assert.equal(statisticsViewMode('statistics-view'), 'saved')
	assert.equal(safeSavedStatisticsViewIcon('\u0000'), '📊')
	assert.equal(safeSavedStatisticsViewIcon('🧭'), '🧭')
})
