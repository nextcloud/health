/**
 * Parses the local Weight input without allowing JavaScript's partial-number
 * coercion to truncate comma decimals such as "82,4" to "82".
 *
 * @param input User-entered Weight value.
 * @return Canonical numeric value, or null for malformed input.
 */
export function normalizeWeightInput(input: string): number | null {
	const normalized = input.trim()
	if (!/^[+-]?\d+(?:[.,]\d+)?$/.test(normalized)) {
		return null
	}

	const value = Number(normalized.replace(',', '.'))
	return Number.isFinite(value) ? value : null
}
