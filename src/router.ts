import { generateUrl } from '@nextcloud/router'
import { createRouter, createWebHistory } from 'vue-router'
import GoalsView from './views/GoalsView.vue'
import JournalView from './views/JournalView.vue'
import { SAVED_STATISTICS_VIEW_ROUTE_PATH } from './statisticsViews.ts'
import { localDateKey } from './utils/dates.ts'

const StatisticsView = () => import('./views/StatisticsView.vue')

function todayRoute() {
	return {
		name: 'journal',
		params: { date: localDateKey(new Date()) },
	}
}

const router = createRouter({
	history: createWebHistory(generateUrl('/apps/health/')),
	routes: [
		{
			path: '/',
			redirect: todayRoute,
		},
		{
			path: '/journal/:date',
			name: 'journal',
			component: JournalView,
			props: true,
		},
		{
			path: '/goals',
			name: 'goals',
			component: GoalsView,
		},
		{
			path: '/statistics',
			name: 'statistics',
			component: StatisticsView,
		},
		{
			path: SAVED_STATISTICS_VIEW_ROUTE_PATH,
			name: 'statistics-view',
			component: StatisticsView,
			props: true,
		},
		{
			path: '/donate',
			name: 'donate',
			component: JournalView,
			props: () => ({ date: localDateKey(new Date()) }),
		},
		{
			path: '/settings',
			redirect: todayRoute,
		},
		{
			path: '/:pathMatch(.*)*',
			redirect: todayRoute,
		},
	],
})

export default router
