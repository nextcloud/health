<script setup lang="ts">
withDefaults(defineProps<{
	symbol: string
	size?: 'button' | 'compact' | 'menu'
}>(), {
	size: 'compact',
})
</script>

<template>
	<span aria-hidden="true" class="event-symbol" :class="`event-symbol--${size}`">{{ symbol }}</span>
</template>

<style scoped>
.event-symbol {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	font-size: 1.1rem;
	line-height: 1;
}

.event-symbol--button {
	width: 20px;
	height: 20px;
}

.event-symbol--compact {
	flex: 0 0 1.35rem;
	width: 1.35rem;
	height: 1.35rem;
}

.event-symbol--menu {
	flex: 0 0 var(--default-clickable-area);
	width: var(--default-clickable-area);
	height: var(--default-clickable-area);
}
</style>
