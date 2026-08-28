import type { InjectionKey, Ref } from 'vue'
import type { HealthConfiguration } from './api/configuration.ts'

export const healthConfigurationKey: InjectionKey<Ref<HealthConfiguration | null>> = Symbol('healthConfiguration')
