import { generateUrl } from '@nextcloud/router'
import { createRouter, createWebHistory } from 'vue-router'
import GoalsView from './views/GoalsView.vue'
import JournalView from './views/JournalView.vue'
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
