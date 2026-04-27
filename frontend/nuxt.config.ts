export default defineNuxtConfig({
  devtools: { enabled: false },
  ssr: true,

  vite: {
    server: {
      allowedHosts: true,
    },
  },

  srcDir: 'app',

  modules: [
    '@nuxt/image',
    '@pinia/nuxt',
    '@vueuse/nuxt',
    '@nuxtjs/i18n',
    '@nuxtjs/tailwindcss',
    '@nuxtjs/seo',
    '@nuxtjs/sitemap',
  ],

  css: ['~/assets/css/main.css'],

  /* Removed manual PostCSS config because it was incorrectly nested and Tailwind module covers it */

  runtimeConfig: {
    // Private keys (solo servidor)
    public: {
      // Public keys (cliente y servidor)
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8001/api',
      storageBase: process.env.NUXT_PUBLIC_STORAGE_BASE || 'http://localhost:8001/storage',
      culqiPublicKey: process.env.NUXT_PUBLIC_CULQI_KEY,
      paypalClientId: process.env.NUXT_PUBLIC_PAYPAL_CLIENT_ID,
      googleMapsKey: process.env.NUXT_PUBLIC_GOOGLE_MAPS_KEY,
    }
  },

  app: {
    head: {
      titleTemplate: '%s - Incalake Tours',
      htmlAttrs: { lang: 'es' },
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        { name: 'format-detection', content: 'telephone=no' },

        // Meta tags globales para SEO
        { name: 'author', content: 'Incalake Tours' },
        { name: 'robots', content: 'index, follow' },
        { name: 'theme-color', content: '#4f46e5' },

        // Open Graph globales
        { property: 'og:site_name', content: 'Incalake Tours' },
        { property: 'og:type', content: 'website' },
        { property: 'og:locale', content: 'es_PE' },
        { property: 'og:image', content: 'https://incalake.com/og-image.jpg' },
        { property: 'og:image:width', content: '1200' },
        { property: 'og:image:height', content: '630' },

        // Twitter Card globales
        { name: 'twitter:card', content: 'summary_large_image' },
        { name: 'twitter:site', content: '@incalaketours' },
        { name: 'twitter:creator', content: '@incalaketours' },
      ],
      link: [
        { rel: 'icon', type: 'image/x-icon', href: '/favicon.ico' },
        { rel: 'canonical', href: 'https://incalake.com' },
        { rel: 'stylesheet', href: 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' }
      ],
      script: [
        {
          src: 'https://maps.googleapis.com/maps/api/js?key=AIzaSyCC2CAVXwufsdT5TX3UPk7hZ3HHw3NZl_c&libraries=places&loading=async',
          defer: true,
          async: true
        }
      ]
    }
  },

  nitro: {
    compressPublicAssets: true,
    // Pre-render páginas estáticas para SEO
    prerender: {
      crawlLinks: false,
      routes: []
    }
  },

  // SSR activado para SEO (IPC fix applied via non-blocking data fetching)

  // SSR + SPA + SWR Híbrido: Optimización por tipo de página
  routeRules: {
    // SPA — páginas privadas (instant load, no SEO needed)
    '/cart': { ssr: false },
    '/checkout': { ssr: false },
    '/payment/**': { ssr: false },
    '/booking-confirmation/**': { ssr: false },
    '/**/cart': { ssr: false },
    '/**/checkout': { ssr: false },

    // SWR — páginas públicas con cache (revalida en background)
    '/': { swr: 3600 },
    '/es': { swr: 3600 },
    '/en': { swr: 3600 },
    '/pt': { swr: 3600 },
    '/fr': { swr: 3600 },
    '/de': { swr: 3600 },
    '/it': { swr: 3600 },
    // Listing page only — individual tour detail pages are under /{city}/{slug}
    '/**/tours': { swr: 60 },
    '/**/about': { swr: 86400 },
    '/**/contact': { swr: 86400 },

    // API pass-through, sin caché
    '/api/**': { headers: { 'cache-control': 'no-cache' } }
  },

  // Sitemap automático para SEO
  site: {
    url: 'https://incalake.com',
    name: 'Incalake Tours'
  },

  sitemap: {
    routes: async () => {
      // Aquí puedes agregar rutas dinámicas desde la API
      // Por ejemplo, fetchear todos los tours desde Laravel
      return [
        '/',
        '/tours',
        '/about',
        '/contact',
      ]
    }
  },

  // i18n configuration for multilang URLs
  i18n: {
    locales: [
      { code: 'es', iso: 'es-PE', name: 'Español' },
      { code: 'en', iso: 'en-US', name: 'English' },
      { code: 'pt', iso: 'pt-BR', name: 'Português' },
      { code: 'fr', iso: 'fr-FR', name: 'Français' },
      { code: 'de', iso: 'de-DE', name: 'Deutsch' },
      { code: 'it', iso: 'it-IT', name: 'Italiano' },
    ],
    defaultLocale: 'es',
    strategy: 'prefix',
    detectBrowserLanguage: {
      useCookie: true,
      cookieKey: 'i18n_redirected',
      redirectOn: 'root',
    },
    vueI18n: './i18n.config.ts',
    restructureDir: false
  },

  typescript: {
    strict: false,
    typeCheck: false
  },

  compatibilityDate: '2024-11-01'
})
