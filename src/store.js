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
		// managing data
		activePersonId: null,
		activeModule: 'weight', // {persons, weight}
		showSidebar: false,
		// .
		// complete data
		persons: null,
		// .
		// individual data for actual person
		weightData: null,
	},
	getters: {
		// NEW -----
		person: state => (state.persons && state.persons[state.activePersonId]) ? state.persons[state.activePersonId] : null,
		personsLength: state => state.persons ? state.persons.length : 0,
		// OLD -----
		activePersonId: state => state.activePersonId,
		persons: state => (state.persons) ? state.persons : null,
		personName: state => (state.persons && state.persons[state.activePersonId]) ? state.persons[state.activePersonId].name : '',
		personAge: state => (state.persons && state.persons[state.activePersonId]) ? state.persons[state.activePersonId].age : null,
		personSize: state => (state.persons && state.persons[state.activePersonId]) ? state.persons[state.activePersonId].size : null,
		personSex: state => (state.persons && state.persons[state.activePersonId]) ? state.persons[state.activePersonId].sex : '',
		personModuleWeight: state => (state.persons && state.persons[state.activePersonId]) ? state.persons[state.activePersonId].enabledModuleWeight : false,
		weightTarget: state => (state.persons && state.persons[state.activePersonId]) ? state.persons[state.activePersonId].weightTarget : null,
		weightUnit: state => (state.persons && state.persons[state.activePersonId]) ? state.persons[state.activePersonId].weightUnit : null,
		weightTargetInitialWeight: state => (state.persons && state.persons[state.activePersonId]) ? state.persons[state.activePersonId].weightTargetInitialWeight : null,
		weightTargetBounty: state => (state.persons && state.persons[state.activePersonId]) ? state.persons[state.activePersonId].weightTargetBounty : null,
		weightMeasurementName: state => (state.persons && state.persons[state.activePersonId]) ? state.persons[state.activePersonId].weightMeasurementName : null,
	},
	mutations: {
		// NEW -----
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
		// directly called (without actions)
		showSidebar(state, bool) {
			state.showSidebar = bool
		},
		// OLD -----
		updatePersonName(state, o) {
			state.persons[o.id].name = o.name
		},
		updatePersonAge(state, age) {
			state.persons[state.activePersonId].age = parseInt(age, 0)
		},
		updatePersonSex(state, sex) {
			state.persons[state.activePersonId].sex = sex
		},
		updatePersonSize(state, size) {
			state.persons[state.activePersonId].size = parseInt(size, 0)
		},
		updatePersonEnabledModuleWeight(state, value) {
			state.persons[state.activePersonId].enabledModuleWeight = value
		},
		updatePersonWeightUnit(state, value) {
			state.persons[state.activePersonId].weightUnit = value
		},
		updatePersonWeightTarget(state, value) {
			state.persons[state.activePersonId].weightTarget = parseInt(value)
		},
		updatePersonWeightTargetBounty(state, value) {
			state.persons[state.activePersonId].weightTargetBounty = value
		},
		updatePersonWeightTargetInitialWeight(state, value) {
			state.persons[state.activePersonId].weightTargetInitialWeight = parseInt(value)
		},
		updatePersonWeightMeasurementName(state, value) {
			state.persons[state.activePersonId].weightMeasurementName = value
		},
		setWeightData(state, value) {
			state.persons[state.activePersonId].weightdata = value
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
		loadModuleContentForPerson({ state, getters, commit, dispatch }) {
			// this ist called if the active person or module changed
			// it should load all data, that is neccessary for the active module
			console.debug('debug: start loading loadModuleContentForPerson')

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
						}
					)
					.catch((err) => {
						console.debug('error detected')
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
		// OLD -----
		updatePerson: function({ context, getters, commit }, data) {
			console.debug('depricated function: updatePerson')
			if (!('id' in data)) {
				data.id = getters.activePersonId
			}
			const p = getters.persons[data.id]
			axios.put(generateUrl('/apps/health/persons/' + p.id), { key: data.key, value: '' + data.value })
				.then(
					(response) => {
						// console.debug('debug axUpdatePersons SUCCESS-------------')
						console.debug(response)
						const method = 'updatePerson' + data.key[0].toUpperCase() + data.key.slice(1)
						commit(method, data.value)
						// const p = response.data
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
		insertWeightData({ dispatch, getters, commit }, row) {
			console.debug('start function: insertWeightData')
			axios.post(generateUrl('/apps/health/weightdata/person/' + getters.person.id + '/create'), row)
				.then(
					(response) => {
						console.debug('debug axInsertWeightData SUCCESS-------------')
						console.debug(response)
						const d = getters.person.weightdata
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
		async updateWeightData({ context, getters, commit }, data) {
			console.debug('depricated function: updateWeightData')
			const axdata = {
				weight: data.weight,
				date: data.date,
				bodyfat: data.bodyfat,
				measurement: data.measurement,
			}
			axios.put(generateUrl('/apps/health/weightdata/update/' + getters.person.weightdata[data.id].id), axdata)
				.then(
					(response) => {
						console.debug('debug axUpdateWeightData SUCCESS-------------')
						console.debug(response)
						const d = getters.person.weightdata
						d.splice(data.id, 1)
						d.unshift(response.data)
						commit('setWeightData', d)
						context.sortWeightData()
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
			// const d = getters.person.weightdata
			// d[data.id].weight = data.weight
			// d[data.id].measurement = data.measurement
			// d[data.id].date = data.date
			// d[data.id].bodyfat = data.bodyfat
			// commit('setWeightData', d)
		},
		deleteWeightDataRow: function({ context, getters, commit }, i) {
			console.debug('depricated function: deleteWeightDatarow')
			axios.delete(generateUrl('/apps/health/weightdata/delete/' + getters.person.weightdata[i].id))
				.then(
					(response) => {
						console.debug('debug axDeleteWeightData SUCCESS-------------')
						console.debug(response)
						const d = getters.person.weightdata
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
