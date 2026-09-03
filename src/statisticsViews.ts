import type { SavedStatisticsView, StatisticsViewConfiguration } from './api/statisticsViews.ts'

export const DEFAULT_SAVED_STATISTICS_VIEW_ICON = '📊'

export type StatisticsViewMode = 'main' | 'saved'

export function savedStatisticsViewConfiguration(view: Pick<SavedStatisticsView, 'metricKeys' | 'period'>): StatisticsViewConfiguration {
	return {
		metricKeys: [...view.metricKeys],
		period: view.period,
	}
}

export function safeSavedStatisticsViewIcon(icon: unknown): string {
	if (typeof icon !== 'string' || icon.trim() === '' || /\p{Cc}/u.test(icon)) {
		return DEFAULT_SAVED_STATISTICS_VIEW_ICON
	}

	return icon
}

export function statisticsViewMode(routeName: unknown): StatisticsViewMode {
	return routeName === 'statistics-view' ? 'saved' : 'main'
}
