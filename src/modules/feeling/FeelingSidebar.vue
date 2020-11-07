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

	Feeling

	Mood
		0 Perfect
		1 Fine
		2 Okay
		3 Don't know
		4 Don't ask me

	Sadness level
		0 none
		1 low
		2 medium
		3 height

	Symptoms
		Fatigue
		No Appetite
		Overeating
		Repeated thoughts
		Unmotivated
		Irritable
		Lack of Concentration
		Anxiety
		Isolation self from others
		Thoughts of death/sicide
		Feeling hopeless
		Feeling worthless
		Indecisive
		[personal unit]

	Attacs
		Panic attac
		Fit of rage
		Ashma attac
		[personal unit]

	Medication
		default medication
		Variations [text]

	Pain
		0 none
		1 low
		2 medium
		3 height

	-->

<template>
	<ul>
		<li><h3>{{ t('health', 'General settings', {}) }}</h3></li>
		<li><h4>{{ t('health', 'Select columns', {}) }}</h4></li>
		<ActionCheckbox
			id="feelingColumnMood"
			:checked="person.feelingColumnMood"
			value="mood"
			@change="updateColumn">
			{{ t('health', 'Mood', {}) }}
		</ActionCheckbox>
		<ActionCheckbox
			id="feelingColumnSadness"
			:checked="person.feelingColumnSadness"
			value="sadness"
			@change="updateColumn">
			{{ t('health', 'Sadness level', {}) }}
		</ActionCheckbox>
		<ActionCheckbox
			id="feelingColumnSymptoms"
			:checked="person.feelingColumnSymptoms"
			value="symptoms"
			@change="updateColumn">
			{{ t('health', 'Symptoms', {}) }}
		</ActionCheckbox>
		<ActionCheckbox
			id="feelingColumnAttacks"
			:checked="person.feelingColumnAttacks"
			value="attacks"
			@change="updateColumn">
			{{ t('health', 'Attacks', {}) }}
		</ActionCheckbox>
		<ActionCheckbox
			id="feelingColumnMedication"
			:checked="person.feelingColumnMedication"
			value="medication"
			@change="updateColumn">
			{{ t('health', 'Medication', {}) }}
		</ActionCheckbox>
		<ActionCheckbox
			id="feelingColumnPain"
			:checked="person.feelingColumnPain"
			value="pain"
			@change="updateColumn">
			{{ t('health', 'Pain', {}) }}
		</ActionCheckbox>

		<li><h3>{{ t('health', 'Custom values', {}) }}</h3></li>
		<li><h4>{{ t('health', 'Special symptom name', {}) }}</h4></li>
		<ActionInput
			type="text"
			icon="icon-rename"
			:value="person.feelingSpecialSymptomName"
			@submit="updateSpecialSymptomName" />
		<li><h4>{{ t('health', 'Special attack name', {}) }}</h4></li>
		<ActionInput
			type="text"
			icon="icon-rename"
			:value="person.feelingSpecialAttackName"
			@submit="updateSpecialAttackName" />
		<li><h4>{{ t('health', 'Default medication', {}) }}</h4></li>
		<div class="textarea-sidebar">
			<textarea
				ref="feelingDefaultMedication"
				:value="person.feelingDefaultMedication" />
		</div>
		<button
			@click="updateDefaultMedication">
			{{ t('health', 'Safe medication', {}) }}
		</button>
	</ul>
</template>

<script>
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'
import ActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox'
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'FeelingSidebar',
	components: {
		ActionInput,
		ActionCheckbox,
	},
	data: function() {
		return {
		}
	},
	computed: {
		...mapState(['activeModule', 'showSidebar']),
		...mapGetters(['person']),
	},
	methods: {
		updateColumn: function(e) {
			console.debug(e)
			this.$store.dispatch('setValue', { key: e.target.id, value: e.target.checked })
		},
		updateDefaultMedication: function(e) {
			this.$store.dispatch('setValue', { key: 'feelingDefaultMedication', value: this.$refs.feelingDefaultMedication.value })
			console.debug(e)
		},
		updateSpecialAttackName: function(e) {
			console.debug(e)
			this.$store.dispatch('setValue', { key: 'feelingSpecialAttackName', value: e.target[1].value })
		},
		updateSpecialSymptomName: function(e) {
			console.debug(e)
			this.$store.dispatch('setValue', { key: 'feelingSpecialSymptomName', value: e.target[1].value })
		},
	},
}
</script>
