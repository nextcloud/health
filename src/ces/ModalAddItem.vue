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
	- header definition: objects in array
	- columnId REQUIRED
	- name -> Name that is displayed REQUIRED
	- type -> number, text, longtext, date, datetime, select, multiselect REQUIRED
	- default -> function that returns default value
	- show -> is the field hidden
	- section -> {id, name} -> every new sections displays a header with the name
	- hint -> give some description or additional information
	-
	- if type = number
	- max
	- min
	-
	- if type select/multiselect
	- options -> {id, label} -> label should be translated
	-
	-->

<template>
	<div>
		<button
			:class="{[icon]: icon !== null}"
			@click="showModal = true">
			{{ icon === null ? t('health', 'Add new {eName}', {eName: entityName}) : '' }}
		</button>
		<Modal v-if="showModal" title="add item" @close="showModal = false">
			<div class="modal__content">
				<h1>{{ t('health', 'Add new {entityName}', { entityName: entityName }) }}</h1>
				<div class="scrollable">
					<div
						v-for="(h, index) in header"
						:key="index"
						:class="{ hide: !h.show, field: true }">
						<h2 v-if="isNewSection(index)">
							{{ h.section.name }}
						</h2>
						<h3>{{ h.name }}</h3>
						<div v-if="'hint' in h" class="hint">
							{{ h.hint }}
						</div>
						<div v-if="h.type === 'date'">
							<input
								v-model="values[h.columnId]"
								type="date"
								class="widthfitcontent">
						</div>
						<div v-else-if="h.type === 'datetime'">
							<input
								v-model="values[h.columnId]"
								type="datetime-local"
								class="widthfitcontent">
						</div>
						<div v-else-if="h.type === 'select'" class="wrapper">
							<Multiselect
								v-model="values[h.columnId]"
								:options="h.options"
								track-by="id"
								label="label" />
						</div>
						<div v-else-if="h.type === 'multiselect'" class="wrapper">
							<Multiselect
								v-model="values[h.columnId]"
								:options="h.options"
								:multiple="true"
								track-by="id"
								label="label" />
							<div v-if="values[h.columnId]">
								<div v-for="(selection, i) in values[h.columnId]" :key="i">
									{{ selection.label }}
								</div>
							</div>
						</div>
						<div v-else-if="h.type === 'longtext'">
							<textarea v-model="values[h.columnId]" :placeholder="(h.placeholder !== undefined) ? h.placeholder : ''" />
						</div>
						<div v-else-if="h.type === 'boolean'">
							<input :id="h.columnId" v-model="values[h.columnId]" type="checkbox">
							<label :for="h.columnId">
								{{
									values[h.columnId] && values[h.columnId] === true ? h.textTrue ? h.textTrue : 'On' : h.textFalse ? h.textFalse : 'Off'
								}}
							</label>
						</div>
						<div v-else-if="h.type === 'number'">
							<input
								v-model="values[h.columnId]"
								type="Number"
								:min="'min' in h ? h.min : ''"
								:max="'max' in h ? h.max : ''"
								class="widthfitcontent">
						</div>
						<div v-else>
							<input
								v-model="values[h.columnId]"
								class="widthfitcontent">
						</div>
					</div>
				</div>
				<button
					class="primary"
					@click="sendData">
					{{ t('health', 'Send {eName}', {eName: entityName}) }}
				</button>
				<button
					@click="closeModal">
					{{ t('health', 'Cancel', {}) }}
				</button>
			</div>
		</Modal>
	</div>
</template>

<script>
import Modal from '@nextcloud/vue/dist/Components/Modal'
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'

export default {
	name: 'ModalAddItem',
	components: {
		Modal,
		Multiselect,
	},
	props: {
		header: {
			type: Array,
			default: null,
		},
		entityName: {
			type: String,
			default: 'item',
		},
		itemData: {
			type: Object,
			default: function() { return {} },
		},
		icon: {
			type: String,
			default: null,
		},
		id: {
			type: Number,
			default: null,
		},
	},
	data: function() {
		return {
			values: {},
			showModal: false,
		}
	},
	watch: {
		itemData: function(newItemData) {
			console.debug('newItemData', newItemData)
			this.resetValues()
		},
	},
	mounted() {
		this.resetValues()
	},
	methods: {
		sendData: function() {
			let ret = {}
			if (this.id !== null) {
				ret.data = this.values
				ret.id = this.id
			} else {
				ret = this.values
			}

			this.$emit('addItem', ret)
			this.resetValues()
			this.showModal = false
		},
		isNewSection: function(index) {
			// TODO only show section header if it is new, problem with hidden columns
			return !(this.header[index]
				&& this.header[index].section
				&& this.header[(index - 1)]
				&& this.header[(index - 1)].section
				&& this.header[index].section.id === this.header[(index - 1)].section.id
				&& this.header[index - 1].show)
		},
		closeModal: function() {
			this.resetValues()
			this.showModal = false
		},
		resetValues: function() {
			console.debug('reset values in modal')
			this.values = Object.assign({}, this.itemData)
			this.header.forEach(h => {
				if ('default' in h && h.default instanceof Function && !(h.columnId in this.values)) {
					this.values[h.columnId] = h.default()
				}
			})
		},
	},
}
</script>
<style lang="scss" scoped>

	button {
		padding: 13px 20px 13px 20px;
	}

	.modal__content {
		width: 50vw;
		margin: 1vw 0;
		padding: 20px;
		font-weight: lighter;
	}

	.modal__content .line {
		clear: both;
	}

	.modal__content .row {
		float: right;
		width: 50vw;
	}

	.modal__content .scrollable {
		overflow-y: auto;
		height: 60vh;
		margin-bottom: 10px;
	}

	.modal__content h1 {
		font-size: 2em;
		padding-bottom: 8px;
	}

	.modal__content h2 {
		border-bottom: 1px gray solid;
		margin-right: 5vw;
	}

	.modal__content h3 {
		margin-bottom: 0px;
	}

	.modal__content .hint {
		margin-bottom: 10px;
		margin-top: 3px;
	}

	.modal__content textarea {
		width: 97%;
		margin-left: 3px;
	}

	.modal__content input {
		width: fit-content;
	}

	.modal__content input[type='checkbox'] {
		min-height: auto;
		margin-top: 15px;
		margin-left: 5px;
	}

	.wrapper {
		margin-left: 3px;
		margin-top: 10px;
	}

	.modal__content .boolean {
		padding: 16px;
	}

	.modal__content .hide {
		display: none;
	}

	@media screen and (max-width:700px) {

		.modal__content {
			width: 90vw;
		}

	}
</style>
