<template>
	<article class="metric-value-card">
		<slot />
	</article>
</template>

<style scoped>
.metric-value-card {
	position: relative;
	display: flex;
	align-items: center;
	box-sizing: border-box;
	flex: 0 1 15rem;
	flex-direction: column;
	min-width: min(100%, 14rem);
	min-height: 11rem;
	max-width: 16rem;
	padding: calc(3 * var(--default-grid-baseline));
	background-color: var(--color-background-hover);
	border-radius: var(--border-radius-element);
	transition: background-color 120ms ease-in-out;
}

@media (prefers-reduced-motion: reduce) {
	.metric-value-card {
		transition: none;
	}
}
</style>
