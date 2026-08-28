<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import { iconPaths } from '../icons.ts'
import { getSourceLabel } from '../metrics.ts'

const { id, source, context, recordLabel } = defineProps<{
	id: number
	source: string
	context: string | null
	recordLabel: string
}>()

function contextLabel(context: string): string {
	if (context === 'checkin') {
		return t('health', 'Check-in')
	}
	if (context === 'checkout') {
		return t('health', 'Check-out')
	}

	return context
}
</script>

<template>
	<NcPopover
		no-focus-trap
		popup-role="dialog">
		<template #trigger>
			<NcButton :aria-label="t('health', 'Show information for {record}', { record: recordLabel })"
				:title="t('health', 'Entry information')"
				variant="tertiary">
				<template #icon>
					<NcIconSvgWrapper :path="iconPaths.information" />
				</template>
			</NcButton>
		</template>
		<div :aria-labelledby="`entry-information-${id}`"
			class="detail-information-popover__content"
			role="dialog"
			tabindex="-1">
			<strong :id="`entry-information-${id}`">{{ t('health', 'Entry information') }}</strong>
			<dl>
				<dt>{{ t('health', 'ID') }}</dt>
				<dd>{{ id }}</dd>
				<dt>{{ t('health', 'Source') }}</dt>
				<dd>{{ getSourceLabel(source) }}</dd>
				<template v-if="context !== null && context !== 'manual'">
					<dt>{{ t('health', 'Context') }}</dt>
					<dd>{{ contextLabel(context) }}</dd>
				</template>
			</dl>
		</div>
	</NcPopover>
</template>

<style scoped>
.detail-information-popover__content { min-width: 13rem; padding: 12px; }

.detail-information-popover__content > strong { display: block; margin-block-end: 8px; }

.detail-information-popover__content dl {
	display: grid;
	grid-template-columns: max-content minmax(0, 1fr);
	gap: 4px 12px;
	margin: 0;
}

.detail-information-popover__content dt { color: var(--color-text-maxcontrast); }

.detail-information-popover__content dd { margin: 0; overflow-wrap: anywhere; }
</style>
