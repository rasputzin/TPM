import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// Build-ul e servit direct din wp-content/plugins/.../admin-app/dist/ de către
// PHP (AdminPage::enqueue), nu dintr-un CDN cu cache pe hash — nume de fișiere
// fixe, cache-busting făcut prin versiunea pluginului la enqueue în WordPress.
export default defineConfig({
  plugins: [react()],
  base: '',
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    rollupOptions: {
      output: {
        entryFileNames: 'admin-app.js',
        chunkFileNames: 'admin-app-[name].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.names?.some((n) => n.endsWith('.css'))) {
            return 'admin-app.css'
          }
          return 'assets/[name][extname]'
        },
      },
    },
  },
})
