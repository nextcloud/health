/*
 * @copyright Copyright (c) 2020 Florian Steffens
 *
 * @author Florian Steffens <flost-online@mailbox.org>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import Vue from 'vue'
import Vuex from 'vuex'
import moment from '@nextcloud/moment'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

Vue.use(Vuex)

export default new Vuex.Store({
	state: {
		// .
		// static
		app: 'health',
		// .
		// settings
		settings: {},
		// .
		// module data
		// only loaded if the component is mounted
		moduleData: {},
		// .
		// local data
		activePersonId: 0,
		activeModule: 'person',
		showSidebar: false,
		// .
		// persons
		persons: [],
	},
	getters: {
		person: state => (state.persons && state.persons[state.activePersonId]) ? state.persons[state.activePersonId] : null,
		persons: state => state.persons,
		activePersonId: state => state.activePersonId,
		activeModule: state => state.activeModule,
		showSidebar: state => state.showSidebar,
	},
	mutations: {
		setPersons(state, persons) {
			state.persons = persons
		},
		setModuleData(state, data) {
			state.moduleData = data
		},
		setSettings(state, settings) {
			state.settings = settings
		},
		// directly called (without actions)
		setActivePersonId(state, id) {
			state.activePersonId = id
		},
		setActiveModule(state, module) {
			state.activeModule = module
		},
		setShowSidebar(state, bool) {
			state.showSidebar = !!bool
		},
	},
	actions: {
		cesRequest({ commit, state, getters }, data) {
			// data.contextFilter
			// data.entityFilter optional
			// data.entityData optional, needed to insert or update (if id in entityFilter is set) data
			if ('entityData' in data) {
				// console.debug('add personId in $store')
				data.entityData.personId = getters.person.id
			}

			console.debug('request', data)

			return axios.post(generateUrl('/apps/health/ces'), { data: data })
				.then(
					(response) => {
						console.debug('debug cesRequest SUCCESS-------------', response)
						return Promise.resolve(response.data)
					},
					(err) => {
						console.debug('debug cesRequest ERROR-------------')
						console.debug(err)
					},
				)
				.catch((err) => {
					console.debug('error detected cesRequest')
					console.debug(err)
				})
		},
		loadPersons: function() {
			// TODO
		},
		// eslint-disable-next-line no-empty-pattern
		addPerson: function({}, data) {
			// TODO
		},
		// eslint-disable-next-line no-empty-pattern
		deletePerson: function({}, personId) {
			// TODO
		},
		updatePerson: function({ getters }, data) {
			// TODO
		},
		updateModuleSettings: function({ state, commit }, data) {
			// data: { module: ..., type: ..., data: ... }
			// console.debug('updateModuleSettings', data)
			const settings = Object.assign({}, state.moduleSettings)
			if (!settings[data.module]) {
				settings[data.module] = {}
			}
			settings[data.module][data.type] = data.data
			// console.debug('try to set settings as following', settings)
			this.commit('setModuleSettings', settings)
		},
		getModuleSetting: function({ state }, data) {
			// data: { module: ..., type: ..., key: ... }
			if (
				this.moduleSettings[data.module]
				&& this.moduleSettings[data.module][data.type]
				&& this.moduleSettings[data.module][data.type][data.key]) {
				return this.moduleSettings[data.module][data.type][data.key]
			} else {
				return null
			}
		},
	},
})
