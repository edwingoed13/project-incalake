import tailwindcss from '@tailwindcss/vite'

// SWR/ISR is a production optimisation. In `nuxt dev` it triggers a Nitro
// payload-cache path collision (/es is cached as a file, /es/tours needs /es as
// a dir) that 500s sub-routes. So apply the cache rules only in production —
// dev renders fresh SSR, which is what you want while developing anyway.
const isProd = process.env.NODE_ENV === 'production'
const swr = (maxage: number) => (isProd ? { swr: maxage } : {})

export default defineNuxtConfig({
  devtools: { enabled: false },
  ssr: true,

  vite: {
    server: {
      allowedHosts: true,
    },
    plugins: [
      tailwindcss(),
    ],
  },

  srcDir: 'app',

  modules: [
    '@nuxt/image',
    '@pinia/nuxt',
    '@vueuse/nuxt',
    '@nuxtjs/i18n',
    // @nuxtjs/seo already bundles sitemap, robots, og-image, schema-org &
    // seo-utils — so @nuxtjs/sitemap is NOT listed separately (was a duplicate).
    '@nuxtjs/seo',
  ],

  css: ['~/assets/css/main.css'],

  // @nuxt/image: allow optimizing the remote storage host + hero fallback,
  // serve modern formats by default.
  image: {
    domains: ['api.incalake.com', 'incalake.com', 'lh3.googleusercontent.com'],
    // WebP only: encodes fast (AVIF is 5-10x slower to encode on-demand, which
    // added multi-second latency when optimizing from the remote origin).
    // WebP is ~70% smaller than the original and universally supported.
    format: ['webp'],
    quality: 72,
  },

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
      // <html lang> is set dynamically per-locale in app.vue (useLocaleHead).
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
        // No global canonical here — it was forcing every page to canonicalize
        // to the homepage. Canonicals are now per-route (useLocaleHead + per-page).
      ],
      // Google Maps now lazy-loaded by useGoogleMaps() in the components that actually need it.
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
    // SPA — páginas privadas (instant load, no SEO). Localized too: with i18n
    // strategy 'prefix' the real paths are /{locale}/cart, /{locale}/payment/…
    // so the unprefixed rules alone never matched. robots:false = noindex.
    '/**/cart': { ssr: false, robots: false },
    '/**/checkout': { ssr: false, robots: false },
    '/**/payment/**': { ssr: false, robots: false },
    '/**/booking-confirmation/**': { ssr: false, robots: false },
    '/**/saved': { ssr: false, robots: false },

    // SWR — páginas públicas con cache (revalida en background). Prod-only.
    '/': swr(3600),
    '/es': swr(3600),
    '/en': swr(3600),
    '/pt': swr(3600),
    '/fr': swr(3600),
    '/de': swr(3600),
    '/it': swr(3600),
    // Tour listing
    '/**/tours': swr(300),
    // Tour detail /{locale}/{city}/{slug} — was pure SSR (no cache) on every
    // request. ISR/SWR 10 min. The more-specific SPA rules above (cart/payment/
    // booking-confirmation) win over this 3-segment wildcard.
    '/*/*/*': swr(600),
    '/**/about': swr(86400),
    '/**/contact': swr(86400),

    // API pass-through, sin caché
    '/api/**': { headers: { 'cache-control': 'no-cache' } }
  },

  // Sitemap automático para SEO
  site: {
    url: 'https://incalake.com',
    name: 'Incalake Tours'
  },

  sitemap: {
    // Static pages get localized variants automatically via the i18n
    // integration. Dynamic tour URLs come from the server source below.
    sources: ['/api/__sitemap__/urls'],
  },

  // i18n configuration for multilang URLs
  i18n: {
    // Absolute base for canonical + hreflang alternate links (useLocaleHead).
    baseUrl: 'https://incalake.com',
    locales: [
      // v10 uses `language` (not `iso`) for hreflang/<html lang>.
      { code: 'es', language: 'es-PE', name: 'Español' },
      { code: 'en', language: 'en-US', name: 'English' },
      { code: 'pt', language: 'pt-BR', name: 'Português' },
      { code: 'fr', language: 'fr-FR', name: 'Français' },
      { code: 'de', language: 'de-DE', name: 'Deutsch' },
      { code: 'it', language: 'it-IT', name: 'Italiano' },
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
