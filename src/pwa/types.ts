export type MetricCategory = 'journal' | 'measurement' | 'daily_value'
export type MetricValueType = 'scale' | 'event' | 'numeric' | 'counter' | 'composite'

export interface MetricDefinition {
	metricKey: string
	category: MetricCategory
	valueType: MetricValueType
	minimum: number | null
	maximum: number | null
	allowedOptions: string[] | null
	canonicalUnit: string | null
	supportedUnits: string[]
}

export interface MetricConfiguration {
	enabled: boolean
	displayUnit: string | null
}

export interface AccountConfiguration {
	key: 'primary'
	serverUrl: string
	apiBaseUrl: string
	loginName: string
	appPassword: string
	locale: string
	timezone: string
	metrics: MetricDefinition[]
	configuration: Record<string, MetricConfiguration>
	authState: 'connected' | 'expired'
}

interface OperationBase {
	operationId: string
	metricKey: string
	createdAt: string
	state: 'pending' | 'sending' | 'failed-retryable'
}

export type PendingOperation = OperationBase & (
	| { kind: 'journal', numericValue: number | null, optionValue: string | null, recordedAt: string }
	| { kind: 'measurement', numericValue: number | null, values: { systolic: number, diastolic: number } | null, unit: string | null, recordedAt: string }
	| { kind: 'daily_value', localDate: string, numericValue: number, unit: string | null }
	| { kind: 'daily_increment', localDate: string, delta: number, unit: string | null, preparedNumericValue: number | null }
)

export interface LoginStart {
	login: string
	poll: { token: string, endpoint: string }
}
