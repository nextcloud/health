/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import assert from 'node:assert/strict'
import { test } from 'node:test'

import { normalizeWeightInput } from '../../src/utils/weightInput.ts'

test('normalizes Weight decimal point and comma input without truncation', () => {
	assert.equal(normalizeWeightInput('82,4'), 82.4)
	assert.equal(normalizeWeightInput('82.4'), 82.4)
	assert.equal(normalizeWeightInput(' 82,4 '), 82.4)
	assert.equal(normalizeWeightInput('82'), 82)
})

test('rejects malformed Weight input rather than coercing it to another value', () => {
	for (const input of ['82,,4', '82..4', 'abc', '82,4.5']) {
		assert.equal(normalizeWeightInput(input), null)
	}
})
