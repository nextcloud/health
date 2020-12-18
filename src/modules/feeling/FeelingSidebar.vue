<!--
	- @copyright Copyright (c) 2020 Florian Steffens <flost-dev@mailbox.org>
	-
	- @author Florian Steffens
	-
	- @license GNU AGPL version 3 or any later version
	-
	- This program is free software: you can redistribute it and/or modify
	- it under the terms of the GNU Affero General Public License as
	- published by the Free Software Foundation, either version 3 of the
	- License, or (at your option) any later version.
	-
	- This program is distributed in the hope that it will be useful,
	- but WITHOUT ANY WARRANTY; without even the implied warranty of
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
	-->

<template>
	<ul>
		<li><h3>{{ t('health', 'Select columns', {}) }}</h3></li>
		<SidebarSelectColumns :context-filter="contextFilter" :columns="columns" :person-id="person.id" />
	</ul>
</template>

<script>
import SidebarSelectColumns from '../generic/SidebarSelectColumns'
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'FeelingSidebar',
	components: {
		SidebarSelectColumns,
	},
	data: function() {
		return {
			contextFilter: {
				app: 'health',
				module: 'feeling',
				type: 'sidebarColumns',
			},
		}
	},
	computed: {
		...mapState(['activeModule', 'showSidebar']),
		...mapGetters(['person']),
		columns: function() {
			return [
				{ id: 'mood', name: t('health', 'Mood', {}) },
				{ id: 'sadness', name: t('health', 'Sadness level', {}) },
				{ id: 'symptoms', name: t('health', 'Symptoms', {}) },
				{ id: 'attacks', name: t('health', 'Attacks', {}) },
				{ id: 'medication', name: t('health', 'Medication', {}) },
				{ id: 'pain', name: t('health', 'Pain', {}) },
			]
		},
	},
	methods: {
	},
}
</script>
