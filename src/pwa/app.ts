/* eslint-disable @stylistic/max-statements-per-line */
import type { AllMetricKey } from '../metrics.ts'
import type { MetricDefinition, PendingOperation } from './types.ts'

import { getMetricVisual } from '../metrics.ts'
import { createDiagnostics, diagnosticsText } from './diagnostics.ts'
import { translate } from './i18n.ts'
import { randomUuid } from './identity.ts'
import { pollLogin, startLogin } from './loginFlow.ts'
import { enabledMetricDefinitions, optionIcon, parseLocaleNumber, quickEntryMode, scaleChoices } from './model.ts'
import { clearLocalData, getAccount, listOperations, putAccount, putOperation } from './storage.ts'
import { SyncCoordinator } from './sync.ts'
import { revoke } from './transport.ts'

import './styles.css'

type InstallPrompt = Event & { prompt: () => Promise<void>, userChoice: Promise<{ outcome: string }> }

function requireRoot(): HTMLElement {
	const root = document.querySelector<HTMLElement>('#health-pwa')
	if (root === null) { throw new Error('Health PWA root is missing.') }
	return root
}
const root = requireRoot()
const marker = '/apps/health/pwa/'
const markerIndex = location.pathname.indexOf(marker)
if (markerIndex < 0) { throw new Error('Health PWA route is invalid.') }
const prefix = location.pathname.slice(0, markerIndex)
const webroot = prefix.endsWith('/index.php') ? prefix.slice(0, -10) : prefix
const loginFlowPath = `${webroot}/index.php/login/v2`
const revokePath = `${webroot}/ocs/v2.php/core/apppassword`
let account = await getAccount()
let syncState: 'idle' | 'syncing' | 'synced' | 'offline' | 'expired' | 'failed' = 'idle'
let pending = (await listOperations()).length
let installPrompt: InstallPrompt | null = null
let loginAbort: AbortController | null = null
let lastSyncAt: string | null = null
const activeMetricActions = new Set<string>()
const coordinator = new SyncCoordinator(() => account, (state) => {
	syncState = state.status
	pending = state.pending
	if (state.status === 'synced') { lastSyncAt = new Date().toISOString() }
	render()
})

function s(message: string): string { return translate(message, account?.locale) }
function element<K extends keyof HTMLElementTagNameMap>(name: K, className?: string, text?: string): HTMLElementTagNameMap[K] {
	const node = document.createElement(name)
	if (className !== undefined) { node.className = className }
	if (text !== undefined) { node.textContent = text }
	return node
}
function button(label: string, action: () => void, className = ''): HTMLButtonElement {
	const node = element('button', className, label)
	node.type = 'button'
	node.addEventListener('click', action)
	return node
}
function metricLabel(key: string): string {
	const labels: Record<string, string> = { stress: 'Stress', energy: 'Energy', mood: 'Mood', hydration: 'Hydration', break: 'Break', temperature: 'Temperature', oxygen_saturation: 'Oxygen saturation', blood_glucose: 'Blood glucose', pulse: 'Pulse', blood_pressure: 'Blood pressure', weight: 'Weight', body_fat: 'Body fat', waist: 'Waist circumference', hip: 'Hip circumference', muscle_percentage: 'Muscle percentage', sins: 'Sins', steps: 'Steps', kilocalories: 'Kilocalories', fruit: 'Fruit', job_satisfaction: 'Job Satisfaction' }
	return s(labels[key] ?? key.replaceAll('_', ' '))
}
function optionLabel(option: string): string {
	const labels: Record<string, string> = { small_glass: 'Small glass', large_glass: 'Large glass', coffee: 'Coffee', cappuccino: 'Cappuccino', espresso: 'Espresso', double_espresso: 'Double espresso', latte_macchiato: 'Latte macchiato', cafe_au_lait: 'Café au lait', tea: 'Tea', other: 'Other', short: 'Short break', regular: 'Regular break', short_walk: 'Short walk', long_walk: 'Long walk', mindfulness: 'Mindfulness exercise', fresh_air: 'Air out & take a breath' }
	return s(labels[option] ?? option.replaceAll('_', ' '))
}
function localDate(): string {
	const parts = new Intl.DateTimeFormat('en-CA', { timeZone: account?.timezone, year: 'numeric', month: '2-digit', day: '2-digit' }).formatToParts()
	const value = (type: string) => parts.find((part) => part.type === type)?.value ?? ''
	return `${value('year')}-${value('month')}-${value('day')}`
}
function icon(metric: MetricDefinition): SVGSVGElement {
	const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg')
	svg.setAttribute('viewBox', '0 0 24 24')
	svg.setAttribute('aria-hidden', 'true')
	const path = document.createElementNS('http://www.w3.org/2000/svg', 'path')
	const visual = getMetricVisual(metric.metricKey as AllMetricKey)
	path.setAttribute('d', visual.iconPath)
	svg.style.color = visual.color
	svg.append(path)
	return svg
}
function enabledMetrics(): MetricDefinition[] {
	return enabledMetricDefinitions(account)
}

function appIcon(): HTMLElement {
	const picture = element('picture', 'app-icon')
	const dark = element('source')
	dark.media = '(prefers-color-scheme: dark)'
	dark.srcset = root.dataset.iconDarkUrl ?? ''
	const image = element('img')
	image.src = root.dataset.iconUrl ?? ''
	image.alt = ''
	picture.append(dark, image)
	return picture
}

function render(): void {
	root.replaceChildren()
	root.append(renderHeader())
	if (account === null) { root.append(renderSetup()); return }
	const connectionNotice = renderConnectionNotice()
	if (connectionNotice !== null) { root.append(connectionNotice) }
	const main = element('main')
	const metrics = enabledMetrics()
	if (metrics.length === 0) { main.append(element('p', 'empty', s('No metrics are enabled. Enable metrics in Health Settings, then synchronize.'))) }
	const grid = element('div', 'metric-grid')
	for (const metric of metrics) { grid.append(renderMetricLauncher(metric)) }
	main.append(grid)
	root.append(main)
}

function renderHeader(): HTMLElement {
	const header = element('header', 'header')
	const brand = element('div', 'brand')
	const heading = element('h1', undefined, s('Health'))
	brand.append(appIcon(), heading)
	header.append(brand)
	if (account === null) { return header }
	const actions = element('div', 'header-actions')
	const sync = button('', () => void coordinator.sync(), `sync-button sync-button-${syncState}`)
	const syncLabel = syncState === 'syncing' ? s('Synchronizing') : syncState === 'expired' ? s('Reconnect to Nextcloud') : syncState === 'failed' ? s('Synchronization error') : s('Synchronize')
	sync.disabled = syncState === 'syncing'
	sync.title = syncLabel
	sync.setAttribute('aria-label', pending === 0 ? syncLabel : `${syncLabel}, ${pending} ${s('entries waiting to sync')}`)
	sync.append(element('span', syncState === 'syncing' ? 'sync-icon sync-icon-syncing' : 'sync-icon', syncState === 'offline' ? '⊘' : syncState === 'expired' ? '↺' : syncState === 'failed' ? '!' : '⟳'))
	if (pending > 0) { sync.append(element('span', 'sync-count', String(pending))) }
	const menu = button('⋮', openDeviceMenu, 'icon-button')
	menu.title = s('Device actions')
	menu.setAttribute('aria-label', s('Device actions'))
	actions.append(sync, menu)
	header.append(actions)
	return header
}

function renderConnectionNotice(): HTMLElement | null {
	if (!navigator.onLine) {
		return notice(s('Offline — entries will sync later.'), false)
	}
	if (account?.authState === 'expired' || syncState === 'expired') {
		const status = notice(s('Reconnect to Nextcloud'), true)
		status.append(button(s('Reconnect'), () => void connect(), 'secondary'))
		return status
	}
	if (syncState === 'offline') {
		return notice(s('Server unavailable. Entries remain on this device.'), true)
	}
	if (syncState === 'failed') {
		return notice(s('Synchronization failed. Entries remain on this device.'), true)
	}
	return null
}

function notice(message: string, error = false): HTMLElement {
	const node = element('section', error ? 'notice notice-error' : 'notice', message)
	node.setAttribute('role', error ? 'alert' : 'status')
	return node
}

function renderSetup(): HTMLElement {
	const section = element('main', 'setup')
	section.append(element('h2', undefined, s('Connect to Nextcloud')), element('p', undefined, s('Authorize this device in Nextcloud. Your normal password is never stored here.')), button(s('Connect'), () => void connect(), 'primary'))
	return section
}

function renderMetricLauncher(metric: MetricDefinition): HTMLButtonElement {
	const launcher = button('', () => openEntryModal(metric, launcher), 'metric-launcher')
	launcher.dataset.metricKey = metric.metricKey
	launcher.setAttribute('aria-label', s('Record {metric}').replace('{metric}', metricLabel(metric.metricKey)))
	launcher.append(icon(metric), element('span', 'metric-launcher__label', metricLabel(metric.metricKey)))
	return launcher
}

function openEntryModal(metric: MetricDefinition, trigger: HTMLElement): void {
	const dialog = element('dialog', 'entry-dialog')
	const content = element('div', 'entry-dialog__content')
	const heading = element('h2', 'entry-dialog__heading')
	heading.append(icon(metric), element('span', undefined, metricLabel(metric.metricKey)))
	const error = element('p', 'entry-dialog__error')
	error.hidden = true
	content.append(heading, error)
	const showError = (): void => {
		error.textContent = s('Could not save. The entry remains queued.')
		error.hidden = false
	}
	const closeAfterSuccess = (): void => dialog.close()
	const action = (control: HTMLButtonElement, operation: () => Promise<void>): void => {
		void completeImmediateAction(metric, control, operation, closeAfterSuccess, showError)
	}
	const mode = quickEntryMode(metric)
	if (mode === 'direct-options') {
		const options = element('div', 'option-grid')
		for (const option of metric.allowedOptions ?? []) {
			const label = optionLabel(option)
			const optionButton = button('', () => action(optionButton, () => queueJournal(metric, null, option)), 'option-button')
			optionButton.setAttribute('aria-label', `${metricLabel(metric.metricKey)}: ${label}`)
			const optionSymbol = optionIcon(metric, option)
			if (optionSymbol !== null) { optionButton.append(element('span', 'option-icon', optionSymbol)) }
			optionButton.append(element('span', undefined, label))
			options.append(optionButton)
		}
		content.append(options, modalCancel(dialog))
	} else if (mode === 'range-buttons') {
		const choices = element('div', 'option-grid option-grid--range')
		for (const value of scaleChoices(metric)) {
			const choice = button(String(value), () => action(choice, () => queueJournal(metric, value, null)), 'option-button option-button--number')
			choice.setAttribute('aria-label', `${metricLabel(metric.metricKey)}: ${value}`)
			choices.append(choice)
		}
		content.append(choices, modalCancel(dialog))
	} else if (mode === 'direct-increment') {
		const input = numericInput(s('Value'), 'numeric', metric.minimum ?? undefined, metric.maximum ?? undefined)
		const form = numericForm(metric, input, error, closeAfterSuccess, dialog)
		const increment = button('+1', () => action(increment, () => queueIncrement(metric)), 'metric-action primary')
		increment.setAttribute('aria-label', s('Add one {metric}').replace('{metric}', metricLabel(metric.metricKey)))
		content.append(form, increment)
	} else if (mode === 'composite-input') {
		const systolic = numericInput(s('Systolic'), 'decimal')
		const diastolic = numericInput(s('Diastolic'), 'decimal')
		const form = element('form', 'entry-dialog__form')
		form.append(systolic.label, diastolic.label, formActions(dialog))
		form.addEventListener('submit', (event) => {
			event.preventDefault()
			const values = { systolic: parseLocaleNumber(systolic.input.value, account?.locale ?? navigator.language), diastolic: parseLocaleNumber(diastolic.input.value, account?.locale ?? navigator.language) }
			if (!Number.isFinite(values.systolic) || !Number.isFinite(values.diastolic)) { showError(); return }
			void saveForm(metric, form, () => queueMeasurement(metric, null, values), closeAfterSuccess, showError)
		})
		content.append(form)
	} else {
		const input = numericInput(s('Value'), 'decimal', metric.minimum ?? undefined, metric.maximum ?? undefined)
		content.append(numericForm(metric, input, error, closeAfterSuccess, dialog))
	}
	dialog.append(content)
	showDialog(dialog, trigger, metric.metricKey)
	const focus = dialog.querySelector<HTMLElement>('input, button')
	focus?.focus()
}

function numericForm(metric: MetricDefinition, input: { label: HTMLLabelElement, input: HTMLInputElement }, error: HTMLElement, closeAfterSuccess: () => void, dialog: HTMLDialogElement): HTMLFormElement {
	const form = element('form', 'entry-dialog__form')
	form.append(input.label, formActions(dialog))
	form.addEventListener('submit', (event) => {
		event.preventDefault()
		const value = parseLocaleNumber(input.input.value, account?.locale ?? navigator.language)
		if (!isValidNumericValue(metric, value)) {
			error.textContent = s('Enter a valid value.')
			error.hidden = false
			return
		}
		void saveForm(metric, form, () => queueNumeric(metric, value), closeAfterSuccess, () => {
			error.textContent = s('Could not save. The entry remains queued.')
			error.hidden = false
		})
	})
	return form
}

function modalCancel(dialog: HTMLDialogElement): HTMLElement {
	const actions = element('div', 'dialog-actions')
	actions.append(button(s('Cancel'), () => dialog.close()))
	return actions
}

function formActions(dialog?: HTMLDialogElement): HTMLElement {
	const actions = element('div', 'dialog-actions')
	if (dialog !== undefined) { actions.append(button(s('Cancel'), () => dialog.close())) }
	actions.append(Object.assign(element('button', 'primary', s('Save')), { type: 'submit' }))
	return actions
}

function isValidNumericValue(metric: MetricDefinition, value: number): boolean {
	if (!Number.isFinite(value) || (metric.valueType === 'counter' && (!Number.isInteger(value) || value < 0))) {
		return false
	}
	return (metric.minimum === null || value >= metric.minimum) && (metric.maximum === null || value <= metric.maximum)
}

async function completeImmediateAction(metric: MetricDefinition, control: HTMLButtonElement, operation: () => Promise<void>, close: () => void, reportError: () => void): Promise<void> {
	if (activeMetricActions.has(metric.metricKey)) { return }
	activeMetricActions.add(metric.metricKey)
	control.disabled = true
	try {
		await operation()
		close()
	} catch {
		control.disabled = false
		reportError()
	} finally {
		activeMetricActions.delete(metric.metricKey)
	}
}

async function saveForm(metric: MetricDefinition, form: HTMLFormElement, operation: () => Promise<void>, close: () => void, reportError: () => void): Promise<void> {
	const controls = form.querySelectorAll<HTMLButtonElement>('button')
	for (const control of controls) { control.disabled = true }
	try {
		await operation()
		close()
	} catch {
		for (const control of controls) { control.disabled = false }
		reportError()
	}
}

function numericInput(labelText: string, inputMode: 'numeric' | 'decimal', minimum?: number, maximum?: number): { label: HTMLLabelElement, input: HTMLInputElement } {
	const label = element('label', undefined, labelText)
	const input = element('input')
	input.type = 'number'
	input.required = true
	input.inputMode = inputMode
	input.step = inputMode === 'numeric' ? '1' : 'any'
	if (minimum !== undefined) { input.min = String(minimum) }
	if (maximum !== undefined) { input.max = String(maximum) }
	label.append(input)
	return { label, input }
}

async function enqueue(operation: PendingOperation): Promise<void> {
	await putOperation(operation)
	pending = (await listOperations()).length
	showToast(navigator.onLine ? s('Saved') : s('Queued for sync'))
	if (navigator.onLine) { void coordinator.sync() }
}
function base(metric: MetricDefinition): { operationId: string, metricKey: string, createdAt: string, state: 'pending' } { return { operationId: randomUuid(), metricKey: metric.metricKey, createdAt: new Date().toISOString(), state: 'pending' } }
function queueJournal(metric: MetricDefinition, numericValue: number | null, optionValue: string | null): Promise<void> { return enqueue({ ...base(metric), kind: 'journal', numericValue, optionValue, recordedAt: new Date().toISOString() }) }
function queueMeasurement(metric: MetricDefinition, numericValue: number | null, values: { systolic: number, diastolic: number } | null): Promise<void> { return enqueue({ ...base(metric), kind: 'measurement', numericValue, values, unit: account?.configuration[metric.metricKey]?.displayUnit ?? metric.canonicalUnit, recordedAt: new Date().toISOString() }) }
function queueIncrement(metric: MetricDefinition): Promise<void> { return enqueue({ ...base(metric), kind: 'daily_increment', localDate: localDate(), delta: 1, unit: metric.canonicalUnit, preparedNumericValue: null }) }
function queueNumeric(metric: MetricDefinition, value: number): Promise<void> {
	if (!Number.isFinite(value) || (metric.valueType === 'counter' && (!Number.isInteger(value) || value < 0))) { return Promise.resolve() }
	if (metric.category === 'journal') { return queueJournal(metric, value, null) }
	if (metric.category === 'measurement') { return queueMeasurement(metric, value, null) }
	return enqueue({ ...base(metric), kind: 'daily_value', localDate: localDate(), numericValue: value, unit: account?.configuration[metric.metricKey]?.displayUnit ?? metric.canonicalUnit })
}

async function connect(): Promise<void> {
	loginAbort?.abort()
	loginAbort = new AbortController()
	try {
		const start = await startLogin(loginFlowPath)
		window.open(start.login, '_blank', 'noopener,noreferrer')
		account = await pollLogin(start, account ?? undefined, loginAbort.signal)
		await putAccount(account)
		render()
		void coordinator.sync()
	} catch { showToast(s('Could not connect to Nextcloud.'), true) }
}

function showDialog(dialog: HTMLDialogElement, trigger?: HTMLElement, metricKey?: string): void {
	document.body.append(dialog)
	dialog.addEventListener('close', () => {
		dialog.remove()
		if (metricKey !== undefined) {
			render()
			window.requestAnimationFrame(() => root.querySelector<HTMLButtonElement>(`[data-metric-key="${metricKey}"]`)?.focus())
			return
		}
		trigger?.focus()
	}, { once: true })
	dialog.showModal()
}

function openDeviceMenu(): void {
	const dialog = element('dialog', 'device-dialog')
	dialog.append(element('h2', undefined, s('Device actions')))
	const actions = element('div', 'device-actions')
	actions.append(
		button(s('Show diagnostics'), () => { dialog.close(); showDiagnostics() }),
		button(s('Disconnect'), () => { dialog.close(); confirmDisconnect() }, 'danger'),
		button(s('Cancel'), () => dialog.close()),
	)
	if (installPrompt !== null) { actions.prepend(button(s('Install'), () => { dialog.close(); void install() }, 'primary')) }
	dialog.append(actions)
	showDialog(dialog)
}

function showDiagnostics(): void {
	const origin = account === null ? null : new URL(account.serverUrl).origin
	const diagnostic = createDiagnostics({
		buildVersion: root.dataset.healthPwaBuild ?? 'unknown',
		serverOrigin: origin,
		online: navigator.onLine,
		authentication: account?.authState ?? 'disconnected',
		synchronization: syncState,
		lastSyncAt,
		pendingOperations: pending,
		serviceWorker: navigator.serviceWorker?.controller === null ? 'unavailable' : 'active',
	})
	const dialog = element('dialog', 'diagnostics-dialog')
	dialog.append(element('h2', undefined, s('Technical diagnostics')))
	const output = element('pre', 'diagnostics-output', diagnosticsText(diagnostic))
	output.tabIndex = 0
	dialog.append(output)
	const actions = element('div', 'dialog-actions')
	actions.append(button(s('Copy diagnostics'), () => void copyDiagnostics(diagnosticsText(diagnostic))), button(s('Close'), () => dialog.close()))
	dialog.append(actions)
	showDialog(dialog)
}

async function copyDiagnostics(content: string): Promise<void> {
	try {
		await navigator.clipboard.writeText(content)
		showToast(s('Diagnostics copied'))
	} catch {
		showToast(s('Diagnostics could not be copied.'), true)
	}
}

function confirmDisconnect(): void {
	const dialog = element('dialog')
	dialog.append(element('h2', undefined, s('Disconnect this device')))
	dialog.append(element('p', undefined, s('Disconnecting removes local connection credentials and pending entries from this device.')))
	const actions = element('div', 'dialog-actions')
	actions.append(button(s('Cancel'), () => dialog.close()), button(s('Disconnect'), () => { dialog.close(); void disconnect() }, 'danger'))
	dialog.append(actions)
	showDialog(dialog)
}

async function disconnect(): Promise<void> {
	if (account !== null) {
		try { await revoke(account, revokePath) } catch { /* Local data is explicitly removed after confirmed disconnect. */ }
	}
	await clearLocalData()
	account = null
	pending = 0
	lastSyncAt = null
	syncState = 'idle'
	render()
}
async function install(): Promise<void> { await installPrompt?.prompt(); await installPrompt?.userChoice; installPrompt = null; render() }
function showToast(message: string, error = false): void { const toast = notice(message, error); toast.classList.add('toast'); document.body.append(toast); window.setTimeout(() => toast.remove(), 3500) }

window.addEventListener('beforeinstallprompt', (event) => { event.preventDefault(); installPrompt = event as InstallPrompt; render() })
window.addEventListener('online', () => void coordinator.sync())
window.addEventListener('offline', render)
document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') { void coordinator.sync() } })
if ('serviceWorker' in navigator) { void navigator.serviceWorker.register(new URL('service-worker.js', location.href), { scope: new URL('.', location.href).pathname, type: 'module' }) }
render()
if (new URLSearchParams(location.search).has('disconnect') && account !== null) { void disconnect() }
if (account !== null) { void coordinator.sync() }
