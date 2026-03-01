import { defineConfig } from 'vite';

export default defineConfig({
  publicDir: false,
  build: {
    lib: {
      entry: 'build/main.js',
      formats: ['iife'],
      name: 'PxmEditor',
      fileName: () => 'editor-bundle.js',
    },
    outDir: 'public/js',
    emptyOutDir: false,
    cssCodeSplit: false, 
  },
});