import { resolve } from 'path'
import { defineConfig } from 'vite'

export default defineConfig({
	build: {
		assetsDir: 'js',
		cssCodeSplit: false,
		emptyOutDir: false,
		outDir: '.',
		sourcemap: true,
		target: 'es2022',
		rollupOptions: { input: resolve('src/pwa/app.ts'), output: { assetFileNames: (asset) => asset.name?.endsWith('.css') === true ? 'css/health-pwa.css' : 'js/health-pwa-[name][extname]', entryFileNames: 'js/health-pwa.mjs', inlineDynamicImports: true } },
	},
})
