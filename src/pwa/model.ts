import type { AccountConfiguration, MetricDefinition, PendingOperation } from './types.ts'

import { getOptionSymbol, PWA_EVENT_QUICK_ACTIONS } from '../metrics.ts'

export type EntryControl = 'event' | 'counter' | 'scale' | 'numeric' | 'composite' | 'option'
export type QuickEntryMode = 'direct-options' | 'range-buttons' | 'direct-increment' | 'numeric-input' | 'composite-input'

export interface QuickEntryAction {
	id: string
	metric: MetricDefinition
	mode: 'metric' | 'immediate-option' | 'options'
	label: string | null
	optionKeys: string[] | null
	icon: 'metric' | 'option'
}

export function enabledMetricDefinitions(account: AccountConfiguration | null): MetricDefinition[] {
	return account?.metrics.filter((metric) => account.configuration[metric.metricKey]?.enabled === true) ?? []
}

/**
 * Derive PWA launchers from enabled server definitions and the shared event
 * option groups. No current values are included in this entry-only model.
 *
 * @param account Account configuration received from the Health API.
 */
export function quickEntryActions(account: AccountConfiguration | null): QuickEntryAction[] {
	return enabledMetricDefinitions(account).flatMap<QuickEntryAction>((metric) => {
		const groups = PWA_EVENT_QUICK_ACTIONS[metric.metricKey as keyof typeof PWA_EVENT_QUICK_ACTIONS]
		if (groups === undefined) {
			return [{ id: metric.metricKey, metric, mode: 'metric', label: null, optionKeys: null, icon: 'metric' }]
		}

		return groups.flatMap<QuickEntryAction>((group) => {
			const optionKeys = group.optionKeys.filter((option) => metric.allowedOptions?.includes(option) === true)
			if (optionKeys.length === 0) {
				return []
			}
			return [{ id: `${metric.metricKey}-${group.actionKey}`, metric, mode: group.mode, label: group.label, optionKeys, icon: group.icon }]
		})
	})
}

export function entryControl(metric: MetricDefinition): EntryControl {
	return metric.valueType
}

/**
 * Select the touch-first control from the server-owned metric definition.
 * A range is deliberately limited to a small set so arbitrary numbers are
 * never forced into preset buttons.
 *
 * @param metric Server-owned metric definition
 */
export function quickEntryMode(metric: MetricDefinition): QuickEntryMode {
	if (metric.valueType === 'event' && (metric.allowedOptions?.length ?? 0) > 0) {
		return 'direct-options'
	}
	if (metric.valueType === 'scale' && scaleChoices(metric).length > 0) {
		return 'range-buttons'
	}
	if (metric.valueType === 'counter') {
		return 'direct-increment'
	}
	return metric.valueType === 'composite' ? 'composite-input' : 'numeric-input'
}

export function scaleChoices(metric: MetricDefinition): number[] {
	if (metric.minimum === null || metric.maximum === null || !Number.isInteger(metric.minimum) || !Number.isInteger(metric.maximum)) {
		return []
	}
	const count = metric.maximum - metric.minimum + 1
	if (count < 1 || count > 10) {
		return []
	}
	return Array.from({ length: count }, (_, index) => metric.minimum! + index)
}

/**
 * Return the centrally-defined option symbol, or no icon for unknown options.
 *
 * @param metric Server-owned metric definition
 * @param option Event option identifier
 */
export function optionIcon(metric: MetricDefinition, option: string): string | null {
	const symbol = getOptionSymbol(metric.metricKey, option)
	return symbol === '•' ? null : symbol
}

/**
 * Parse a number the way the account locale writes it.
 *
 * Group separators are removed before the decimal separator is canonicalised,
 * so a grouped entry such as the German `12.000` is read as twelve thousand
 * instead of twelve. Whitespace is dropped because several locales group with
 * a no-break space that a keyboard does not produce.
 *
 * @param value Raw text entered by the user.
 * @param locale Account locale, or the browser locale as a fallback.
 */
export function parseLocaleNumber(value: string, locale: string): number {
	const parts = new Intl.NumberFormat(locale).formatToParts(11111.1)
	const decimal = parts.find((part) => part.type === 'decimal')?.value ?? '.'
	const group = parts.find((part) => part.type === 'group')?.value ?? ''
	const compact = value.replaceAll(/\s/gu, '')
	const ungrouped = group === '' ? compact : compact.replaceAll(group, '')
	const normalized = decimal === '.' ? ungrouped : ungrouped.replaceAll(decimal, '.')
	return normalized === '' ? Number.NaN : Number(normalized)
}

/**
 * Build the canonical PWA operation for a numeric metric from server-owned
 * metric metadata. Scale controls must not assume that every 1–5 metric is a
 * journal entry: Job Satisfaction is a daily value.
 *
 * @param metric Server-owned metric definition.
 * @param base Generated replay and creation metadata.
 * @param base.operationId Stable client replay identity.
 * @param base.createdAt Client creation timestamp.
 * @param base.state Pending operation state.
 * @param value Canonical numeric value selected by the user.
 * @param localDate User-local date for Daily Values.
 * @param recordedAt RFC3339 timestamp for journal and measurement records.
 * @param unit Selected display unit or canonical unit.
 */
export function numericOperation(
	metric: MetricDefinition,
	base: { operationId: string, createdAt: string, state: 'pending' },
	value: number,
	localDate: string,
	recordedAt: string,
	unit: string | null,
): PendingOperation {
	if (metric.category === 'journal') {
		return { ...base, metricKey: metric.metricKey, kind: 'journal', numericValue: value, optionValue: null, recordedAt }
	}
	if (metric.category === 'measurement') {
		return { ...base, metricKey: metric.metricKey, kind: 'measurement', numericValue: value, optionValue: null, values: null, unit, recordedAt, note: null }
	}
	return { ...base, metricKey: metric.metricKey, kind: 'daily_value', localDate, numericValue: value, unit }
}
