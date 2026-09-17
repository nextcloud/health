<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import { iconPaths } from '../../icons.ts'

withDefaults(defineProps<{
	variant?: 'secondary' | 'tertiary'
	exporting?: 'pdf' | 'csv' | null
}>(), {
	variant: 'tertiary',
	exporting: null,
})

const emit = defineEmits<{
	edit: []
	delete: []
	export: [format: 'pdf' | 'csv']
}>()
</script>

<template>
	<NcActions
		:aria-label="t('health', 'Saved Statistics view actions')"
		force-menu
		:variant="variant">
		<NcActionButton @click.stop="emit('edit')">
			<template #icon>
				<NcIconSvgWrapper :path="iconPaths.pencil" />
			</template>
			{{ t('health', 'Edit') }}
		</NcActionButton>
		<NcActionButton @click.stop="emit('delete')">
			<template #icon>
				<NcIconSvgWrapper :path="iconPaths.delete" />
			</template>
			{{ t('health', 'Delete') }}
		</NcActionButton>
		<NcActionButton :aria-busy="exporting === 'pdf'" :disabled="exporting !== null" @click.stop="emit('export', 'pdf')">
			<template #icon>
				<NcLoadingIcon v-if="exporting === 'pdf'" /><NcIconSvgWrapper v-else :path="iconPaths.download" />
			</template>
			{{ t('health', 'Export to PDF') }}
		</NcActionButton>
		<NcActionButton :aria-busy="exporting === 'csv'" :disabled="exporting !== null" @click.stop="emit('export', 'csv')">
			<template #icon>
				<NcLoadingIcon v-if="exporting === 'csv'" /><NcIconSvgWrapper v-else :path="iconPaths.download" />
			</template>
			{{ t('health', 'Export to CSV') }}
		</NcActionButton>
	</NcActions>
</template>
