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

Vue.use(Vuex)

export default new Vuex.Store({
	modules: {
	},
	state: {
		activePersonId: 0,
		activeModule: 'weight', // persons
		showSidebar: false,
		persons: null,
	},
	getters: {
		persons: state => state.persons,
		person: state => state.persons[state.activePersonId],
		activePersonId: state => state.activePersonId,
		activeModule: state => state.activeModule,
		showSidebar: state => state.showSidebar,
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
	},
	actions: {
		addPerson: function({ context, getters, commit }, person) {
			const id = getters.persons.length + 1
			person.id = id
			commit('addPerson', person)
			commit('activePersonId', id)
		},
	},
})
