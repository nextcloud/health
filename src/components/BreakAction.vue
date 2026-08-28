<script setup lang="ts">
import type { BreakOption } from '../metrics.ts'

import { t } from '@nextcloud/l10n'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import EventSymbol from './EventSymbol.vue'
import { BREAK_OPTIONS, getOptionLabel, getOptionSymbol } from '../metrics.ts'

withDefaults(defineProps<{ disabled?: boolean, variant?: 'secondary' | 'tertiary' }>(), {
	disabled: false,
	variant: 'secondary',
})

const emit = defineEmits<{ select: [optionValue: BreakOption] }>()
</script>

<template>
	<NcActions :aria-label="t('health', 'Record break')"
		:disabled="disabled"
		:title="t('health', 'Record break')"
		:variant="variant">
		<template #icon>
			<EventSymbol symbol="⏱️" size="button" />
		</template>
		<NcActionButton
			v-for="optionValue in BREAK_OPTIONS"
			:key="optionValue"
			close-after-click
			@click="emit('select', optionValue)">
			<template #icon>
				<EventSymbol :symbol="getOptionSymbol('break', optionValue)" size="menu" />
			</template>
			{{ getOptionLabel('break', optionValue) }}
		</NcActionButton>
	</NcActions>
</template>
