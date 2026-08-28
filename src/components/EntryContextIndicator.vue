<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'

const props = defineProps<{ context: string }>()

const indicator = computed(() => {
	if (props.context === 'checkin') {
		return { symbol: '🌅', label: t('health', 'Recorded during Check-in') }
	}
	if (props.context === 'checkout') {
		return { symbol: '🌃', label: t('health', 'Recorded during Check-out') }
	}
	return null
})
</script>

<template>
	<span v-if="indicator" class="entry-context-indicator">
		<span aria-hidden="true">{{ indicator.symbol }}</span>
		<span class="entry-context-indicator__label">{{ indicator.label }}</span>
	</span>
</template>

<style scoped>
.entry-context-indicator {
	display: inline-flex;
	align-items: center;
	line-height: 1;
}

.entry-context-indicator__label {
	position: absolute;
	width: 1px;
	height: 1px;
	padding: 0;
	margin: -1px;
	overflow: hidden;
	clip-path: inset(50%);
	white-space: nowrap;
	border: 0;
}
</style>
