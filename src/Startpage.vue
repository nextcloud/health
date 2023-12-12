<template>
	<div>
		<NcEmptyContent :title="t('health', 'Health')"
			:description="t('health', 'Track your health privately.')"
		>
			<template #icon>
				<IconHealth />
			</template>
		</NcEmptyContent>
	</div>
</template>

<script>
import { NcEmptyContent } from '@nextcloud/vue'
import IconHealth from 'vue-material-design-icons/HeartPulse.vue'

export default {
	components: {
		NcEmptyContent,
		IconHealth,
	},
}
</script>
