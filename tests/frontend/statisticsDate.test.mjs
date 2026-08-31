/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import assert from 'node:assert/strict'
import { after, test } from 'node:test'

import { getLocale, setLocale } from '@nextcloud/l10n'
import { formatStatisticsDate } from '../../src/statistics.ts'

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
