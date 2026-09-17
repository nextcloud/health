import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import { test } from 'node:test'

test('PDF export defines a readable statistics typography hierarchy', async () => {
	const source = await readFile(new URL('../../src/statisticsExport.ts', import.meta.url), 'utf8')
	assert.match(source, /PDF_TEXT_STYLES/)
	assert.match(source, /title: \{ size: 20/)
	assert.match(source, /section: \{ size: 15/)
	assert.match(source, /group: \{ size: 11/)
	assert.match(source, /measurement: \{ size: 8\.5/)
	assert.match(source, /pdf\.addImage\(chart, 'PNG'/)
	assert.match(source, /'Average values'/)
})
