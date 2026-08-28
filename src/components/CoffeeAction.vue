<script setup lang="ts">
import type { BeverageOption } from '../metrics.ts'

import { t } from '@nextcloud/l10n'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import EventSymbol from './EventSymbol.vue'
import { BEVERAGE_OPTIONS, getOptionLabel, getOptionSymbol } from '../metrics.ts'

withDefaults(defineProps<{ disabled?: boolean, variant?: 'secondary' | 'tertiary' }>(), {
	disabled: false,
	variant: 'secondary',
})

const emit = defineEmits<{ select: [optionValue: BeverageOption] }>()
</script>

<template>
	<NcActions :aria-label="t('health', 'Record coffee or tea')"
		:disabled="disabled"
		:title="t('health', 'Record coffee or tea')"
		:variant="variant">
		<template #icon>
			<EventSymbol symbol="☕️" size="button" />
		</template>
		<NcActionButton
			v-for="optionValue in BEVERAGE_OPTIONS"
			:key="optionValue"
			close-after-click
			@click="emit('select', optionValue)">
			<template #icon>
				<EventSymbol :symbol="getOptionSymbol('hydration', optionValue)" size="menu" />
			</template>
			{{ getOptionLabel('hydration', optionValue) }}
		</NcActionButton>
	</NcActions>
</template>
