/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import { test } from 'node:test'

import { isShellNavigation, isStaticAsset } from '../../src/pwa/cachePolicy.ts'
import { createDiagnostics, diagnosticsText } from '../../src/pwa/diagnostics.ts'
import { enabledMetricDefinitions, entryControl, optionIcon, parseLocaleNumber, quickEntryMode, scaleChoices } from '../../src/pwa/model.ts'
import { ApiError } from '../../src/pwa/transport.ts'
import { processQueue } from '../../src/pwa/sync.ts'

const definition = (metricKey, category, valueType, overrides = {}) => ({ metricKey, category, valueType, minimum: null, maximum: null, allowedOptions: null, canonicalUnit: null, supportedUnits: [], ...overrides })
const baseOperation = { operationId: '747e4696-2be2-4a64-9c32-fbdc21e55778', metricKey: 'stress', createdAt: '2026-09-05T12:00:00Z', state: 'pending', kind: 'journal', numericValue: 3, optionValue: null, recordedAt: '2026-09-05T12:00:00Z' }

test('loads only enabled metric definitions and selects a control from API value types', () => {
	const metrics = [definition('stress', 'journal', 'scale'), definition('hydration', 'journal', 'event'), definition('fruit', 'daily_value', 'counter'), definition('weight', 'daily_value', 'numeric'), definition('blood_pressure', 'measurement', 'composite')]
	const account = { metrics, configuration: { stress: { enabled: true }, hydration: { enabled: false }, fruit: { enabled: true }, weight: { enabled: true }, blood_pressure: { enabled: true } } }
	assert.deepEqual(enabledMetricDefinitions(account).map(({ metricKey }) => metricKey), ['stress', 'fruit', 'weight', 'blood_pressure'])
	assert.deepEqual(metrics.map(entryControl), ['scale', 'event', 'counter', 'numeric', 'composite'])
	assert.equal(parseLocaleNumber('2140,5', 'de-DE'), 2140.5)
})

test('maps server metric types to dialog entry modes without metric-key special cases', () => {
	const stress = definition('stress', 'journal', 'scale', { minimum: 1, maximum: 5 })
	const hydration = definition('hydration', 'journal', 'event', { allowedOptions: ['small_glass', 'coffee', 'tea'] })
	const unknownEvent = definition('break', 'journal', 'event', { allowedOptions: ['unrecognized'] })
	const fruit = definition('fruit', 'daily_value', 'counter')
	const weight = definition('weight', 'daily_value', 'numeric')
	assert.deepEqual(scaleChoices(stress), [1, 2, 3, 4, 5])
	assert.equal(quickEntryMode(stress), 'range-buttons')
	assert.equal(quickEntryMode(hydration), 'direct-options')
	assert.equal(quickEntryMode(fruit), 'direct-increment')
	assert.equal(quickEntryMode(weight), 'numeric-input')
	assert.equal(optionIcon(hydration, 'coffee'), '☕️')
	assert.equal(optionIcon(unknownEvent, 'unrecognized'), null)
})

test('removes queued operations only after a confirmed send and retains a failed operation for retry', async () => {
	const events = []
	await processQueue([baseOperation], {
		prepare: async (operation) => operation,
		markSending: async () => events.push('sending'),
		send: async () => events.push('sent'),
		remove: async () => events.push('removed'),
	})
	assert.deepEqual(events, ['sending', 'sent', 'removed'])

	const failed = []
	await assert.rejects(processQueue([baseOperation], {
		prepare: async (operation) => operation,
		markSending: async () => failed.push('sending'),
		send: async () => { failed.push('failed'); throw new ApiError('unreachable', 'offline') },
		remove: async () => failed.push('removed'),
	}), (error) => error instanceof ApiError && error.kind === 'unreachable')
	assert.deepEqual(failed, ['sending', 'failed'])
})

test('service-worker policy caches the static shell only and excludes API responses', () => {
	const origin = 'https://cloud.example.test'
	const scope = '/apps/health/pwa/'
	const shell = `${origin}${scope}`
	const asset = `${origin}/apps/health/js/health-pwa.mjs?v=1`
	assert.equal(isShellNavigation({ method: 'GET', mode: 'navigate', url: shell }, scope, origin), true)
	assert.equal(isShellNavigation({ method: 'GET', mode: 'navigate', url: `${origin}/ocs/v2.php/apps/health/api/v2/daily-values` }, scope, origin), false)
	assert.equal(isStaticAsset(new Request(asset), [shell, asset]), true)
	assert.equal(isStaticAsset(new Request(`${origin}/ocs/v2.php/apps/health/api/v2/daily-values`), [shell, asset]), false)
})

test('PWA remains capture-only and does not render history, statistics, current values, or goals', async () => {
	const source = await readFile(new URL('../../src/pwa/app.ts', import.meta.url), 'utf8')
	for (const forbidden of ['goals/progress', 'statistics?', 'history', 'current Weight']) assert.equal(source.includes(forbidden), false)
})

test('PWA home uses one equal-sized metric launcher pattern and opens Taskbook-style entry dialogs', async () => {
	const source = await readFile(new URL('../../src/pwa/app.ts', import.meta.url), 'utf8')
	const styles = await readFile(new URL('../../src/pwa/styles.css', import.meta.url), 'utf8')
	const shell = await readFile(new URL('../../templates/pwa.php', import.meta.url), 'utf8')
	assert.match(source, /renderMetricLauncher/)
	assert.match(source, /openEntryModal\(metric, launcher\)/)
	assert.match(source, /'metric-launcher'/)
	assert.match(source, /dataset\.metricKey/)
	assert.match(styles, /grid-auto-rows:9rem/)
	assert.match(styles, /\.metric-launcher \{ display:flex; width:100%; height:100%/)
	assert.match(source, /entry-dialog__heading/)
	assert.match(source, /showDialog\(dialog, trigger, metric\.metricKey\)/)
	assert.match(source, /brand\.append\(appIcon\(\), heading\)/)
	assert.match(source, /iconDarkUrl/)
	assert.match(shell, /data-icon-dark-url/)
	assert.match(source, /sync-icon/)
	assert.match(source, /openDeviceMenu/)
	assert.match(source, /Show diagnostics/)
	assert.match(source, /Disconnect/)
	assert.match(source, /option-grid/)
	assert.doesNotMatch(source, /renderMetric\(metric\)/)
	assert.doesNotMatch(source, /element\('select'/)
	assert.doesNotMatch(source, /Choose an option/)
})

test('PWA dialog action matrix keeps Save flows explicit and closes only after durable immediate actions', async () => {
	const source = await readFile(new URL('../../src/pwa/app.ts', import.meta.url), 'utf8')
	assert.match(source, /mode === 'direct-increment'/)
	assert.match(source, /queueIncrement\(metric\)/)
	assert.match(source, /numericForm\(metric, input, error, closeAfterSuccess, dialog\)/)
	assert.match(source, /mode === 'direct-options'/)
	assert.match(source, /mode === 'range-buttons'/)
	assert.match(source, /mode === 'composite-input'/)
	assert.match(source, /await operation\(\)\n\t\tclose\(\)/)
	assert.match(source, /catch \{\n\t\tcontrol\.disabled = false\n\t\treportError\(\)/)
	assert.match(source, /activeMetricActions\.has\(metric\.metricKey\)/)
	assert.match(source, /requestAnimationFrame\(\(\) => root\.querySelector/)
})

test('connection states distinguish normal offline queueing from synchronization failures', async () => {
	const source = await readFile(new URL('../../src/pwa/app.ts', import.meta.url), 'utf8')
	assert.match(source, /Offline — entries will sync later\./)
	assert.match(source, /Server unavailable\. Entries remain on this device\./)
	assert.match(source, /Synchronization failed\. Entries remain on this device\./)
})

test('diagnostics are a technical allow-list and cannot contain health or authentication secrets', () => {
	const output = diagnosticsText(createDiagnostics({
		buildVersion: 'build-1',
		serverOrigin: 'https://cloud.example.test',
		online: true,
		authentication: 'connected',
		synchronization: 'synced',
		lastSyncAt: '2026-09-05T12:00:00Z',
		pendingOperations: 3,
		serviceWorker: 'active',
	}))
	for (const forbidden of ['Weight', '90', 'journal', 'password', 'token', 'Authorization', 'Cookie', 'secret']) assert.equal(output.includes(forbidden), false)
	assert.match(output, /"serverOrigin"/)
	assert.match(output, /"pendingOperations"/)
})

test('Journal Weight progress uses the shared clamped progress-bar model', async () => {
	const source = await readFile(new URL('../../src/components/DailyValues.vue', import.meta.url), 'utf8')
	const goalSource = await readFile(new URL('../../src/goals.ts', import.meta.url), 'utf8')
	assert.match(source, /period === 'long_term'/)
	assert.match(source, /NcProgressBar/)
	assert.match(source, /progress toward the long-term goal/)
	assert.match(goalSource, /goalProgressPercentage/)
	assert.match(goalSource, /Math\.max\(0, Math\.min\(1/)
})
