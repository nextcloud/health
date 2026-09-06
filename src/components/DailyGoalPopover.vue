<script setup lang="ts">
import type { GoalProgress, GoalTarget } from '../api/goals.ts'

import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import NcProgressBar from '@nextcloud/vue/components/NcProgressBar'
import GoalIndicator from './GoalIndicator.vue'
import MetricIcon from './MetricIcon.vue'
import { comparatorLabel, formatGoalValue, getGoalStatusLabel, getGoalTargetLabel, getGoalTargetMetricKey, getProgressLabel, goalProgressPercentage, goalTargetByKey } from '../goals.ts'

const props = defineProps<{ progresses: GoalProgress[], targets: GoalTarget[] }>()
const buttonStatus = computed<'tertiary' | 'success' | 'error'>(() => {
	if (props.progresses.some((progress) => progress.status === 'exceeded')) {
		return 'error'
	}
	if (props.progresses.length > 0 && props.progresses.every((progress) => progress.status === 'reached' || progress.status === 'within_limit')) {
		return 'success'
	}
	return 'tertiary'
})
const accessibleLabel = computed(() => {
	if (buttonStatus.value === 'success') {
		return t('health', 'Goal reached')
	}
	if (buttonStatus.value === 'error') {
		return t('health', 'Goal exceeded')
	}
	return t('health', 'Goal')
})

function targetFor(progress: GoalProgress): GoalTarget | undefined {
	return goalTargetByKey(props.targets, progress.targetKey)
}

function progressValue(progress: GoalProgress): number {
	return goalProgressPercentage(progress)
}
</script>

<template>
	<NcPopover popup-role="dialog">
		<template #trigger>
			<NcButton :aria-label="accessibleLabel"
				:title="accessibleLabel"
				:variant="buttonStatus">
				<template #icon>
					<GoalIndicator />
				</template>
			</NcButton>
		</template>
		<div class="daily-goal-popover" role="dialog">
			<section v-for="progress in progresses" :key="progress.goalId" class="daily-goal-popover__goal">
				<strong>
					<MetricIcon :metric-key="getGoalTargetMetricKey(progress.targetKey)" />
					{{ getGoalTargetLabel(progress.targetKey) }}
				</strong>
				<p v-if="targetFor(progress)">
					{{ comparatorLabel(progress.comparator, targetFor(progress)) }} {{ formatGoalValue(progress.targetValue, targetFor(progress)!) }}
				</p>
				<div v-if="targetFor(progress)" class="daily-goal-popover__progress">
					<span>{{ progress.period === 'long_term' ? t('health', 'Long-term') : t('health', 'Today') }}</span>
					<strong>{{ getProgressLabel(progress, targetFor(progress)!) }}</strong>
					<div v-if="progress.progressRatio !== null"
						:aria-label="t('health', '{percent} percent of the goal reached', { percent: progressValue(progress) })"
						role="img">
						<NcProgressBar :aria-hidden="true" :value="progressValue(progress)" />
					</div>
				</div>
				<div v-if="progress.status === 'exceeded'" class="daily-goal-popover__status">
					<p>{{ t('health', 'Status') }}</p>
					<strong>{{ getGoalStatusLabel(progress) }}</strong>
				</div>
				<strong v-else-if="progress.status === 'reached'" class="daily-goal-popover__reached">
					{{ progress.period === 'long_term' ? t('health', 'Goal reached') : t('health', 'Reached today') }}
				</strong>
			</section>
		</div>
	</NcPopover>
</template>

<style scoped>
.daily-goal-popover {
	min-width: min(20rem, calc(100vw - 2rem));
	padding: 12px;
}

.daily-goal-popover__goal + .daily-goal-popover__goal {
	margin-top: 16px;
	padding-top: 16px;
	border-top: 1px solid var(--color-border-dark);
}

.daily-goal-popover p {
	margin: 10px 0 2px;
	color: var(--color-text-maxcontrast);
}

.daily-goal-popover__goal > strong:first-child {
	display: flex;
	align-items: center;
	gap: var(--health-metric-title-gap, calc(3 * var(--default-grid-baseline)));
}

.daily-goal-popover__progress { display: grid; gap: 6px; margin-top: 10px; }

.daily-goal-popover__status,
.daily-goal-popover__reached { display: block; margin-top: 10px; }
</style>
