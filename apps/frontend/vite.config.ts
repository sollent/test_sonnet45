import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'
import { visualizer } from 'rollup-plugin-visualizer'
import viteCompression from 'vite-plugin-compression'

// https://vitejs.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const isProduction = mode === 'production'

  return {
    plugins: [
      vue(),

      // Gzip compression for production
      isProduction && viteCompression({
        verbose: true,
        disable: false,
        threshold: 10240, // 10kb
        algorithm: 'gzip',
        ext: '.gz',
        deleteOriginFile: false
      }),

      // Brotli compression (better than gzip)
      isProduction && viteCompression({
        verbose: true,
        disable: false,
        threshold: 10240,
        algorithm: 'brotliCompress',
        ext: '.br',
        deleteOriginFile: false
      }),

      // Bundle analyzer (только для анализа - раскомментировать при необходимости)
      // isProduction && visualizer({
      //   filename: 'dist/stats.html',
      //   open: false,
      //   gzipSize: true,
      //   brotliSize: true
      // })
    ].filter(Boolean),

    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url))
      }
    },

    server: {
      port: 3000,
      proxy: {
        '/api': {
          target: env.VITE_API_URL || 'http://localhost:8089',
          changeOrigin: true
        }
      }
    },

    build: {
      target: 'es2020',
      minify: 'terser',
      terserOptions: {
        compress: {
          drop_console: isProduction, // Убрать console.log в production
          drop_debugger: true,        // Убрать debugger
          pure_funcs: isProduction ? ['console.info', 'console.debug'] : []
        },
        mangle: {
          safari10: true
        }
      },

      // CSS Code Splitting
      cssCodeSplit: true,

      // Chunk size warning limit
      chunkSizeWarningLimit: 500,

      rollupOptions: {
        output: {
          // Manual Chunk Splitting для оптимального кеширования
          manualChunks: {
            // Vendor chunks
            'vue-vendor': ['vue', 'vue-router', 'pinia'],
            'primevue-vendor': [
              'primevue/config',
              'primevue/toastservice',
              'primevue/confirmationservice',
              'primevue/ripple'
            ],
            'primevue-components': [
              'primevue/autocomplete',
              'primevue/badge',
              'primevue/button',
              'primevue/calendar',
              'primevue/card',
              'primevue/checkbox',
              'primevue/chip',
              'primevue/chips',
              'primevue/confirmdialog',
              'primevue/dialog',
              'primevue/divider',
              'primevue/dropdown',
              'primevue/fileupload',
              'primevue/image',
              'primevue/inputnumber',
              'primevue/inputswitch',
              'primevue/inputtext',
              'primevue/menu',
              'primevue/message',
              'primevue/multiselect',
              'primevue/organizationchart',
              'primevue/paginator',
              'primevue/password',
              'primevue/progressbar',
              'primevue/sidebar',
              'primevue/skeleton',
              'primevue/tag',
              'primevue/textarea',
              'primevue/toast',
              'primevue/togglebutton'
            ],
            'echarts-vendor': ['echarts/core', 'vue-echarts'],
            'utils': ['axios', 'date-fns', '@vueuse/core', 'zod']
          },

          // Naming для долгосрочного кеширования
          chunkFileNames: 'js/[name]-[hash].js',
          entryFileNames: 'js/[name]-[hash].js',
          assetFileNames: (assetInfo) => {
            const info = assetInfo.name?.split('.') || []
            const ext = info[info.length - 1]

            if (/\.(png|jpe?g|gif|svg|webp|avif)$/.test(assetInfo.name || '')) {
              return `images/[name]-[hash][extname]`
            }
            if (/\.(woff2?|eot|ttf|otf)$/.test(assetInfo.name || '')) {
              return `fonts/[name]-[hash][extname]`
            }
            if (ext === 'css') {
              return `css/[name]-[hash][extname]`
            }
            return `assets/[name]-[hash][extname]`
          }
        }
      },

      // Sourcemap только для staging
      sourcemap: env.VITE_SOURCEMAP === 'true',

      // Report compressed size
      reportCompressedSize: true
    },

    // Оптимизация зависимостей
    optimizeDeps: {
      include: [
        'vue',
        'vue-router',
        'pinia',
        'axios',
        '@vueuse/core',
        'vue-i18n'
      ],
      exclude: ['@vue/test-utils']
    },

    test: {
      globals: true,
      environment: 'happy-dom',
      setupFiles: ['./src/tests/setup.ts'],
      exclude: ['node_modules/**', 'e2e/**', 'dist/**'],
      coverage: {
        provider: 'v8',
        reporter: ['text', 'json', 'html'],
        exclude: [
          'node_modules/',
          'src/tests/',
          '**/*.spec.ts',
          '**/*.test.ts',
          '**/types/**',
          '**/*.d.ts'
        ]
      }
    }
  }
})

