<template>
	<NcContent app-name="health">
		<NcAppContent>
			<h2>Health</h2>
		</NcAppContent>
	</NcContent>
</template>

<script>
import { NcContent, NcAppContent } from '@nextcloud/vue'

export default {

	components: {
		NcContent,
		NcAppContent,
	},
}
</script>
<style lang="scss">

	.app-content {
		padding-left: calc(var(--default-grid-baseline) * 6) !important;
		padding-right: calc(var(--default-grid-baseline) * 5) !important;
	}

	.content-wrapper *:after, .content-wrapper *:before{
		-webkit-box-sizing: border-box;
		-moz-box-sizing: border-box;
		box-sizing: border-box;
	}

	.row {
		width: 100%;
	}

	.row.first-row {
		margin-top: calc(var(--default-grid-baseline) * 10);
	}

	.col-1 { width: 25%; }

	.col-2 { width: 50%; }

	.col-3 { width: 75%; }

	.col-4 { width: 100%; }

	[class*='col-'] {
		float: left;
		padding: 15px 15px 0;
	}

	.floatReverse [class*='col-'] {
		float: right;
	}

	.row::after {
		content: '';
		clear: both;
		display: table;
	}

	@media only screen and (max-width: 1025px) {
		.hide-m {
			display: none;
		}

		.col-1 { width: 50%; }

		.col-3 { width: 100%; }

	}

	@media only screen and (max-width: 641px) {
		.hide-s {
			display: none;
		}

		.col-1 { width: 50%; }

		.col-2 { width: 100%; }

	}

	.content-wrapper {
		padding: 35px 10px 10px 10px;
	}

	.green {
		color: #19880C;
	}

	.red {
		color: #bd1500;
	}

</style>
