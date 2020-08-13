# vue module

## persons
	content
	navigation
	sidebar
	sidebartab

## weight
	content
	sidebartab

## breaks
	content
	sidebartab

## tracking
	content
	sidebartab


# data

## top-level

loading: boolean
showSidebar: boolean
activePersonId: id
activeModule: string
notifications: array
	type: {good, hint, warn, error} -> green, blue, orange, red
	text: string

persons
	: array
		id: id
		name: string
		notifications: array
			type: {red, orange, green, blue}
			text: string
		age: number
		sex: {female, male}
		size: number
		enabledModules: array
			weight: boolean
			breaks: boolean
			tracking: boolean
		weight: object
			unit: string
			weightTarget: number
			weightTargetInitialWeight: number
			measurementName: string

## persons

### persons -> navigation
	showNewPersonForm: boolean
	menuOpenPersonId: id

## weight
	items: array
		date: string
		weight: number
		measurement: number
		bodyfat: number