import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
  build: {
    lib: {
      entry: resolve(__dirname, 'src/widget.ts'),
      name: 'WACChatWidget',
      fileName: (format) => `widget.${format}.js`
    },
    rollupOptions: {
      external: [],
      output: [
        {
          format: 'es',
          entryFileNames: 'widget.min.js',
          inlineDynamicImports: true
        },
        {
          format: 'iife',
          entryFileNames: 'widget.iife.js',
          name: 'WACChatWidget',
          inlineDynamicImports: true
        }
      ]
    },
    target: 'es2015',
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true,
        drop_debugger: true
      }
    },
    sourcemap: false,
    chunkSizeWarningLimit: 50000
  },
  define: {
    'process.env.NODE_ENV': JSON.stringify(process.env.NODE_ENV || 'development')
  }
});
