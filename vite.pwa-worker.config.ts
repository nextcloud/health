import { resolve } from 'path'
import { defineConfig } from 'vite'

export default defineConfig({
	build: {
		emptyOutDir: false,
		outDir: '.',
		sourcemap: true,
		target: 'es2022',
		rollupOptions: { input: resolve('src/pwa/service-worker.ts'), output: { entryFileNames: 'js/health-pwa-service-worker.mjs', inlineDynamicImports: true } },
	},
})
