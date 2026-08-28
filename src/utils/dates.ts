export interface DateRange {
	from: string
	to: string
}

export function startOfLocalDay(day: Date): Date {
	return new Date(day.getFullYear(), day.getMonth(), day.getDate())
}

export function getLocalDayRange(day: Date): DateRange {
	const from = startOfLocalDay(day)
	const to = new Date(from.getFullYear(), from.getMonth(), from.getDate() + 1)

	return {
		from: from.toISOString(),
		to: to.toISOString(),
	}
}

export function addLocalDays(day: Date, amount: number): Date {
	return new Date(day.getFullYear(), day.getMonth(), day.getDate() + amount)
}

export function localDateKey(day: Date): string {
	const year = String(day.getFullYear()).padStart(4, '0')
	const month = String(day.getMonth() + 1).padStart(2, '0')
	const date = String(day.getDate()).padStart(2, '0')
	return `${year}-${month}-${date}`
}

export function parseLocalDateKey(value: string): Date | null {
	const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value)
	if (match === null) {
		return null
	}

	const year = Number(match[1])
	const month = Number(match[2])
	const day = Number(match[3])
	const date = new Date(year, month - 1, day)
	if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) {
		return null
	}

	return startOfLocalDay(date)
}

export function recordedAtForLocalDay(day: Date): string {
	const now = new Date()
	return new Date(
		day.getFullYear(),
		day.getMonth(),
		day.getDate(),
		now.getHours(),
		now.getMinutes(),
		now.getSeconds(),
	).toISOString()
}
