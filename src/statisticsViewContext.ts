import type { InjectionKey, Ref } from 'vue'
import type { SavedStatisticsView, StatisticsViewConfiguration } from './api/statisticsViews.ts'

export interface SavedStatisticsViewsContext {
	views: Ref<SavedStatisticsView[]>
	upsert: (view: SavedStatisticsView) => void
	remove: (id: number) => void
	openCreate: (configuration: StatisticsViewConfiguration) => void
	openEdit: (view: SavedStatisticsView) => void
	openClone: (view: SavedStatisticsView) => void
	openDelete: (view: SavedStatisticsView) => void
}

export const savedStatisticsViewsKey: InjectionKey<SavedStatisticsViewsContext> = Symbol('savedStatisticsViews')
