import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import { resolve } from 'node:path';

const rootDir = import.meta.dirname;

// React islands for the PHP pages. The build lands in public/build with a manifest that
// App\Libraries\Vite reads to print the hashed file names; `npm run dev` serves the same
// entry with hot reload when VITE_DEV_SERVER is set in .env.
export default defineConfig({
  plugins: [react(), tailwindcss()],
  publicDir: false,
  base: '/build/',
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: { islands: resolve(rootDir, 'resources/js/main.tsx') },
    },
  },
  resolve: {
    alias: { '@': resolve(rootDir, 'resources/js') },
  },
  server: {
    port: 5173,
    strictPort: true,
    cors: true,
  },
});
