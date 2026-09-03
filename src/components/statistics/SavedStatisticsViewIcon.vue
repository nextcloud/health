<script setup lang="ts">
import { computed } from 'vue'
import { safeSavedStatisticsViewIcon } from '../../statisticsViews.ts'

const props = defineProps<{ icon: string }>()
const displayIcon = computed(() => safeSavedStatisticsViewIcon(props.icon))
</script>

<template>
	<span aria-hidden="true" class="saved-statistics-view-icon">{{ displayIcon }}</span>
</template>

<style scoped>
.saved-statistics-view-icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 20px;
	height: 20px;
	font-size: 16px;
	line-height: 1;
}
</style>
