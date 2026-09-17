/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import { test } from 'node:test'

test('main UI and offline PWA route visible strings through their localization helpers', async () => {
	const [routine, measurements, pwa, catalog] = await Promise.all([
		readFile(new URL('../../src/components/RoutineDialog.vue', import.meta.url), 'utf8'),
		readFile(new URL('../../src/components/MeasurementsSection.vue', import.meta.url), 'utf8'),
		readFile(new URL('../../src/pwa/app.ts', import.meta.url), 'utf8'),
		readFile(new URL('../../src/pwa/i18n.ts', import.meta.url), 'utf8'),
	])
	assert.match(routine, /import \{ t \} from '@nextcloud\/l10n'/)
	assert.match(measurements, /t\('health', 'Total: \{value\}'/)
	assert.match(measurements, /<NcTextField v-model="note"/)
	assert.doesNotMatch(measurements, /NcTextArea/)
	assert.match(pwa, /function s\(message: string\): string \{ return translatePwa\(message, message, account\?\.locale\) \}/)
	assert.match(pwa, /return s\(labels\[key\]/)
	assert.match(pwa, /return s\(labels\[option\]/)
	assert.match(catalog, /Water: 'Wasser'/)
	assert.match(catalog, /Standalone catalogs are bundled so the PWA never needs runtime l10n globals/)
})
