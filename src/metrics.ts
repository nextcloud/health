import {
	mdiArmFlex,
	mdiArrowExpandHorizontal,
	mdiBloodBag,
	mdiBriefcase,
	mdiEmoticonHappy,
	mdiFootPrint,
	mdiGauge,
	mdiHeartPulse,
	mdiLightningBolt,
	mdiLungs,
	mdiPause,
	mdiPulse,
	mdiTapeMeasure,
	mdiThermometer,
	mdiThumbDown,
	mdiWater,
	mdiWaterPercent,
	mdiWeight,
} from '@mdi/js'
import { t } from '@nextcloud/l10n'

export const METRIC_KEYS = ['stress', 'energy', 'mood', 'hydration', 'break'] as const
export const MEASUREMENT_METRIC_KEYS = ['temperature', 'oxygen_saturation', 'blood_glucose', 'pulse', 'blood_pressure'] as const
export const DAILY_VALUE_METRIC_KEYS = ['weight', 'body_fat', 'waist', 'hip', 'muscle_percentage', 'sins', 'steps', 'job_satisfaction'] as const
export const ALL_METRIC_KEYS = [...METRIC_KEYS, ...MEASUREMENT_METRIC_KEYS, ...DAILY_VALUE_METRIC_KEYS] as const
export const SCALE_METRIC_KEYS = ['stress', 'energy', 'mood'] as const
export const WATER_OPTIONS = ['small_glass', 'large_glass'] as const
export const COFFEE_OPTIONS = ['coffee', 'cappuccino', 'espresso', 'double_espresso', 'latte_macchiato', 'cafe_au_lait'] as const
export const TEA_OPTIONS = ['tea'] as const
export const BEVERAGE_OPTIONS = [...COFFEE_OPTIONS, ...TEA_OPTIONS] as const
export const HYDRATION_OPTIONS = [...WATER_OPTIONS, ...COFFEE_OPTIONS, ...TEA_OPTIONS, 'other'] as const
export const BREAK_OPTIONS = ['short', 'regular', 'short_walk', 'long_walk', 'mindfulness', 'fresh_air'] as const

export type MetricKey = typeof METRIC_KEYS[number]
export type MeasurementMetricKey = typeof MEASUREMENT_METRIC_KEYS[number]
export type DailyValueMetricKey = typeof DAILY_VALUE_METRIC_KEYS[number]
export type AllMetricKey = typeof ALL_METRIC_KEYS[number]
export type Unit = 'cm' | 'in' | 'kg' | 'lb' | 'st' | 'percent' | 'count' | 'steps' | 'celsius' | 'fahrenheit' | 'mmol_l' | 'mg_dl' | 'bpm' | 'mmhg' | 'kpa'
export type ScaleMetricKey = typeof SCALE_METRIC_KEYS[number]
export type EventMetricKey = 'hydration' | 'break'
export type CoffeeOption = typeof COFFEE_OPTIONS[number]
export type TeaOption = typeof TEA_OPTIONS[number]
export type BeverageOption = CoffeeOption | TeaOption
export type BreakOption = typeof BREAK_OPTIONS[number]
export type EventOption = typeof HYDRATION_OPTIONS[number] | typeof BREAK_OPTIONS[number]

export interface MetricValue {
	numericValue: number | null
	optionValue: EventOption | null
}

export interface MetricVisual {
	color: string
	iconPath: string
}

export type StatisticsChartType = 'line' | 'stacked-bar'
export type StatisticsCompatibilityGroup = 'scale_1_5' | 'length' | 'blood_pressure' | 'individual'

export interface ChartableMetricDefinition {
	chartType: StatisticsChartType
	compatibilityGroup: StatisticsCompatibilityGroup
}

export interface EventChartSeriesDefinition {
	key: string
	color: string
}

const METRIC_VISUALS: Record<AllMetricKey, MetricVisual> = {
	stress: { color: '#D96C0B', iconPath: mdiPulse },
	energy: { color: '#9A7200', iconPath: mdiLightningBolt },
	mood: { color: '#2E8B57', iconPath: mdiEmoticonHappy },
	hydration: { color: '#168BD2', iconPath: mdiWater },
	break: { color: '#167D8C', iconPath: mdiPause },
	temperature: { color: '#E85D5D', iconPath: mdiThermometer },
	oxygen_saturation: { color: '#4F8CC9', iconPath: mdiLungs },
	blood_glucose: { color: '#D32F2F', iconPath: mdiBloodBag },
	pulse: { color: '#D32F2F', iconPath: mdiHeartPulse },
	blood_pressure: { color: '#7B5AA6', iconPath: mdiGauge },
	weight: { color: '#546E7A', iconPath: mdiWeight },
	body_fat: { color: '#8D6E63', iconPath: mdiWaterPercent },
	waist: { color: '#5C6BC0', iconPath: mdiTapeMeasure },
	hip: { color: '#7E57C2', iconPath: mdiArrowExpandHorizontal },
	muscle_percentage: { color: '#388E3C', iconPath: mdiArmFlex },
	sins: { color: '#8E6C4A', iconPath: mdiThumbDown },
	steps: { color: '#39796B', iconPath: mdiFootPrint },
	job_satisfaction: { color: '#5C6BC0', iconPath: mdiBriefcase },
}

export function getMetricVisual(metricKey: AllMetricKey): MetricVisual {
	return METRIC_VISUALS[metricKey]
}

const CHARTABLE_METRICS: Record<AllMetricKey, ChartableMetricDefinition> = {
	stress: { chartType: 'line', compatibilityGroup: 'scale_1_5' },
	energy: { chartType: 'line', compatibilityGroup: 'scale_1_5' },
	mood: { chartType: 'line', compatibilityGroup: 'scale_1_5' },
	hydration: { chartType: 'stacked-bar', compatibilityGroup: 'individual' },
	break: { chartType: 'stacked-bar', compatibilityGroup: 'individual' },
	temperature: { chartType: 'line', compatibilityGroup: 'individual' },
	oxygen_saturation: { chartType: 'line', compatibilityGroup: 'individual' },
	blood_glucose: { chartType: 'line', compatibilityGroup: 'individual' },
	pulse: { chartType: 'line', compatibilityGroup: 'individual' },
	blood_pressure: { chartType: 'line', compatibilityGroup: 'blood_pressure' },
	weight: { chartType: 'line', compatibilityGroup: 'individual' },
	body_fat: { chartType: 'line', compatibilityGroup: 'individual' },
	waist: { chartType: 'line', compatibilityGroup: 'length' },
	hip: { chartType: 'line', compatibilityGroup: 'length' },
	muscle_percentage: { chartType: 'line', compatibilityGroup: 'individual' },
	sins: { chartType: 'line', compatibilityGroup: 'individual' },
	steps: { chartType: 'line', compatibilityGroup: 'individual' },
	job_satisfaction: { chartType: 'line', compatibilityGroup: 'scale_1_5' },
}

export function colorWithAlpha(color: string, alpha: number): string {
	const red = Number.parseInt(color.slice(1, 3), 16)
	const green = Number.parseInt(color.slice(3, 5), 16)
	const blue = Number.parseInt(color.slice(5, 7), 16)
	return `rgba(${red}, ${green}, ${blue}, ${alpha})`
}

const EVENT_CHART_SERIES: Record<EventMetricKey, readonly EventChartSeriesDefinition[]> = {
	hydration: [
		{ key: 'water', color: METRIC_VISUALS.hydration.color },
		{ key: 'coffee', color: colorWithAlpha(METRIC_VISUALS.hydration.color, 0.78) },
		{ key: 'tea', color: colorWithAlpha(METRIC_VISUALS.hydration.color, 0.58) },
		{ key: 'other', color: colorWithAlpha(METRIC_VISUALS.hydration.color, 0.38) },
	],
	break: BREAK_OPTIONS.map((optionValue, index) => ({
		key: optionValue,
		color: index === 0
			? METRIC_VISUALS.break.color
			: colorWithAlpha(METRIC_VISUALS.break.color, 0.82 - index * 0.1),
	})),
}

export function getChartableMetricDefinition(metricKey: AllMetricKey): ChartableMetricDefinition {
	return CHARTABLE_METRICS[metricKey]
}

export function getEventChartSeries(metricKey: EventMetricKey): readonly EventChartSeriesDefinition[] {
	return EVENT_CHART_SERIES[metricKey]
}

export function isScaleMetric(metricKey: MetricKey): metricKey is ScaleMetricKey {
	return (SCALE_METRIC_KEYS as readonly MetricKey[]).includes(metricKey)
}

export function getEventOptions(metricKey: EventMetricKey): readonly EventOption[] {
	return metricKey === 'hydration' ? HYDRATION_OPTIONS : BREAK_OPTIONS
}

export function isCoffeeOption(optionValue: string | null): optionValue is CoffeeOption {
	return optionValue !== null && (COFFEE_OPTIONS as readonly string[]).includes(optionValue)
}

export function isTeaOption(optionValue: string | null): optionValue is TeaOption {
	return optionValue !== null && (TEA_OPTIONS as readonly string[]).includes(optionValue)
}

export function getMetricLabel(metricKey: string): string {
	switch (metricKey) {
		case 'stress':
			return t('health', 'Stress')
		case 'energy':
			return t('health', 'Energy')
		case 'mood':
			return t('health', 'Mood')
		case 'hydration':
			return t('health', 'Hydration')
		case 'break':
			return t('health', 'Break')
		case 'temperature':
			return t('health', 'Temperature')
		case 'oxygen_saturation':
			return t('health', 'Oxygen saturation')
		case 'blood_glucose':
			return t('health', 'Blood glucose')
		case 'pulse':
			return t('health', 'Pulse')
		case 'blood_pressure':
			return t('health', 'Blood pressure')
		case 'weight':
			return t('health', 'Weight')
		case 'body_fat':
			return t('health', 'Body fat')
		case 'waist':
			return t('health', 'Waist circumference')
		case 'hip':
			return t('health', 'Hip circumference')
		case 'muscle_percentage':
			return t('health', 'Muscle percentage')
		case 'sins':
			return t('health', 'Sins')
		case 'steps':
			return t('health', 'Steps')
		case 'job_satisfaction':
			return t('health', 'Job Satisfaction')
		default:
			return t('health', 'Journal entry')
	}
}

export function hasDisplayUnit(metricKey: AllMetricKey): boolean {
	return metricKey !== 'sins' && metricKey !== 'steps' && metricKey !== 'job_satisfaction'
}

export function getUnitLabel(unit: Unit): string {
	return ({ cm: 'cm', in: 'in', kg: 'kg', lb: 'lb', st: 'st', percent: '%', count: t('health', 'count'), steps: t('health', 'steps'), celsius: '°C', fahrenheit: '°F', mmol_l: 'mmol/L', mg_dl: 'mg/dL', bpm: 'bpm', mmhg: 'mmHg', kpa: 'kPa' })[unit]
}

export function getMetricUnits(metricKey: AllMetricKey): readonly Unit[] {
	return ({ stress: [], energy: [], mood: [], hydration: [], break: [], temperature: ['celsius', 'fahrenheit'], oxygen_saturation: ['percent'], blood_glucose: ['mmol_l', 'mg_dl'], pulse: ['bpm'], blood_pressure: ['mmhg', 'kpa'], weight: ['kg', 'lb', 'st'], body_fat: ['percent'], waist: ['cm', 'in'], hip: ['cm', 'in'], muscle_percentage: ['percent'], sins: ['count'], steps: ['steps'], job_satisfaction: [] })[metricKey] as readonly Unit[]
}

export function fromCanonical(metricKey: AllMetricKey, value: number, unit: Unit): number {
	if (metricKey === 'weight') {
		return unit === 'lb' ? value * 2.20462262 : unit === 'st' ? value / 6.35029318 : value
	}
	if (metricKey === 'waist' || metricKey === 'hip') {
		return unit === 'in' ? value / 2.54 : value
	}
	if (metricKey === 'temperature') {
		return unit === 'fahrenheit' ? value * 9 / 5 + 32 : value
	}
	if (metricKey === 'blood_glucose') {
		return unit === 'mg_dl' ? value * 18.0182 : value
	}
	if (metricKey === 'blood_pressure') {
		return unit === 'kpa' ? value * 0.133322 : value
	}
	return value
}

export function getScaleQuestion(metricKey: MetricKey): string {
	switch (metricKey) {
		case 'stress':
			return t('health', 'How stressed do you feel?')
		case 'energy':
			return t('health', 'How is your energy?')
		case 'mood':
			return t('health', 'How is your mood?')
		default:
			return t('health', 'Choose a value')
	}
}

export function getOptionLabel(metricKey: string, optionValue: string | null): string {
	if (metricKey === 'hydration') {
		switch (optionValue) {
			case 'small_glass':
				return t('health', 'Small glass')
			case 'large_glass':
				return t('health', 'Large glass')
			case 'coffee':
				return t('health', 'Coffee')
			case 'cappuccino':
				return t('health', 'Cappuccino')
			case 'espresso':
				return t('health', 'Espresso')
			case 'double_espresso':
				return t('health', 'Double espresso')
			case 'latte_macchiato':
				return t('health', 'Latte macchiato')
			case 'cafe_au_lait':
				return t('health', 'Café au lait')
			case 'tea':
				return t('health', 'Tea')
			case 'other':
				return t('health', 'Other')
		}
	}

	if (metricKey === 'break') {
		switch (optionValue) {
			case 'short':
				return t('health', 'Short break')
			case 'regular':
				return t('health', 'Regular break')
			case 'short_walk':
				return t('health', 'Short walk')
			case 'long_walk':
				return t('health', 'Long walk')
			case 'mindfulness':
				return t('health', 'Mindfulness exercise')
			case 'fresh_air':
				return t('health', 'Air out & take a breath')
		}
	}

	return t('health', 'Recorded event')
}

export function getEventChartSeriesLabel(metricKey: EventMetricKey, seriesKey: string): string {
	if (metricKey === 'hydration') {
		switch (seriesKey) {
			case 'water':
				return t('health', 'Water')
			case 'coffee':
				return t('health', 'Coffee')
			case 'tea':
				return t('health', 'Tea')
			case 'other':
				return t('health', 'Other')
		}
	}

	return getOptionLabel(metricKey, seriesKey)
}

export function getOptionSymbol(metricKey: string, optionValue: string | null): string {
	if (metricKey === 'hydration') {
		if (isCoffeeOption(optionValue)) {
			return '☕️'
		}
		if (isTeaOption(optionValue)) {
			return '🫖'
		}
		return '🥛'
	}

	if (metricKey === 'break') {
		switch (optionValue) {
			case 'short':
				return '⏱️'
			case 'regular':
				return '⏸️'
			case 'short_walk':
				return '🚶'
			case 'long_walk':
				return '🥾'
			case 'mindfulness':
				return '🧘'
			case 'fresh_air':
				return '🌬️'
		}
	}

	return '•'
}

export function getSourceLabel(source: string): string {
	switch (source) {
		case 'web':
			return t('health', 'Web')
		case 'api':
			return t('health', 'API')
		case 'mobile':
			return t('health', 'Mobile')
		case 'notification':
			return t('health', 'Notification')
		default:
			return t('health', 'Unknown source')
	}
}
