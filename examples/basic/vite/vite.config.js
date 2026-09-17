import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig(({ command }) => ({
  plugins: [react(), tailwindcss()],
  // Built assets are served by Yii from web/dist; the dev server serves them from its root.
  base: command === 'build' ? '/dist/' : '/',
  build: {
    outDir: '../web/dist',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: 'src/main.jsx',
    },
  },
  server: {
    port: 5173,
    strictPort: true,
    // Makes URLs of assets imported from CSS/JS point at the dev server instead of the PHP server.
    origin: 'http://localhost:5173',
  },
}))
