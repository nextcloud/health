/*
 * @copyright Copyright (c) 2018 ...
 *
 * @author ...
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

Vue.use(Vuex)

export default new Vuex.Store({
	state: {
		activePersonId: null,
		activeModule: 'weight', // persons
		showSidebar: false,
		persons: null,
	},
	getters: {
		person: state => (state.persons && state.persons[state.activePersonId]) ? state.persons[state.activePersonId] : null,
		personName: state => (state.persons && state.persons[state.activePersonId]) ? state.persons[state.activePersonId].name : '',
		personAge: state => (state.persons && state.persons[state.activePersonId]) ? state.persons[state.activePersonId].age : null,
		personSize: state => (state.persons && state.persons[state.activePersonId]) ? state.persons[state.activePersonId].size : null,
		personSex: state => (state.persons && state.persons[state.activePersonId]) ? state.persons[state.activePersonId].sex : '',
		personsLength: state => state.persons ? state.persons.length : 0,
		personModuleWeight: state => (state.persons && state.persons[state.activePersonId]) ? state.persons[state.activePersonId].enabledModules.weight : false,
		lastWeight: state => {
			if (state.persons && state.persons[state.activePersonId] && state.persons[state.activePersonId].weight.data[0]) {
				return state.persons[state.activePersonId].weight.data[0].weight
				// const l = state.persons[state.activePersonId].weight.data.length
				// if (l > 0) {
				// return state.persons[state.activePersonId].weight.data[(l - 1)].weight
				// }
			}
			return null
		},
		weightTarget: state => (state.persons && state.persons[state.activePersonId]) ? state.persons[state.activePersonId].weight.weightTarget : null,
		weightUnit: state => (state.persons && state.persons[state.activePersonId]) ? state.persons[state.activePersonId].weight.unit : null,
		weightTargetInitialWeight: state => (state.persons && state.persons[state.activePersonId]) ? state.persons[state.activePersonId].weight.weightTargetInitialWeight : null,
		weightMeasurementName: state => (state.persons && state.persons[state.activePersonId]) ? state.persons[state.activePersonId].weight.measurementName : null,
	},
	mutations: {
		persons(state, persons) {
			state.persons = persons
		},
		addPerson(state, person) {
			const p = state.persons
			p.push(person)
			state.persons = p
		},
		activePersonId(state, id) {
			state.activePersonId = id
		},
		activeModule(state, module) {
			state.activeModule = module
		},
		showSidebar(state, bool) {
			state.showSidebar = bool
		},
		deletePerson(state, id) {
			const p = state.persons
			p.splice(id, 1)
			state.persons = p
		},
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
			state.persons[state.activePersonId].enabledModules.weight = value
		},
		updateWeightUnit(state, value) {
			state.persons[state.activePersonId].weight.unit = value
		},
		updateWeightTarget(state, value) {
			state.persons[state.activePersonId].weight.weightTarget = parseInt(value)
		},
		updateWeightTargetInitialWeight(state, value) {
			state.persons[state.activePersonId].weight.weightTargetInitialWeight = parseInt(value)
		},
		updateWeightMeasurementName(state, value) {
			state.persons[state.activePersonId].weight.measurementName = value
		},
		setWeightData(state, value) {
			state.persons[state.activePersonId].weight.data = value
		},
	},
	actions: {
		addPerson: function({ context, getters, commit }, person) {
			const id = getters.personsLength + 1
			person.id = id
			commit('addPerson', person)
			commit('activePersonId', id - 1)
		},
		addWeightData: function({ context, getters, commit }, data) {
			const d = getters.person.weight.data
			d.unshift(data)
			commit('setWeightData', d)
		},
		sortWeightData: function({ context, getters, commit }) {
			const d = getters.person.weight.data
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
		deleteWeightDataRow: function({ context, getters, commit }, i) {
			const d = getters.person.weight.data
			d.splice(i, 1)
			commit('setWeightData', d)
		},
	},
})
