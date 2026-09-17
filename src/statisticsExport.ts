import type { HealthConfiguration } from './api/configuration.ts'
import type { StatisticsResponse, StatisticsSourceRecord } from './api/statistics.ts'

import { getCanonicalLocale, t } from '@nextcloud/l10n'
import { fromCanonical, getMetricLabel, getMetricUnits, getOptionLabel, getUnitLabel } from './metrics.ts'
import { displayUnitForMetric } from './statistics.ts'

export const PDF_TEXT_STYLES = {
	title: { size: 20, style: 'bold' },
	section: { size: 15, style: 'bold' },
	group: { size: 11, style: 'bold' },
	body: { size: 10, style: 'normal' },
	measurement: { size: 8.5, style: 'normal' },
} as const

function value(record: StatisticsSourceRecord, configuration: HealthConfiguration | null): [string, string] {
	if (record.optionValue !== null) {
		return [getOptionLabel(record.metricKey, record.optionValue), '']
	}
	const unit = displayUnitForMetric(configuration, record.metricKey) ?? getMetricUnits(record.metricKey)[0] ?? null
	if (record.values !== null) {
		return [`${record.values.systolic} / ${record.values.diastolic}`, unit === null ? '' : getUnitLabel(unit)]
	}
	const numeric = record.numericValue ?? 0
	return [String(unit === null ? numeric : fromCanonical(record.metricKey, numeric, unit)), unit === null ? '' : getUnitLabel(unit)]
}

function filename(title: string, response: StatisticsResponse, extension: string): string {
	const safe = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
	return `health-statistics${safe === 'statistics' || safe === '' ? '' : `-${safe}`}-${response.from}-${response.to}.${extension}`
}

function download(content: Blob, name: string): void {
	const url = URL.createObjectURL(content)
	const anchor = document.createElement('a')
	anchor.href = url
	anchor.download = name
	anchor.click()
	URL.revokeObjectURL(url)
}

export function exportCsv(title: string, response: StatisticsResponse, configuration: HealthConfiguration | null): void {
	const rows = [[t('health', 'Date'), t('health', 'Time'), t('health', 'Metric'), t('health', 'Value'), t('health', 'Unit')], ...response.sourceRecords.map((record) => {
		const [displayValue, unit] = value(record, configuration)
		return [record.date, record.recordedAt === null ? '' : new Intl.DateTimeFormat(getCanonicalLocale(), { hour: '2-digit', minute: '2-digit', hour12: false }).format(new Date(record.recordedAt)), getMetricLabel(record.metricKey), displayValue, unit]
	})]
	const csv = rows.map((row) => row.map((cell) => `"${cell.replace(/"/g, '""')}"`).join(',')).join('\r\n')
	download(new Blob([`\ufeff${csv}`], { type: 'text/csv;charset=utf-8' }), filename(title, response, 'csv'))
}

export async function exportPdf(title: string, response: StatisticsResponse, configuration: HealthConfiguration | null, chart: string | null): Promise<void> {
	const { jsPDF } = await import('jspdf')
	const pdf = new jsPDF()
	let y = 18
	type PdfStyle = keyof typeof PDF_TEXT_STYLES
	const line = (text: string, style: PdfStyle, gap = 5): void => {
		const textStyle = PDF_TEXT_STYLES[style]
		if (y > 278 - textStyle.size / 2) {
			pdf.addPage()
			y = 18
		}
		pdf.setFont('helvetica', textStyle.style)
		pdf.setFontSize(textStyle.size)
		pdf.setTextColor(30, 30, 30)
		pdf.text(text, 14, y)
		y += textStyle.size / 2 + gap
	}
	line(title, 'title', 9)
	line(t('health', 'Period'), 'group', 2)
	line(`${response.from} – ${response.to}`, 'body', 5)
	line(t('health', 'Metrics'), 'group', 2)
	line(response.metrics.map((metric) => getMetricLabel(metric.metricKey)).join(' · '), 'body', 9)
	if (chart !== null) {
		pdf.addImage(chart, 'PNG', 14, y, 180, 80)
		y += 88
	}
	line(t('health', 'Average values'), 'section', 5)
	for (const metric of response.metrics) {
		const unit = displayUnitForMetric(configuration, metric.metricKey) ?? getMetricUnits(metric.metricKey)[0] ?? null
		const average = metric.summary.average === null ? '—' : String(unit === null ? metric.summary.average : fromCanonical(metric.metricKey, metric.summary.average, unit))
		line(`${getMetricLabel(metric.metricKey)}    ${average}${unit === null ? '' : ` ${getUnitLabel(unit)}`}`, 'body', 2)
	}
	y += 4
	line(t('health', 'Measurements'), 'section', 5)
	let date: string | null = null
	for (const record of response.sourceRecords) {
		if (date !== record.date) {
			date = record.date
			line(new Intl.DateTimeFormat(getCanonicalLocale(), { dateStyle: 'long' }).format(new Date(`${date}T12:00:00`)), 'group', 3)
		}
		const [displayValue, unit] = value(record, configuration)
		line(`${getMetricLabel(record.metricKey)}    ${displayValue}${unit === '' ? '' : ` ${unit}`}${record.recordedAt === null ? '' : `    ${new Intl.DateTimeFormat(getCanonicalLocale(), { hour: '2-digit', minute: '2-digit' }).format(new Date(record.recordedAt))}`}`, 'measurement', 2)
	}
	pdf.save(filename(title, response, 'pdf'))
}
