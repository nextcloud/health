<script setup lang="ts">
import type { HealthConfiguration, MetricConfiguration } from '../api/configuration.ts'
import type { AllMetricKey, Unit } from '../metrics.ts'

import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed, inject, onBeforeUnmount, onMounted, ref } from 'vue'
import NcAppSettingsSection from '@nextcloud/vue/components/NcAppSettingsSection'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcRadioGroup from '@nextcloud/vue/components/NcRadioGroup'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import MetricIcon from './MetricIcon.vue'
import { getConfiguration, getEnabledMetricKeys, updateConfiguration } from '../api/configuration.ts'
import { healthConfigurationKey } from '../configurationContext.ts'
import { ALL_METRIC_KEYS, DAILY_VALUE_METRIC_KEYS, getMetricLabel, getMetricUnits, getUnitLabel, MEASUREMENT_METRIC_KEYS, METRIC_KEYS } from '../metrics.ts'

type Profile = HealthConfiguration['profile']
type MetricFlag = 'enabled' | 'checkInEnabled' | 'checkOutEnabled'

const configuration = inject(healthConfigurationKey, ref<HealthConfiguration | null>(null))
const initialLoading = ref(true)
const savingMetrics = ref<Partial<Record<AllMetricKey, boolean>>>({})
const savingProfile = ref(false)
const savingIntegration = ref(false)
const height = ref('')
const heightUnit = ref<'cm' | 'in'>('cm')
const dateOfBirth = ref('')
const growthReferenceSex = ref<'female' | 'male' | ''>('')
const savedProfile = ref<Profile | null>(null)
const groups = [
	{ title: t('health', 'Journal metrics'), keys: METRIC_KEYS },
	{ title: t('health', 'Measurements'), keys: MEASUREMENT_METRIC_KEYS },
	{ title: t('health', 'Daily values'), keys: DAILY_VALUE_METRIC_KEYS },
] as const
const enabledUnitMetrics = computed(() => getEnabledMetricKeys(configuration.value, ALL_METRIC_KEYS).filter((key) => {
	return getMetricUnits(key).length > 0
}))

const metricRequestVersions = new Map<AllMetricKey, number>()
let searchRequestVersion = 0
let profileTimer: number | undefined
let profileRevision = 0
let profileRequestInFlight = false
let profileSaveQueued = false

function formatHeight(heightCm: number | null, unit: 'cm' | 'in'): string {
	if (heightCm === null) {
		return ''
	}

	const value = unit === 'in' ? heightCm / 2.54 : heightCm
	return String(Number(value.toFixed(2)))
}

function heightInCentimetres(value: string, unit: 'cm' | 'in'): number | null | undefined {
	if (value.trim() === '') {
		return null
	}

	const parsed = Number(value)
	if (!Number.isFinite(parsed)) {
		return undefined
	}

	return unit === 'in' ? parsed * 2.54 : parsed
}

function applyProfile(profile: Profile) {
	if (configuration.value !== null) {
		configuration.value.profile = { ...profile }
	}
	savedProfile.value = { ...profile }
	heightUnit.value = profile.heightDisplayUnit
	height.value = formatHeight(profile.heightCm, profile.heightDisplayUnit)
	dateOfBirth.value = profile.dateOfBirth ?? ''
	growthReferenceSex.value = profile.growthReferenceSex ?? ''
}

async function load() {
	if (configuration.value !== null) {
		applyProfile(configuration.value.profile)
		initialLoading.value = false
		return
	}

	initialLoading.value = true
	try {
		configuration.value = await getConfiguration()
		applyProfile(configuration.value.profile)
	} catch {
		showError(t('health', 'Settings could not be loaded.'))
	} finally {
		initialLoading.value = false
	}
}

function metricIsSaving(metricKey: AllMetricKey): boolean {
	return savingMetrics.value[metricKey] === true
}

async function updateMetric(metricKey: AllMetricKey, patch: Partial<MetricConfiguration>) {
	if (configuration.value === null) {
		return
	}

	const previous = { ...configuration.value.metrics[metricKey] }
	const next = { ...previous, ...patch }
	configuration.value.metrics[metricKey] = next
	const version = (metricRequestVersions.get(metricKey) ?? 0) + 1
	metricRequestVersions.set(metricKey, version)
	savingMetrics.value = { ...savingMetrics.value, [metricKey]: true }

	try {
		const canonical = await updateConfiguration({ metrics: { [metricKey]: next } })
		if (metricRequestVersions.get(metricKey) === version && configuration.value !== null) {
			configuration.value.metrics[metricKey] = canonical.metrics[metricKey]
			showSuccess(t('health', 'Settings saved.'))
		}
	} catch {
		if (metricRequestVersions.get(metricKey) === version && configuration.value !== null) {
			configuration.value.metrics[metricKey] = previous
			showError(t('health', 'Settings could not be saved.'))
		}
	} finally {
		if (metricRequestVersions.get(metricKey) === version) {
			savingMetrics.value = { ...savingMetrics.value, [metricKey]: false }
		}
	}
}

function updateMetricFlag(metricKey: AllMetricKey, flag: MetricFlag, value: unknown) {
	if (typeof value === 'boolean') {
		void updateMetric(metricKey, { [flag]: value })
	}
}

function updateMetricUnit(metricKey: AllMetricKey, value: unknown) {
	if (typeof value === 'string' && getMetricUnits(metricKey).includes(value as Unit)) {
		void updateMetric(metricKey, { displayUnit: value as Unit })
	}
}

async function updateSearchDailyNotes(value: unknown) {
	if (configuration.value === null || typeof value !== 'boolean') {
		return
	}

	const previous = configuration.value.searchDailyNotes
	configuration.value.searchDailyNotes = value
	const version = ++searchRequestVersion
	savingIntegration.value = true
	try {
		const canonical = await updateConfiguration({ searchDailyNotes: value })
		if (configuration.value !== null && version === searchRequestVersion) {
			configuration.value.searchDailyNotes = canonical.searchDailyNotes
			showSuccess(t('health', 'Settings saved.'))
		}
	} catch {
		if (configuration.value !== null && version === searchRequestVersion) {
			configuration.value.searchDailyNotes = previous
			showError(t('health', 'Settings could not be saved.'))
		}
	} finally {
		if (version === searchRequestVersion) {
			savingIntegration.value = false
		}
	}
}

function scheduleProfileSave() {
	profileRevision++
	if (profileTimer !== undefined) {
		window.clearTimeout(profileTimer)
	}
	profileTimer = window.setTimeout(() => {
		profileTimer = undefined
		void persistProfile()
	}, 800)
}

function updateHeight(value: unknown) {
	if (typeof value !== 'string') {
		return
	}
	height.value = value
	scheduleProfileSave()
}

function updateHeightUnit(value: unknown) {
	if ((value !== 'cm' && value !== 'in') || value === heightUnit.value) {
		return
	}

	const canonical = heightInCentimetres(height.value, heightUnit.value)
	heightUnit.value = value
	if (canonical !== null && canonical !== undefined) {
		height.value = formatHeight(canonical, value)
	}
	scheduleProfileSave()
}

function updateDateOfBirth(value: unknown) {
	if (typeof value === 'string') {
		dateOfBirth.value = value
		scheduleProfileSave()
	}
}

function updateGrowthReferenceSex(value: unknown) {
	if (value === 'female' || value === 'male') {
		growthReferenceSex.value = value
		scheduleProfileSave()
	}
}

function profileMatchesSaved(heightCm: number | null, unit: 'cm' | 'in', savedDateOfBirth: string | null, savedGrowthReferenceSex: 'female' | 'male' | null): boolean {
	if (savedProfile.value === null || savedProfile.value.heightDisplayUnit !== unit || savedProfile.value.heightCm === null || heightCm === null) {
		return savedProfile.value?.heightCm === heightCm
			&& savedProfile.value?.heightDisplayUnit === unit
			&& savedProfile.value?.dateOfBirth === savedDateOfBirth
			&& savedProfile.value?.growthReferenceSex === savedGrowthReferenceSex
	}

	return Math.abs(savedProfile.value.heightCm - heightCm) < 0.000001
		&& savedProfile.value.dateOfBirth === savedDateOfBirth
		&& savedProfile.value.growthReferenceSex === savedGrowthReferenceSex
}

async function persistProfile() {
	if (profileRequestInFlight) {
		profileSaveQueued = true
		return
	}

	const heightCm = heightInCentimetres(height.value, heightUnit.value)
	if (heightCm === undefined) {
		showError(t('health', 'Height must be a number.'))
		return
	}
	const requestedDateOfBirth = dateOfBirth.value === '' ? null : dateOfBirth.value
	const requestedGrowthReferenceSex = growthReferenceSex.value === '' ? null : growthReferenceSex.value
	if (profileMatchesSaved(heightCm, heightUnit.value, requestedDateOfBirth, requestedGrowthReferenceSex)) {
		return
	}

	const revision = profileRevision
	const requestedHeight = height.value.trim() === '' ? null : Number(height.value)
	const requestedUnit = heightUnit.value
	profileRequestInFlight = true
	savingProfile.value = true
	try {
		const canonical = await updateConfiguration({ profile: { height: requestedHeight, heightUnit: requestedUnit, dateOfBirth: requestedDateOfBirth, growthReferenceSex: requestedGrowthReferenceSex } })
		if (revision === profileRevision) {
			applyProfile(canonical.profile)
			showSuccess(t('health', 'Profile saved.'))
		} else {
			savedProfile.value = { ...canonical.profile }
		}
	} catch {
		if (revision === profileRevision && savedProfile.value !== null) {
			applyProfile(savedProfile.value)
			showError(t('health', 'Settings could not be saved.'))
		}
	} finally {
		profileRequestInFlight = false
		savingProfile.value = false
		if (profileSaveQueued) {
			profileSaveQueued = false
			void persistProfile()
		}
	}
}

onMounted(load)
onBeforeUnmount(() => {
	if (profileTimer !== undefined) {
		window.clearTimeout(profileTimer)
	}
})
</script>

<template>
	<NcLoadingIcon v-if="initialLoading" :name="t('health', 'Loading settings')" :size="32" />
	<template v-else-if="configuration">
		<NcAppSettingsSection
			id="metrics"
			class="settings-content__main-section settings-content__main-section--first"
			:description="t('health', 'Choose which health values you want to track and which are included in your Check-in and Check-out.')"
			:name="t('health', 'Metrics')"
			:order="10">
			<div class="settings-content__section settings-content__metrics">
				<div class="settings-content__metric-headings">
					<span /> <span>{{ t('health', 'Enabled') }}</span> <span>{{ t('health', 'Check-in') }}</span> <span>{{ t('health', 'Check-out') }}</span>
				</div>
				<section v-for="group in groups" :key="group.title" class="settings-content__metric-group">
					<h4>{{ group.title }}</h4>
					<div v-for="metricKey in group.keys"
						:key="metricKey"
						:aria-busy="metricIsSaving(metricKey)"
						class="settings-content__metric-row">
						<span class="settings-content__metric-label">
							<MetricIcon :metric-key="metricKey" />
							<span>{{ getMetricLabel(metricKey) }}</span>
						</span>
						<NcCheckboxRadioSwitch
							:model-value="configuration.metrics[metricKey].enabled"
							type="switch"
							:aria-label="t('health', 'Enable {metric}', { metric: getMetricLabel(metricKey) })"
							@update:modelValue="updateMetricFlag(metricKey, 'enabled', $event)" />
						<NcCheckboxRadioSwitch
							:model-value="configuration.metrics[metricKey].checkInEnabled"
							:disabled="!configuration.metrics[metricKey].enabled"
							type="switch"
							:aria-label="t('health', 'Include {metric} in Check-in', { metric: getMetricLabel(metricKey) })"
							@update:modelValue="updateMetricFlag(metricKey, 'checkInEnabled', $event)" />
						<NcCheckboxRadioSwitch
							:model-value="configuration.metrics[metricKey].checkOutEnabled"
							:disabled="!configuration.metrics[metricKey].enabled"
							type="switch"
							:aria-label="t('health', 'Include {metric} in Check-out', { metric: getMetricLabel(metricKey) })"
							@update:modelValue="updateMetricFlag(metricKey, 'checkOutEnabled', $event)" />
					</div>
				</section>
			</div>
		</NcAppSettingsSection>

		<NcAppSettingsSection
			id="profile"
			class="settings-content__main-section"
			:description="t('health', 'Personal information used for calculated values such as BMI.')"
			:name="t('health', 'Profile')"
			:order="20">
			<div :aria-busy="savingProfile" class="settings-content__section settings-content__profile">
				<div class="settings-content__setting-row">
					<label class="settings-content__setting-label" for="health-profile-height">{{ t('health', 'Height') }}</label>
					<div class="settings-content__height-control">
						<NcTextField
							id="health-profile-height"
							:model-value="height"
							:label="t('health', 'Height')"
							label-outside
							inputmode="decimal"
							@update:modelValue="updateHeight" />
						<NcRadioGroup
							:model-value="heightUnit"
							:label="t('health', 'Height unit')"
							class="settings-content__inline-radio-group"
							hide-label
							@update:modelValue="updateHeightUnit">
							<NcCheckboxRadioSwitch value="cm">
								cm
							</NcCheckboxRadioSwitch>
							<NcCheckboxRadioSwitch value="in">
								in
							</NcCheckboxRadioSwitch>
						</NcRadioGroup>
					</div>
				</div>
				<div class="settings-content__setting-row">
					<label class="settings-content__setting-label" for="health-profile-date-of-birth">{{ t('health', 'Date of birth') }}</label>
					<input
						id="health-profile-date-of-birth"
						class="settings-content__date-input"
						:value="dateOfBirth"
						type="date"
						@input="updateDateOfBirth(($event.target as HTMLInputElement).value)">
				</div>
				<div class="settings-content__setting-row">
					<span class="settings-content__setting-label">{{ t('health', 'Sex') }}</span>
					<NcRadioGroup
						:model-value="growthReferenceSex"
						:label="t('health', 'Sex')"
						class="settings-content__inline-radio-group"
						hide-label
						@update:modelValue="updateGrowthReferenceSex">
						<NcCheckboxRadioSwitch value="female">
							{{ t('health', 'Female') }}
						</NcCheckboxRadioSwitch>
						<NcCheckboxRadioSwitch value="male">
							{{ t('health', 'Male') }}
						</NcCheckboxRadioSwitch>
					</NcRadioGroup>
				</div>
				<p class="settings-content__profile-help">
					{{ t('health', 'BMI is calculated from height and weight. For children and adolescents, BMI-for-age information is available only when Health includes a verified WHO reference.') }}
				</p>
			</div>
		</NcAppSettingsSection>

		<NcAppSettingsSection
			id="units"
			class="settings-content__main-section"
			:description="t('health', 'Choose how enabled measurements are entered and displayed. Health stores values internally in canonical units.')"
			:name="t('health', 'Units')"
			:order="30">
			<div class="settings-content__section settings-content__units">
				<p v-if="enabledUnitMetrics.length === 0" class="settings-content__empty-units">
					{{ t('health', 'Enable a measurement or daily value in Metrics to choose units.') }}
				</p>
				<div v-for="metricKey in enabledUnitMetrics"
					:key="metricKey"
					:aria-busy="metricIsSaving(metricKey)"
					class="settings-content__unit-row">
					<span class="settings-content__metric-label">
						<MetricIcon :metric-key="metricKey" />
						<span>{{ getMetricLabel(metricKey) }}</span>
					</span>
					<span v-if="getMetricUnits(metricKey).length === 1" class="settings-content__fixed-unit">{{ getUnitLabel(getMetricUnits(metricKey)[0]) }}</span>
					<NcRadioGroup
						v-else
						:model-value="configuration.metrics[metricKey].displayUnit ?? getMetricUnits(metricKey)[0]"
						:label="t('health', '{metric} unit', { metric: getMetricLabel(metricKey) })"
						class="settings-content__inline-radio-group"
						hide-label
						@update:modelValue="updateMetricUnit(metricKey, $event)">
						<NcCheckboxRadioSwitch v-for="unit in getMetricUnits(metricKey)" :key="unit" :value="unit">
							{{ getUnitLabel(unit) }}
						</NcCheckboxRadioSwitch>
					</NcRadioGroup>
				</div>
			</div>
		</NcAppSettingsSection>

		<NcAppSettingsSection
			id="integration"
			class="settings-content__main-section"
			:description="t('health', 'Connect Health with other Nextcloud features.')"
			:name="t('health', 'Integration')"
			:order="40">
			<div :aria-busy="savingIntegration" class="settings-content__section settings-content__integration">
				<h4>{{ t('health', 'Search') }}</h4>
				<div class="settings-content__setting-row">
					<span class="settings-content__setting-label">{{ t('health', 'Include Daily Notes in Nextcloud search') }}</span>
					<NcCheckboxRadioSwitch
						:model-value="configuration.searchDailyNotes"
						type="switch"
						:aria-label="t('health', 'Include Daily Notes in Nextcloud search')"
						@update:modelValue="updateSearchDailyNotes" />
				</div>
				<p class="settings-content__integration-help">
					{{ t('health', 'Allows Nextcloud search to find words in your Daily Notes. Only your own notes are searched.') }}
				</p>
			</div>
		</NcAppSettingsSection>
	</template>
</template>

<style scoped>
.settings-content__main-section--first { padding-block-start: calc(3 * var(--default-grid-baseline)); }

.settings-content__section {
	padding-inline: var(--app-settings-section-text-offset);
}

.settings-content__metrics,
.settings-content__units,
.settings-content__profile {
	display: flex;
	flex-direction: column;
	gap: 16px;
}

.settings-content__integration {
	display: flex;
	flex-direction: column;
	gap: 0;
}

.settings-content__metric-headings,
.settings-content__metric-row {
	display: grid;
	grid-template-columns: minmax(12rem, 1fr) repeat(3, minmax(4rem, max-content));
	align-items: center;
	gap: 12px;
}

.settings-content__metric-headings {
	color: var(--color-text-maxcontrast);
	font-size: var(--font-size-small);
}

.settings-content__metric-group h4 {
	margin: 0 0 8px;
	font-size: var(--default-font-size);
}

.settings-content__metric-row { min-height: var(--default-clickable-area); }

.settings-content__metric-label {
	display: grid;
	grid-template-columns: 24px minmax(0, 1fr);
	align-items: center;
	min-width: 0;
	gap: var(--health-metric-title-gap, calc(3 * var(--default-grid-baseline)));
}

.settings-content__setting-row {
	display: grid;
	grid-template-columns: minmax(10rem, 1fr) minmax(0, 1fr);
	align-items: center;
	gap: 16px;
	min-height: var(--default-clickable-area);
}

.settings-content__profile .settings-content__setting-row {
	grid-template-columns: minmax(9rem, 30%) minmax(0, 70%);
}

.settings-content__setting-label { font-weight: var(--font-weight-element, normal); }

.settings-content__height-control {
	display: flex;
	align-items: center;
	flex-wrap: wrap;
	min-width: 0;
	gap: 12px;
}

.settings-content__height-control :deep(.input-field) { width: min(100%, 10rem); }

.settings-content__date-input {
	box-sizing: border-box;
	width: min(100%, 12rem);
	min-height: var(--default-clickable-area);
	padding-inline: var(--border-radius-element);
	color: var(--color-main-text);
	background: var(--color-main-background);
	border: 1px solid var(--color-border-dark);
	border-radius: var(--border-radius-element);
}

.settings-content__profile-help {
	margin: 0;
	color: var(--color-text-maxcontrast);
}

.settings-content__integration h4 {
	margin: calc(5 * var(--default-grid-baseline)) 0 var(--default-grid-baseline);
	font-size: var(--default-font-size);
}

.settings-content__integration-help {
	max-width: 42rem;
	margin: var(--default-grid-baseline) 0 0;
	color: var(--color-text-maxcontrast);
}

.settings-content__unit-row {
	display: grid;
	grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
	align-items: center;
	gap: 16px;
	min-height: var(--default-clickable-area);
}

.settings-content__fixed-unit { color: var(--color-text-maxcontrast); }

.settings-content__empty-units {
	margin: 0;
	color: var(--color-text-maxcontrast);
}

:deep(.settings-content__inline-radio-group [class*="radioGroup_checkboxRadioContainer"]) {
	display: flex;
	flex-wrap: wrap;
	gap: 4px;
}

@media (max-width: 600px) {
	.settings-content__metric-headings { display: none; }
	.settings-content__metric-row { grid-template-columns: minmax(0, 1fr) repeat(3, max-content); }
	.settings-content__setting-row,
	.settings-content__unit-row { grid-template-columns: 1fr; gap: 4px; }
}
</style>
