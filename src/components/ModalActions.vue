<template>
	<div class="modal-actions">
		<slot />
	</div>
</template>

<style scoped>
.modal-actions {
	display: flex;
	align-items: center;
	justify-content: flex-end;
	box-sizing: border-box;
	width: 100%;
	min-width: 0;
	padding: calc(5 * var(--default-grid-baseline)) calc(2 * var(--default-grid-baseline)) calc(3 * var(--default-grid-baseline));
	gap: calc(2 * var(--default-grid-baseline));
	flex-wrap: wrap;
}
</style>
