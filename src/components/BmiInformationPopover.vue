<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import { iconPaths } from '../icons.ts'

defineProps<{
	bmi: number
}>()
</script>

<template>
	<NcPopover popup-role="dialog">
		<template #trigger>
			<NcButton :aria-label="t('health', 'About the BMI calculation')"
				:title="t('health', 'About the BMI calculation')"
				variant="tertiary">
				<template #icon>
					<NcIconSvgWrapper :path="iconPaths.information" />
				</template>
			</NcButton>
		</template>
		<section :aria-label="t('health', 'BMI details')" class="bmi-information-popover__content">
			<strong>{{ t('health', 'BMI details') }}</strong>
			<dl>
				<dt>{{ t('health', 'BMI') }}</dt>
				<dd>{{ bmi.toFixed(1) }}</dd>
			</dl>
			<p>{{ t('health', 'Adult BMI is calculated as weight in kilograms divided by height in metres squared. It is shown as a descriptive value only.') }}</p>
		</section>
	</NcPopover>
</template>

<style scoped>
.bmi-information-popover__content {
	box-sizing: border-box;
	max-inline-size: min(22rem, calc(100vw - 32px));
	padding: calc(4 * var(--default-grid-baseline));
	overflow-wrap: anywhere;
}

.bmi-information-popover__content > strong {
	display: block;
	margin-block-end: calc(3 * var(--default-grid-baseline));
}

.bmi-information-popover__content dl {
	display: grid;
	grid-template-columns: max-content minmax(0, 1fr);
	margin: 0;
	gap: var(--default-grid-baseline) calc(3 * var(--default-grid-baseline));
}

.bmi-information-popover__content dt { color: var(--color-text-maxcontrast); }

.bmi-information-popover__content dd { margin: 0; font-variant-numeric: tabular-nums; }

.bmi-information-popover__content p { margin: calc(3 * var(--default-grid-baseline)) 0 0; }
</style>
