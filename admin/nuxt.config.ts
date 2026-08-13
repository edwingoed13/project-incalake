// https://nuxt.com/docs/api/configuration/nuxt-config
import tailwindcss from "@tailwindcss/vite"

export default defineNuxtConfig({
  ssr: false,

  // Recover stale admin clients after a deploy without a manual hard-refresh:
  // reload the SPA when a lazy chunk 404s (old build replaced), and poll the
  // build manifest every 5 min so an open admin tab picks up new versions.
  experimental: {
    appManifest: true,
    checkOutdatedBuildInterval: 1000 * 60 * 5,
    emitRouteChunkError: 'automatic-immediate',
  },

  devtools: { enabled: true },

  css: ['~/assets/css/main.css'],

  // Two separate `vite:` blocks lived here before; the second silently
  // overrode the first (so server.allowedHosts never actually applied).
  // Merged into one. esbuild.drop strips console.* + debugger from the
  // production bundle so admin-side debugging logs don't leak in the
  // built JS (admin handles bookings / customer data).
  vite: {
    server: {
      allowedHosts: true,
    },
    plugins: [
      tailwindcss(),
    ],
    esbuild: {
      drop: process.env.NODE_ENV === 'production' ? ['console', 'debugger'] : [],
    },
  },

  modules: [
    '@nuxt/ui',
    '@pinia/nuxt',
    '@nuxtjs/i18n'
  ],

  i18n: {
    locales: ['es', 'en', 'fr'],
    defaultLocale: 'es',
    strategy: 'no_prefix' // En el admin no importa prefixar URLs por SEO
  },

  app: {
    head: {
      link: [
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        // Solo la familia que se usa de verdad. Inter se cargaba sin que
        // ninguna regla la referenciara, y Material Symbols cayó al migrar
        // sus 56 iconos a Lucide (un solo sistema de iconos en todo el admin).
        { rel: 'stylesheet', href: 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap' }
      ]
    }
  },

  runtimeConfig: {
    public: {
      apiUrl: process.env.NUXT_PUBLIC_API_URL || 'http://localhost:8000/api',
      googleMapsApiKey: process.env.NUXT_PUBLIC_GOOGLE_MAPS_API_KEY || ''
    }
  },

  compatibilityDate: '2024-11-01',
})
