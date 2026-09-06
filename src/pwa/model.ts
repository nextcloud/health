import type { AccountConfiguration, MetricDefinition } from './types.ts'

import { getOptionSymbol } from '../metrics.ts'

export type EntryControl = 'event' | 'counter' | 'scale' | 'numeric' | 'composite'
export type QuickEntryMode = 'direct-options' | 'range-buttons' | 'direct-increment' | 'numeric-input' | 'composite-input'

export function enabledMetricDefinitions(account: AccountConfiguration | null): MetricDefinition[] {
	return account?.metrics.filter((metric) => account.configuration[metric.metricKey]?.enabled === true) ?? []
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

export function parseLocaleNumber(value: string, locale: string): number {
	const parts = new Intl.NumberFormat(locale).formatToParts(1.1)
	const decimal = parts.find((part) => part.type === 'decimal')?.value ?? '.'
	const normalized = decimal === '.' ? value : value.replace(decimal, '.')
	return Number(normalized.trim())
}
