<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { imagePath } from '@nextcloud/router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import ModalActions from './ModalActions.vue'
import { iconPaths } from '../icons.ts'

defineProps<{ open: boolean }>()

const emit = defineEmits<{ 'update:open': [open: boolean] }>()

const paypalUrl = 'https://www.paypal.com/donate/?hosted_button_id=3NBB57F2WUFTN'
const paypalIconUrl = imagePath('health', 'donation/paypal.svg')
const bankTransferMailto = `mailto:support@datenangebot.de?subject=${encodeURIComponent(t('health', 'Bank transfer details'))}&body=${encodeURIComponent(t('health', 'Hello,\n\nI would like to support your open-source work by bank transfer.\nCould you please send me the bank details?\n\nThank you.'))}`
</script>

<template>
	<NcDialog
		:open="open"
		class="support-dialog__modal"
		:name="t('health', 'Support open-source development')"
		size="normal"
		@update:open="emit('update:open', $event)">
		<div class="support-dialog">
			<div class="support-dialog__introduction">
				<p>{{ t('health', 'I’m passionate about building useful, privacy-respecting open-source software that gives people more control over their digital lives.') }}</p>
				<p>{{ t('health', 'Contributions help support development, maintenance, testing, documentation, infrastructure, and the time needed to keep these projects useful and evolving.') }}</p>
				<p>{{ t('health', 'Every contribution, large or small, is also a meaningful sign that this work matters to someone.') }}</p>
			</div>

			<section :aria-label="t('health', 'PayPal')" class="support-dialog__option">
				<div class="support-dialog__option-heading">
					<img :src="paypalIconUrl" :alt="t('health', 'PayPal')" class="support-dialog__paypal-icon">
					<div>
						<h3>{{ t('health', 'PayPal') }}</h3>
						<p>{{ t('health', 'Make a donation via PayPal.') }}</p>
					</div>
				</div>
				<NcButton
					:aria-label="t('health', 'Donate with PayPal (opens external site)')"
					:href="paypalUrl"
					target="_blank"
					:text="t('health', 'Donate with PayPal')"
					variant="primary" />
			</section>

			<section :aria-label="t('health', 'Bank transfer')" class="support-dialog__option">
				<div class="support-dialog__option-heading">
					<NcIconSvgWrapper :path="iconPaths.bankOutline" />
					<div>
						<h3>{{ t('health', 'Bank transfer') }}</h3>
						<p>{{ t('health', 'Bank transfer details can be requested personally by email.') }}</p>
						<a class="support-dialog__email" href="mailto:support@datenangebot.de">support@datenangebot.de</a>
					</div>
				</div>
				<NcButton
					:aria-label="t('health', 'Request bank details by email')"
					:href="bankTransferMailto"
					:text="t('health', 'Request bank details')" />
			</section>

			<p class="support-dialog__disclaimer">
				{{ t('health', 'Support does not buy feature priority, guaranteed support, or access to private data.') }}
			</p>
		</div>
		<template #actions>
			<ModalActions>
				<NcButton :text="t('health', 'Close')" @click="emit('update:open', false)" />
			</ModalActions>
		</template>
	</NcDialog>
</template>

<style scoped>
.support-dialog {
	display: grid;
	gap: calc(3 * var(--default-grid-baseline));
	max-width: 38rem;
	padding-bottom: calc(2 * var(--default-grid-baseline));
}

.support-dialog__modal :deep(.dialog__name) {
	padding-block-start: calc(2 * var(--default-grid-baseline));
	margin-block-end: calc(4 * var(--default-grid-baseline));
}

.support-dialog__introduction { display: grid; gap: calc(2 * var(--default-grid-baseline)); }

.support-dialog p { margin: 0; }

.support-dialog__option {
	display: grid;
	gap: calc(2 * var(--default-grid-baseline));
	padding-top: calc(3 * var(--default-grid-baseline));
	border-top: 1px solid var(--color-border);
}

.support-dialog__option-heading { display: flex; gap: calc(2 * var(--default-grid-baseline)); }

.support-dialog__option-heading > :first-child {
	flex: 0 0 32px;
	width: 32px;
	height: 32px;
	color: var(--color-text-maxcontrast);
}

.support-dialog__option-heading h3 { margin: 0 0 var(--default-grid-baseline); }

.support-dialog__option-heading p { color: var(--color-text-maxcontrast); }

.support-dialog__option :deep(.button-vue) { justify-self: end; }

.support-dialog__paypal-icon { object-fit: contain; }

.support-dialog__email {
	display: inline-block;
	margin-top: var(--default-grid-baseline);
}

.support-dialog__disclaimer {
	padding-top: calc(3 * var(--default-grid-baseline));
	border-top: 1px solid var(--color-border);
	color: var(--color-text-maxcontrast);
	font-size: var(--default-font-size);
}

@media (max-width: 480px) {
	.support-dialog__option :deep(.button-vue) { justify-self: stretch; }
}
</style>
