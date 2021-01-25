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
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { WeightApi } from './WeightApi'
import { PersonApi } from './PersonApi'
import { MeasurementApi } from './MeasurementApi'
import { FeelingApi } from './FeelingApi'
import { SleepApi } from './SleepApi'

Vue.use(Vuex)
const weightApiClient = new WeightApi()
const personApiClient = new PersonApi()
const measurementApiClient = new MeasurementApi()
const feelingApiClient = new FeelingApi()
const sleepApiClient = new SleepApi()

export default new Vuex.Store({
	state: {
		loading: false,
		// .
		// static
		app: 'health',
		// .
		// managing data
		activePersonId: null,
		activeModule: 'weight', // person
		showSidebar: false,
		// .
		// complete data
		persons: null,
		moduleSettings: {},
		moduleData: {},
		// .
		// individual data for actual person
		personData: null,
		// .
		// rebuild data
		// depending on the actual person
		rWeightDatasets: [],
		measurementDatasets: [],
		feelingDatasets: [],
		sleepDatasets: [],
	},
	getters: {
		person: state => (state.persons && state.persons[state.activePersonId]) ? state.persons[state.activePersonId] : null,
		personsLength: state => state.persons ? state.persons.length : 0,
	},
	mutations: {
		persons(state, persons) {
			state.persons = persons.slice()
		},
		personsAdd(state, person) {
			state.persons.push(person)
		},
		personsDelete(state, person) {
			const existingIndex = state.persons.findIndex(set => set.id === person.id)
			if (existingIndex !== -1) {
				state.persons.splice(existingIndex, 1)
			}
		},
		personUpdate(state, data) {
			const p = state.persons
			const existingIndex = p.findIndex(set => set.id === data.id)
			if (existingIndex !== -1) {
				p.splice(existingIndex, 1)
				p.push(data)
			}
			state.persons = p
		},
		activePersonId(state, id) {
			state.activePersonId = id
		},
		activeModule(state, module) {
			state.activeModule = module
		},
		personData(state, data) {
			state.personData = data
		},
		// directly called (without actions)
		showSidebar(state, bool) {
			state.showSidebar = bool
		},
		setModuleSettings(state, data) {
			state.moduleSettings = data
		},
		setModuleData(state, data) {
			state.moduleData = data
		},
		rWeightDatasets(state, data) {
			state.rWeightDatasets = data
		},
		rWeightDatasetsAppend(state, data) {
			state.rWeightDatasets.push(data)
		},
		rWeightDatasetsDelete(state, data) {
			const existingIndex = state.rWeightDatasets.findIndex(set => set.id === data.id)
			if (existingIndex !== -1) {
				state.rWeightDatasets.splice(existingIndex, 1)
			}
		},
		rWeightDatasetsUpdate(state, data) {
			const datasets = state.rWeightDatasets
			const existingIndex = datasets.findIndex(set => set.id === data.id)
			if (existingIndex !== -1) {
				datasets.splice(existingIndex, 1)
				datasets.push(data)
			}
			state.rWeightDatasets = datasets
		},
		loading(state, status) {
			state.loading = !!status
		},
		// -------------
		measurementDatasets(state, data) {
			state.measurementDatasets = data
		},
		measurementDatasetsAppend(state, data) {
			state.measurementDatasets.push(data)
		},
		measurementDatasetsDelete(state, data) {
			const existingIndex = state.measurementDatasets.findIndex(set => set.id === data.id)
			if (existingIndex !== -1) {
				state.measurementDatasets.splice(existingIndex, 1)
			}
		},
		measurementDatasetsUpdate(state, data) {
			const datasets = state.measurementDatasets
			const existingIndex = datasets.findIndex(set => set.id === data.id)
			if (existingIndex !== -1) {
				datasets.splice(existingIndex, 1)
				datasets.push(data)
			}
			state.measurementDatasets = datasets
		},
		// -------------
		feelingDatasets(state, data) {
			state.feelingDatasets = data
		},
		feelingDatasetsAppend(state, data) {
			state.feelingDatasets.push(data)
		},
		feelingDatasetsDelete(state, data) {
			const existingIndex = state.feelingDatasets.findIndex(set => set.id === data.id)
			if (existingIndex !== -1) {
				state.feelingDatasets.splice(existingIndex, 1)
			}
		},
		feelingDatasetsUpdate(state, data) {
			const datasets = state.feelingDatasets
			const existingIndex = datasets.findIndex(set => set.id === data.id)
			if (existingIndex !== -1) {
				datasets.splice(existingIndex, 1)
				datasets.push(data)
			}
			state.feelingDatasets = datasets
		},
		// -------------
		sleepDatasets(state, data) {
			state.sleepDatasets = data
		},
		sleepDatasetsAppend(state, data) {
			state.sleepDatasets.push(data)
		},
		sleepDatasetsDelete(state, data) {
			const existingIndex = state.sleepDatasets.findIndex(set => set.id === data.id)
			if (existingIndex !== -1) {
				state.sleepDatasets.splice(existingIndex, 1)
			}
		},
		sleepDatasetsUpdate(state, data) {
			const datasets = state.sleepDatasets
			const existingIndex = datasets.findIndex(set => set.id === data.id)
			if (existingIndex !== -1) {
				datasets.splice(existingIndex, 1)
				datasets.push(data)
			}
			state.sleepDatasets = datasets
		},

	},
	actions: {
		async loadPersons({ dispatch, state, commit }) {
			const persons = await personApiClient.load()
			if (persons && persons.length > 0) {
				commit('persons', persons)
				if (state.activePersonId === null) {
					dispatch('setActivePerson', 0)
				}
				dispatch('loadModuleContentForPerson')
			}
		},
		setActivePerson({ dispatch, commit }, id) {
			commit('activePersonId', id)
			// commit('setModuleSettings', {})
			dispatch('loadModuleContentForPerson')
		},
		setActiveModule({ dispatch, commit }, module) {
			commit('activeModule', module)
			dispatch('loadModuleContentForPerson')
		},
		async loadModuleContentForPerson({ getters, commit, dispatch }) {
			// this ist called if the active person or module changed
			// it should load all data, that is necessary for the active module
			commit('loading', true)
			// await dispatch('weightDatasetsLoadByPerson', [])
			// await dispatch('measurementDatasetsLoadByPerson', [])
			// await dispatch('feelingDatasetsLoadByPerson', [])
			await dispatch('weightDatasetsLoadByPerson', getters.person.id)
			await dispatch('measurementDatasetsLoadByPerson', getters.person.id)
			await dispatch('feelingDatasetsLoadByPerson', getters.person.id)
			await dispatch('sleepDatasetsLoadByPerson', getters.person.id)
			commit('loading', false)
		},
		async setValue({ commit, state, getters }, data) {
			// console.debug('try to update person value', data)
			// data: {key, value, [id]}
			const id = 'id' in data ? data.id : getters.person.id
			const o = await personApiClient.updateValue(id, data.key, data.value)
			// console.debug('return from person api', o)
			commit('personUpdate', o)
		},
		cesRequest({ commit, state, getters }, data) {
			console.debug('CES DEPRECATED!')
			// console.debug('start cesRequest')

			if ('entityData' in data) {
				// console.debug('add personId in $store')
				data.entityData.personId = getters.person.id
			}

			// console.debug('request', data)

			return axios.post(generateUrl('/apps/health/ces'), { data: data })
				.then(
					(response) => {
						// console.debug('cesRequest SUCCESS-------------', { request: data, response: response })
						return Promise.resolve(response.data)
					},
					(err) => {
						console.debug('debug cesRequest ERROR-------------', err)
					},
				)
				.catch((err) => {
					console.debug('error detected cesRequest', err)
				})
		},
		async addPerson({ state, dispatch, commit }, name) {
			const p = await personApiClient.addPerson(name)
			commit('personsAdd', p)
		},
		async deletePerson({ state, dispatch, commit }, person) {
			const existingIndex = state.persons.findIndex(set => set.id === person.id)
			await weightApiClient.deleteAllByPerson(person.id)
			await measurementApiClient.deleteAllByPerson(person.id)
			await personApiClient.deletePerson(person.id)
			commit('personsDelete', person)
			if (existingIndex !== -1) {
				dispatch('setActivePerson', 0)
			}
		},
		updateModuleSettings: function({ state, commit }, data) {
			console.debug('CES DEPRECATED!')

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
			console.debug('CES DEPRECATED!')

			// data: { module: ..., type: ..., key: ... }
			if (
				this.moduleSettings
				&& this.moduleSettings[data.module]
				&& this.moduleSettings[data.module][data.type]
				&& this.moduleSettings[data.module][data.type][data.key]) {
				console.debug('getModuleSetting', { request: data, result: this.moduleSettings[data.module][data.type][data.key] })
				return this.moduleSettings[data.module][data.type][data.key]
			} else {
				return null
			}
		},
		updateModuleData: function({ state, commit }, data) {
			console.debug('CES DEPRECATED!')

			// data: { module: ..., type: ..., data: ... }
			// console.debug('updateModuleSettings', data)
			const d = Object.assign({}, state.moduleData)
			if (!d[data.module]) {
				d[data.module] = {}
			}
			d[data.module][data.type] = data.data
			// console.debug('try to set settings as following', settings)
			this.commit('setModuleData', d)
		},
		// module weight
		async weightDatasetsLoadByPerson({ commit }, personId) {
			// console.debug('weightDatasetsLoadByPerson', personId)
			const datasets = await weightApiClient.findDatasetsByPerson(personId)
			// console.debug('found datasets', datasets)
			commit('rWeightDatasets', datasets)
			// console.debug('saved to store "rWeightDatasets"')
		},
		async rWeightDatasetsAppend({ commit, getters }, set) {
			// console.debug('add weight dataset', set)
			const o = await weightApiClient.addSet(getters.person.id, set)
			// console.debug('returned o', o)
			commit('rWeightDatasetsAppend', o)
		},
		async rWeightDatasetsUpdate({ commit, getters }, set) {
			// console.debug('update weight dataset', set)
			const o = await weightApiClient.updateSet(set)
			// console.debug('returned o', o)
			commit('rWeightDatasetsUpdate', o)
		},
		async rWeightDatasetsDelete({ commit, getters }, set) {
			// console.debug('remove weight dataset', set)
			const o = await weightApiClient.deleteSet(set)
			// console.debug('returned o', o)
			commit('rWeightDatasetsDelete', o)
		},
		// module measurement
		async measurementDatasetsLoadByPerson({ commit }, personId) {
			const datasets = await measurementApiClient.findDatasetsByPerson(personId)
			// console.debug('found datasets', datasets)
			commit('measurementDatasets', datasets)
		},
		async measurementDatasetsAppend({ commit, getters }, set) {
			const o = await measurementApiClient.addSet(getters.person.id, set)
			// console.debug('returned o', o)
			commit('measurementDatasetsAppend', o)
		},
		async measurementDatasetsUpdate({ commit, getters }, set) {
			const o = await measurementApiClient.updateSet(set)
			// console.debug('returned o', o)
			commit('measurementDatasetsUpdate', o)
		},
		async measurementDatasetsDelete({ commit, getters }, set) {
			const o = await measurementApiClient.deleteSet(set)
			// console.debug('returned o', o)
			commit('measurementDatasetsDelete', o)
		},
		// module feeling
		async feelingDatasetsLoadByPerson({ commit }, personId) {
			const datasets = await feelingApiClient.findDatasetsByPerson(personId)
			// console.debug('found datasets', datasets)
			commit('feelingDatasets', datasets)
		},
		async feelingDatasetsAppend({ commit, getters }, set) {
			const o = await feelingApiClient.addSet(getters.person.id, set)
			// console.debug('returned o', o)
			commit('feelingDatasetsAppend', o)
		},
		async feelingDatasetsUpdate({ commit, getters }, set) {
			const o = await feelingApiClient.updateSet(set)
			// console.debug('returned o', o)
			commit('feelingDatasetsUpdate', o)
		},
		async feelingDatasetsDelete({ commit, getters }, set) {
			const o = await feelingApiClient.deleteSet(set)
			// console.debug('returned o', o)
			commit('feelingDatasetsDelete', o)
		},
		// module sleep
		async sleepDatasetsLoadByPerson({ commit }, personId) {
			const datasets = await sleepApiClient.findDatasetsByPerson(personId)
			// console.debug('found datasets', datasets)
			commit('sleepDatasets', datasets)
		},
		async sleepDatasetsAppend({ commit, getters }, set) {
			const o = await sleepApiClient.addSet(getters.person.id, set)
			// console.debug('returned o', o)
			commit('sleepDatasetsAppend', o)
		},
		async sleepDatasetsUpdate({ commit, getters }, set) {
			const o = await sleepApiClient.updateSet(set)
			// console.debug('returned o', o)
			commit('sleepDatasetsUpdate', o)
		},
		async sleepDatasetsDelete({ commit, getters }, set) {
			const o = await sleepApiClient.deleteSet(set)
			// console.debug('returned o', o)
			commit('sleepDatasetsDelete', o)
		},
	},
})
