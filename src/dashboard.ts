import { createApp } from 'vue'
import HealthDashboardWidget from './components/dashboard/HealthDashboardWidget.vue'

import '@nextcloud/dialogs/style.css'

type DashboardRegistration = {
	register: (widgetId: string, mount: (element: HTMLElement) => void | Promise<void>) => void
}

function registerDashboardWidget() {
	const dashboard = (window as Window & { OCA?: { Dashboard?: DashboardRegistration } }).OCA?.Dashboard
	if (dashboard === undefined) {
		return
	}

	dashboard.register('health', (element) => {
		createApp(HealthDashboardWidget).mount(element)
	})
}

document.addEventListener('DOMContentLoaded', registerDashboardWidget)
