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
	<div>
		<button
			@click="showModal = true">
			{{ t('health', 'Add new {eName}', {eName: entityName}) }}
		</button>
		<Modal v-if="showModal" title="add item" @close="showModal = false">
			<div class="modal__content">
				<h1>{{ t('health', 'Add new {entityName}', { entityName: entityName }) }}</h1>
				<div class="scrollable">
					<div
						v-for="(h, index) in header"
						:key="index"
						class="field">
						<h2 v-if="isNewSection(index)">
							{{ h.section.name }}
						</h2>
						<h3>{{ h.name }}</h3>
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
						</div>
						<div v-else-if="h.type === 'longtext'">
							<textarea v-model="values[h.columnId]" :placeholder="(h.placeholder !== undefined) ? h.placeholder : ''" />
						</div>
						<div v-else-if="h.type === 'boolean'">
							<div>
								{{ values[h.columnId] ? ('textTrue' in h) ? h.textTrue : 'x' : '' }}
								{{ !values[h.columnId] ? ('textFalse' in h) ? h.textFalse : '-' : '-' }}
							</div>
							<button
								class="icon-checkmark boolean"
								@click="values[h.columnId] = true" />
							<button
								class="icon-close boolean"
								@click="values[h.columnId] = false" />
						</div>
						<div v-else>
							<ActionInput
								:type="text"
								:value="values[h.columnId]"
								icon="icon-edit" />
						</div>
					</div>
				</div>
				<button
					class="primary"
					@click="sendData">
					{{ t('health', 'Add {eName}', {eName: entityName}) }}
				</button>
				<button
					@click="showModal = false">
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
	},
	data: function() {
		return {
			values: [],
			showModal: false,
		}
	},
	methods: {
		sendData: function() {
			this.$emit('addItem', this.values)
			this.showModal = false
		},
		isNewSection: function(index) {
			return !(this.header[index]
				&& this.header[index].section
				&& this.header[(index - 1)]
				&& this.header[(index - 1)].section
				&& this.header[index].section.id === this.header[(index - 1)].section.id)
		},
	},
}
</script>
<style lang="scss" scoped>
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

	.modal__content textarea {
		width: 97%;
	}

	.modal__content .boolean {
		padding: 16px;
	}

	@media screen and (max-width:700px) {

		.modal__content {
			width: 90vw;
		}

	}
</style>
