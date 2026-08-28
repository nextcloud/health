import { recommended } from '@nextcloud/eslint-config'

export default [
	...recommended,
	{
		name: 'health/rules',
		files: ['**/*.{js,mjs,ts,vue}'],
		rules: {
			'jsdoc/require-jsdoc': 'off',
		},
	},
	{
		name: 'health/vue',
		files: ['**/*.vue'],
		rules: {
			'vue/attribute-hyphenation': 'off',
			'vue/first-attribute-linebreak': 'off',
		},
	},
]
