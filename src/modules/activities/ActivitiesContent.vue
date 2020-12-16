<!--
	- @copyright Copyright (c) 2019 Florian Steffens <flost-dev@mailbox.org>
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
	<div>
		<h2>
			{{ t('health', 'Activities for {name}', {name: person.name}) }}
		</h2>
		<EmptyContent icon="icon-comment">
			{{ t('health', 'Coming soon', {}) }}
			<template #desc>
				{{ t('health', 'This module is under development.', {}) }}
			</template>
		</EmptyContent>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
// import moment from '@nextcloud/moment'
// import DataTable from '../generic/DataTable'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'

export default {
	name: 'ActivitiesContent',
	components: {
		// DataTable,
		EmptyContent,
	},
	data: function() {
		return {
			contextFilter: {
				app: 'health',
				module: 'activities',
			},
		}
	},
	computed: {
		...mapState(['activePersonId']),
		...mapGetters(['person']),
		header: function() {
			return []
		},
	},
	methods: {
	},
}
</script>
