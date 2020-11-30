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
		app: 'health',
		// .
		// managing data
		activePersonId: null,
		activeModule: 'feeling',
		showSidebar: false,
		// .
		// complete data
		persons: null,
		// .
		// individual data for actual person
		weightData: null,
		personData: null,
		feelingData: null,
	},
	getters: {
		person: state => (state.persons && state.persons[state.activePersonId]) ? state.persons[state.activePersonId] : null,
		personsLength: state => state.persons ? state.persons.length : 0,
	},
	mutations: {
		persons(state, persons) {
			state.persons = persons.slice()
		},
		activePersonId(state, id) {
			state.activePersonId = id
		},
		activeModule(state, module) {
			state.activeModule = module
		},
		weightData(state, data) {
			state.weightData = data
		},
		personData(state, data) {
			state.personData = data
		},
		feelingData(state, data) {
			state.feelingData = data
		},
		setWeightData(state, value) {
			state.persons[state.activePersonId].weightdata = value
		},
		// directly called (without actions)
		showSidebar(state, bool) {
			state.showSidebar = bool
		},
	},
	actions: {
		loadPersons({ dispatch, state, getters, commit }) {
			console.debug('debug: start loading persons')
			axios.get(generateUrl('/apps/health/persons'))
				.then(
					(response) => {
						console.debug('debug axLoadPersons SUCCESS-------------')
						console.debug(response)
						if (response.data && response.data.length > 0) {
							commit('persons', response.data)
							if (state.activePersonId === null) {
								dispatch('setActivePerson', 0)
							}
							dispatch('loadModuleContentForPerson')
						}
					},
					(err) => {
						console.debug('debug axLoadPersons ERROR-------------')
						console.debug(err)
					}
				)
				.catch((err) => {
					console.debug('error detected')
					console.debug(err)
				})
		},
		setActivePerson({ dispatch, commit }, id) {
			commit('activePersonId', id)
			dispatch('loadModuleContentForPerson')
		},
		setActiveModule({ dispatch, commit }, module) {
			commit('activeModule', module)
			dispatch('loadModuleContentForPerson')
		},
		async loadModuleContentForPerson({ state, getters, commit, dispatch }) {
			// this ist called if the active person or module changed
			// it should load all data, that is necessary for the active module
			console.debug('debug: start loading loadModuleContentForPerson')

			commit('weightData', null)
			commit('personData', null)

			if (state.activeModule === 'weight') {
				console.debug('debug: start loading weightdata')
				axios.get(generateUrl('/apps/health/weightdata/person/' + getters.person.id))
					.then(
						(response) => {
							console.debug('debug axLoadWeightdata SUCCESS-------------')
							console.debug(response)
							commit('weightData', response.data)
							dispatch('sortWeightData')
						},
						(err) => {
							console.debug('debug axLoadWeightdata ERROR-------------')
							console.debug(err)
						},
					)
					.catch((err) => {
						console.debug('error detected')
						console.debug(err)
					})
			} else if (state.activeModule === 'person') {
				console.debug('debug: start loading persondata')
				axios.get(generateUrl('/apps/health/person/' + getters.person.id + '/data'))
					.then(
						(response) => {
							console.debug('debug axLoadPersondata SUCCESS-------------')
							console.debug(response)
							commit('personData', response.data)
						},
						(err) => {
							console.debug('debug axLoadPersondata ERROR-------------')
							console.debug(err)
						},
					)
					.catch((err) => {
						console.debug('error detected')
						console.debug(err)
					})
			} else if (state.activeModule === 'feeling') {
				console.debug('debug: start loading feelingdata')
				const request = {
					contextFilter: {
						app: state.app,
						module: state.activeModule,
						type: 'datasets',
					},
					entityFilter: {
						personId: state.activePersonId,
					},
				}

				axios.post(generateUrl('/apps/health/ces'), { data: request })
					.then(
						(response) => {
							// console.debug('debug feelingdata SUCCESS-------------')
							// console.debug(response)
							const data = []
							response.data.forEach(function(item) {
								const d = JSON.parse(item.data)
								// d.id = item.id
								delete d.personId
								data.push(d)
							})
							commit('feelingData', data)
						},
						(err) => {
							console.debug('debug feelingdata ERROR-------------')
							console.debug(err)
						},
					)
					.catch((err) => {
						console.debug('error detected cesRequest')
						console.debug(err)
					})
			}
		},
		setValue({ commit, state }, data) {
			// data: {key, value, [id]}
			console.debug('start setValue')
			console.debug(data)

			if (!('id' in data)) {
				data.id = state.activePersonId
			}

			const p = state.persons[data.id]
			axios.put(generateUrl('/apps/health/persons/' + p.id), { key: data.key, value: '' + data.value })
				.then(
					(response) => {
						console.debug('debug axSetValue SUCCESS-------------')
						console.debug(response)
						const persons = state.persons
						persons[data.id] = response.data
						commit('persons', persons)
					},
					(err) => {
						console.debug('debug axUpdatePersons ERROR-------------')
						console.debug(err)
					}
				)
				.catch((err) => {
					console.debug('error detected')
					console.debug(err)
				})
		},
		cesRequest({ commit, state }, data) {
			// console.debug('start cesRequest')

			if ('entityData' in data) {
				data.entityData.personId = state.activePersonId
			}
			// console.debug(data)

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
		addPerson: function({ state, dispatch, commit }, name) {
			console.debug('debug start addPerson')
			axios.post(generateUrl('/apps/health/persons'), { name: name })
				.then(
					(response) => {
						console.debug('debug axPostPersons SUCCESS-------------')
						console.debug(response)
						const persons = state.persons
						persons.push(response.data)
						commit('persons', persons)
						const id = state.persons.length - 1
						dispatch('setActivePerson', id)
					},
					(err) => {
						console.debug('debug axPostPersons ERROR-------------')
						console.debug(err)
					}
				)
				.catch((err) => {
					console.debug('error detected')
					console.debug(err)
				})
		},
		deletePerson: function({ state, dispatch, commit }, id) {
			console.debug('debug deletePerson')
			const p = state.persons[id]
			axios.delete(generateUrl('/apps/health/persons/' + p.id))
				.then(
					(response) => {
						console.debug('debug axDeletePersons SUCCESS-------------')
						console.debug(response)
						const persons = state.persons
						persons.splice(id, 1)
						commit('persons', persons)
						if (state.activePersonId === id) {
							dispatch('setActivePerson', 0)
						}
					},
					(err) => {
						console.debug('debug axDeletePersons ERROR-------------')
						console.debug(err)
					}
				)
				.catch((err) => {
					console.debug('error detected')
					console.debug(err)
				})
		},
		insertFeelingData({ dispatch, getters, commit, state }, item) {
			const d = state.feelingData
			d.push(item)
			commit('feelingData', d)
			// dispatch('sortWeightData')
		},
		async sortWeightData({ state, getters, commit }) {
			console.debug('start function: sortWeightData')
			const d = state.weightData
			if (!d) {
				return null
			}
			d.sort(function(a, b) {
				if (moment(a.date) > moment(b.date)) {
					return -1
				} else if (moment(a.date) < moment(b.date)) {
					return 1
				} else {
					return 0
				}
			})
			commit('setWeightData', d)
		},
		insertWeightData({ dispatch, getters, commit, state }, row) {
			console.debug('start function: insertWeightData')
			axios.post(generateUrl('/apps/health/weightdata/person/' + getters.person.id + '/create'), row)
				.then(
					(response) => {
						console.debug('debug axInsertWeightData SUCCESS-------------')
						console.debug(response)
						const d = state.weightData
						d.unshift(response.data)
						commit('setWeightData', d)
						dispatch('sortWeightData')
					},
					(err) => {
						console.debug('debug axCreateWeightdata ERROR-------------')
						console.debug(err)
					}
				)
				.catch((err) => {
					console.debug('error detected')
					console.debug(err)
				})
			return false
		},
		updateWeightData({ dispatch, state, commit }, data) {
			console.debug('start function: updateWeightData')
			const serverId = state.weightData[data.id].id
			const clientId = data.id
			delete data.id
			axios.put(generateUrl('/apps/health/weightdata/update/' + serverId), data)
				.then(
					(response) => {
						console.debug('debug axUpdateWeightData SUCCESS-------------')
						console.debug(response)
						const d = state.weightData
						d.splice(clientId, 1)
						d.unshift(response.data)
						commit('setWeightData', d)
						dispatch('sortWeightData')
					},
					(err) => {
						console.debug('debug axUpdateWeightData ERROR-------------')
						console.debug(err)
					}
				)
				.catch((err) => {
					console.debug('error detected')
					console.debug(err)
				})
		},
		deleteWeightDataRow: function({ context, state, commit }, i) {
			console.debug('function: deleteWeightDatarow')
			axios.delete(generateUrl('/apps/health/weightdata/delete/' + state.weightData[i].id))
				.then(
					(response) => {
						console.debug('debug axDeleteWeightData SUCCESS-------------')
						console.debug(response)
						const d = state.weightData
						d.splice(i, 1)
						commit('setWeightData', d)
					},
					(err) => {
						console.debug('debug axDeleteWeightData ERROR-------------')
						console.debug(err)
					}
				)
				.catch((err) => {
					console.debug('error detected')
					console.debug(err)
				})
		},
	},
})
