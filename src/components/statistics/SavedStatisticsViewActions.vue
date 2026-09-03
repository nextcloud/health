<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import { iconPaths } from '../../icons.ts'

withDefaults(defineProps<{
	variant?: 'secondary' | 'tertiary'
}>(), {
	variant: 'tertiary',
})

const emit = defineEmits<{
	edit: []
	delete: []
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
	</NcActions>
</template>
