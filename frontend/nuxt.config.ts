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

  // Inline the SSR payload in the HTML instead of a separate _payload.json.
  // On Vercel SWR, nested routes (tour detail /*/*/*) served a cached HTML that
  // referenced a payload hash the edge didn't have → 404 → the client re-fetched
  // the tour on hydration → content collapsed to the spinner (footer jumped up)
  // and hard refresh felt slow. Inlining means the client always has the data:
  // no re-fetch, no layout shift. getCachedData/prefetch still read payload.data.
  experimental: {
    payloadExtraction: false,
  },

  vite: {
    server: {
      allowedHosts: true,
    },
    plugins: [
      tailwindcss(),
    ],
    // Drop console.* and debugger statements in the production bundle.
    // Several components left payment-flow and cart-state console.logs that
    // leak request tokens and customer fields into the browser DevTools.
    // Dev builds keep them so live debugging still works; Vercel builds with
    // NODE_ENV=production so the drop only fires on the deployed bundle.
    esbuild: {
      drop: process.env.NODE_ENV === 'production' ? ['console', 'debugger'] : [],
    },
  },

  srcDir: 'app',

  modules: [
    '@nuxt/icon',
    '@nuxt/image',
    '@pinia/nuxt',
    '@vueuse/nuxt',
    '@nuxtjs/i18n',
    // @nuxtjs/seo already bundles sitemap, robots, og-image, schema-org &
    // seo-utils — so @nuxtjs/sitemap is NOT listed separately (was a duplicate).
    '@nuxtjs/seo',
  ],

  css: ['~/assets/css/main.css'],

  // Icons migrated off the Material Symbols web font (was 3.8 MB, pinned to 312 KB)
  // to inline SVG via @nuxt/icon + the locally-installed @iconify-json/material-symbols.
  // No runtime Iconify API calls: the server renders SVG from the bundled collection,
  // and the client bundle below lists every icon we use (incl. the data-driven ones
  // a source scan can't see — Footer socials, trust signals, filters). `scan` is a
  // backstop that auto-includes any literal `material-symbols:*` names in templates.
  icon: {
    mode: 'svg',
    serverBundle: { collections: ['material-symbols'] },
    clientBundle: {
      scan: true,
      sizeLimitKb: 256,
      icons: [
        'material-symbols:account-balance-wallet-outline',
        'material-symbols:add',
        'material-symbols:arrow-back',
        'material-symbols:arrow-forward',
        'material-symbols:block-outline',
        'material-symbols:bolt-outline',
        'material-symbols:calendar-today-outline',
        'material-symbols:cancel-outline',
        'material-symbols:celebration-outline',
        'material-symbols:chat-outline',
        'material-symbols:check',
        'material-symbols:check-circle-outline',
        'material-symbols:chevron-left',
        'material-symbols:chevron-right',
        'material-symbols:close',
        'material-symbols:confirmation-number-outline',
        'material-symbols:content-copy-outline',
        'material-symbols:credit-card-outline',
        'material-symbols:delete-outline',
        'material-symbols:description-outline',
        'material-symbols:directions-bus-outline',
        'material-symbols:done',
        'material-symbols:download',
        'material-symbols:edit-outline',
        'material-symbols:error-outline',
        'material-symbols:expand-more',
        'material-symbols:explore-outline',
        'material-symbols:favorite',
        'material-symbols:favorite-outline',
        'material-symbols:grid-view-outline',
        'material-symbols:group-outline',
        'material-symbols:help-outline',
        'material-symbols:home-outline',
        'material-symbols:hotel-outline',
        'material-symbols:hourglass-empty',
        'material-symbols:image-outline',
        'material-symbols:inbox-outline',
        'material-symbols:info-outline',
        'material-symbols:label-outline',
        'material-symbols:location-on-outline',
        'material-symbols:lock-outline',
        'material-symbols:menu',
        'material-symbols:payments-outline',
        'material-symbols:person-outline',
        'material-symbols:photo-camera-outline',
        'material-symbols:photo-library',
        'material-symbols:play-arrow-outline',
        'material-symbols:policy-outline',
        'material-symbols:progress-activity',
        'material-symbols:public',
        'material-symbols:receipt-long-outline',
        'material-symbols:record-voice-over-outline',
        'material-symbols:refresh',
        'material-symbols:remove',
        'material-symbols:report-outline',
        'material-symbols:schedule-outline',
        'material-symbols:search',
        'material-symbols:search-off',
        'material-symbols:security',
        'material-symbols:sell-outline',
        'material-symbols:share-outline',
        'material-symbols:shield-outline',
        'material-symbols:shopping-cart-outline',
        'material-symbols:sort',
        'material-symbols:star',
        'material-symbols:star-outline',
        'material-symbols:tour-outline',
        'material-symbols:trending-flat',
        'material-symbols:tune',
        'material-symbols:verified-outline',
        'material-symbols:verified-user-outline',
        'material-symbols:view-list-outline',
        'material-symbols:warning-outline',
        'material-symbols:wifi-off',
      ],
    },
  },

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
    // payment (/es/payment/culqi) and booking-confirmation (/es/booking-confirmation/{code})
    // are 3-segment paths. The `/*/*/*` swr(600) rule below ALSO matches them, and in
    // Nitro/radix3 a param segment (`*`) outranks a wildcard (`**`), so a `/**/…`
    // rule LOSES to `/*/*/*` — that's why ssr:false never applied and the page was
    // SSR-cached by PATH, dropping the per-user `?token=`/`?email=` query (links
    // always hit the no-token branch → "verificación de email requerida", plus
    // personal data got cached). Use a STATIC 2nd segment (`/*/booking-confirmation/**`),
    // which DOES outrank `/*/*/*`, and swr:false to drop the inherited swr(600) so
    // they stay pure client-side SPA and read the token in the browser.
    '/**/cart': { ssr: false, robots: false, swr: false },
    '/**/checkout': { ssr: false, robots: false, swr: false },
    '/*/payment/**': { ssr: false, robots: false, swr: false },
    '/*/booking-confirmation/**': { ssr: false, robots: false, swr: false },
    '/**/saved': { ssr: false, robots: false, swr: false },

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
