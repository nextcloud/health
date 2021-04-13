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
import { WeightApi } from './WeightApi'
import { PersonApi } from './PersonApi'
import { MeasurementApi } from './MeasurementApi'
import { FeelingApi } from './FeelingApi'
import { SleepApi } from './SleepApi'
import { SmokingApi } from './SmokingApi'
import { ActivitiesApi } from './ActivitiesApi'

Vue.use(Vuex)
const weightApiClient = new WeightApi()
const personApiClient = new PersonApi()
const measurementApiClient = new MeasurementApi()
const feelingApiClient = new FeelingApi()
const sleepApiClient = new SleepApi()
const smokingApiClient = new SmokingApi()
const activitiesApiClient = new ActivitiesApi()

export default new Vuex.Store({
	state: {
		initialLoading: true,
		loading: false,
		app: 'health',
		activePersonId: null,
		activeModule: 'person', // person
		showSidebar: false,
		persons: null,
		rWeightDatasets: [],
		measurementDatasets: [],
		feelingDatasets: [],
		sleepDatasets: [],
		smokingDatasets: [],
		activitiesDatasets: [],
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
		initialLoading(state, status) {
			state.initialLoading = !!status
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
		// -------------
		smokingDatasets(state, data) {
			state.smokingDatasets = data
		},
		smokingDatasetsAppend(state, data) {
			state.smokingDatasets.push(data)
		},
		smokingDatasetsDelete(state, data) {
			const existingIndex = state.smokingDatasets.findIndex(set => set.id === data.id)
			if (existingIndex !== -1) {
				state.smokingDatasets.splice(existingIndex, 1)
			}
		},
		smokingDatasetsUpdate(state, data) {
			const datasets = state.smokingDatasets
			const existingIndex = datasets.findIndex(set => set.id === data.id)
			if (existingIndex !== -1) {
				datasets.splice(existingIndex, 1)
				datasets.push(data)
			}
			state.smokingDatasets = datasets
		},
		// -------------
		activitiesDatasets(state, data) {
			state.activitiesDatasets = data
		},
		activitiesDatasetsAppend(state, data) {
			state.activitiesDatasets.push(data)
		},
		activitiesDatasetsDelete(state, data) {
			const existingIndex = state.activitiesDatasets.findIndex(set => set.id === data.id)
			if (existingIndex !== -1) {
				state.activitiesDatasets.splice(existingIndex, 1)
			}
		},
		activitiesDatasetsUpdate(state, data) {
			const datasets = state.activitiesDatasets
			const existingIndex = datasets.findIndex(set => set.id === data.id)
			if (existingIndex !== -1) {
				datasets.splice(existingIndex, 1)
				datasets.push(data)
			}
			state.activitiesDatasets = datasets
		},
	},
	actions: {
		async loadPersons({ dispatch, state, commit }) {
			commit('initialLoading', true)
			const persons = await personApiClient.load()
			if (persons && persons.length > 0) {
				commit('persons', persons)
				if (state.activePersonId === null) {
					dispatch('setActivePerson', 0)
				}
				dispatch('loadModuleContentForPerson')
			}
			commit('initialLoading', false)
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
		async loadModuleContentForPerson({ getters, commit, dispatch, state }) {
			// this ist called if the active person or module changed
			// it should load all data, that is necessary for the active module
			commit('loading', true)
			// await dispatch('weightDatasetsLoadByPerson', [])
			// await dispatch('measurementDatasetsLoadByPerson', [])
			// await dispatch('feelingDatasetsLoadByPerson', [])
			commit('rWeightDatasets', [])
			commit('feelingDatasets', [])
			commit('sleepDatasets', [])
			commit('measurementDatasets', [])
			commit('smokingDatasets', [])
			commit('activitiesDatasets', [])
			if (state.activeModule === 'weight') {
				await dispatch('weightDatasetsLoadByPerson', getters.person.id)
			} else if (state.activeModule === 'measurement') {
				await dispatch('measurementDatasetsLoadByPerson', getters.person.id)
			} else if (state.activeModule === 'feeling') {
				await dispatch('feelingDatasetsLoadByPerson', getters.person.id)
			} else if (state.activeModule === 'sleep') {
				await dispatch('sleepDatasetsLoadByPerson', getters.person.id)
			} else if (state.activeModule === 'smoking') {
				await dispatch('smokingDatasetsLoadByPerson', getters.person.id)
			} else if (state.activeModule === 'activities') {
				await dispatch('activitiesDatasetsLoadByPerson', getters.person.id)
			}
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
		async addPerson({ state, dispatch, commit }, name) {
			const p = await personApiClient.addPerson(name)
			commit('personsAdd', p)
		},
		async deletePerson({ state, dispatch, commit }, person) {
			const existingIndex = state.persons.findIndex(set => set.id === person.id)
			await personApiClient.deletePerson(person.id)
			commit('personsDelete', person)
			if (existingIndex !== -1) {
				dispatch('setActivePerson', 0)
			}
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
			console.debug('update weight dataset', set)
			const o = await weightApiClient.updateSet(set)
			console.debug('returned o', o)
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
		// module smoking
		async smokingDatasetsLoadByPerson({ commit }, personId) {
			const datasets = await smokingApiClient.findDatasetsByPerson(personId)
			// console.debug('found datasets', datasets)
			commit('smokingDatasets', datasets)
		},
		async smokingDatasetsAppend({ commit, getters }, set) {
			const o = await smokingApiClient.addSet(getters.person.id, set)
			// console.debug('returned o', o)
			commit('smokingDatasetsAppend', o)
		},
		async smokingDatasetsUpdate({ commit, getters }, set) {
			const o = await smokingApiClient.updateSet(set)
			// console.debug('returned o', o)
			commit('smokingDatasetsUpdate', o)
		},
		async smokingDatasetsDelete({ commit, getters }, set) {
			const o = await smokingApiClient.deleteSet(set)
			// console.debug('returned o', o)
			commit('smokingDatasetsDelete', o)
		},
		// module activities
		async activitiesDatasetsLoadByPerson({ commit }, personId) {
			const datasets = await activitiesApiClient.findDatasetsByPerson(personId)
			// console.debug('found datasets', datasets)
			commit('activitiesDatasets', datasets)
		},
		async activitiesDatasetsAppend({ commit, getters }, set) {
			const o = await activitiesApiClient.addSet(getters.person.id, set)
			// console.debug('returned o', o)
			commit('activitiesDatasetsAppend', o)
		},
		async activitiesDatasetsUpdate({ commit, getters }, set) {
			const o = await activitiesApiClient.updateSet(set)
			// console.debug('returned o', o)
			commit('activitiesDatasetsUpdate', o)
		},
		async activitiesDatasetsDelete({ commit, getters }, set) {
			const o = await activitiesApiClient.deleteSet(set)
			// console.debug('returned o', o)
			commit('activitiesDatasetsDelete', o)
		},
	},
})
