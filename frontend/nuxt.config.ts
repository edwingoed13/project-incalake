import tailwindcss from '@tailwindcss/vite'

// ISR is a production optimisation. In `nuxt dev` it triggers a Nitro
// payload-cache path collision (/es is cached as a file, /es/tours needs /es as
// a dir) that 500s sub-routes. So apply the cache rules only in production —
// dev renders fresh SSR, which is what you want while developing anyway.
//
// `isr`, not `swr`: on Vercel the swr rule only stamps stale-while-revalidate
// headers on the CDN, it does NOT wire up Vercel's ISR cache. In practice that
// meant pages went stale and never regenerated (observed Age 551s on a
// swr(300) route, still serving pre-edit HTML), and — the reason this changed —
// on-demand revalidation is only available for isr routes. Publishing a tour
// can now purge its pages instead of waiting out a timer.
const isProd = process.env.NODE_ENV === 'production'
const isr = (maxage: number) => (isProd ? { isr: maxage } : {})

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
    // Recover stale clients after a deploy without a manual hard-refresh:
    //  - emitRouteChunkError: reload the page when a lazy chunk 404s because the
    //    old build was replaced (the classic "white screen / doesn't update").
    //  - appManifest + checkOutdatedBuildInterval: poll the build manifest every
    //    5 min so an open tab detects a new deployment and reloads on next nav.
    appManifest: true,
    checkOutdatedBuildInterval: 1000 * 60 * 5,
    emitRouteChunkError: 'automatic-immediate',
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
    // Self-hosts Inter at build time (immutable /_nuxt assets): kills the
    // render-blocking fonts.googleapis.com CSS + gstatic round-trips and the
    // occasional stale-hash woff2 404 Google served us.
    '@nuxt/fonts',
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
      // Bumped 256 → 384 KB after a hydration regression: the wishlist
      // heart icon (material-symbols:favorite) was getting dropped from
      // the client bundle when scan picked up new icons from recent
      // feature work (tour options selector, filter panel, video facade)
      // and pushed the total over 256 KB. The icon was listed explicitly
      // below but the cap still wins. 384 KB keeps us well under the
      // 3.8 MB web-font regression that prompted this strategy.
      sizeLimitKb: 384,
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
    // Shared secret that lets the backend purge an ISR page on demand: a GET
    // with `x-prerender-revalidate: <token>` regenerates that path instead of
    // waiting out the expiration above. Must match VERCEL_BYPASS_TOKEN in the
    // Vercel project env AND FRONTEND_REVALIDATE_TOKEN in the API's .env.
    vercel: {
      config: {
        bypassToken: process.env.VERCEL_BYPASS_TOKEN,
      },
    },
    // Pre-render páginas estáticas para SEO
    prerender: {
      crawlLinks: false,
      // Filled by the nitro:config hook below with every public page. A tour
      // that fails to prerender (API hiccup mid-build) must not fail the
      // deploy — it just falls back to being generated on first request.
      failOnError: false,
      routes: []
    }
  },

  hooks: {
    /**
     * Build every public page into static HTML at deploy time.
     *
     * Without this, a page only exists once someone asks for it, and Vercel
     * caches per EDGE REGION — so the first visitor in each region waits
     * 1.3–2.5s while it is generated, and a purge only refreshes the region
     * that receives it. Warming from one place cannot fix that: there is
     * nothing to copy until a region generates its own.
     *
     * Prerendered pages are uploaded as static files and served from Vercel's
     * global CDN, so page one exists everywhere the moment the deploy
     * finishes — Lima, Madrid or Sydney, first visitor included. The ISR
     * window on top keeps them fresh.
     */
    async 'nitro:config'(nitroConfig) {
      // Only for the real deploy: `nuxt dev` would pay this on every restart.
      if (nitroConfig.dev) return

      const api = process.env.NUXT_PUBLIC_API_BASE || ''
      const locales = ['es', 'en', 'pt', 'fr', 'de', 'it']
      const routes = new Set<string>(['/'])

      for (const l of locales) {
        routes.add(`/${l}`)
        routes.add(`/${l}/tours`)
        routes.add(`/${l}/about`)
        routes.add(`/${l}/contact`)
      }

      if (!api) {
        console.warn('[prerender] NUXT_PUBLIC_API_BASE unset — only static pages will be prerendered')
        nitroConfig.prerender!.routes = [...routes]
        return
      }

      // How many tour pages per locale. Deliberately NOT all 175: prerendering
      // ~200 pages ran Node out of heap and aborted the build (exit 134), and
      // a build that dies is a site that cannot be updated — a far worse
      // problem than a slow first load. These counts land around 60 pages,
      // the size that built cleanly. The listing is ordered by real sales, so
      // the ones baked in are the ones travellers open most; the rest stay on
      // ISR and are kept warm by the hourly workflow.
      const perLocale: Record<string, number> = { es: 30, en: 10, pt: 5, fr: 5, de: 5, it: 5 }

      // Ask per locale: the listing only returns a tour in a language it has
      // been translated into, so this yields exactly the URLs that resolve —
      // no 404s baked into the build.
      for (const l of locales) {
        try {
          const res = await fetch(
            `${api}/tours?light=1&per_page=${perLocale[l] ?? 5}&language=${l.toUpperCase()}`,
            { signal: AbortSignal.timeout(60_000) }
          )
          if (!res.ok) continue
          const body: any = await res.json()
          for (const tour of body?.data ?? []) {
            const city = tour?.city?.slug || 'puno'
            if (tour?.slug) routes.add(`/${l}/${city}/${tour.slug}`)
          }
        } catch (e: any) {
          // A locale that fails simply keeps being rendered on demand.
          console.warn(`[prerender] skipping ${l}: ${e?.message || e}`)
        }
      }

      console.log(`[prerender] ${routes.size} public pages will be built as static HTML`)
      nitroConfig.prerender!.routes = [...routes]
    },
  },

  // SSR activado para SEO (IPC fix applied via non-blocking data fetching)

  // SSR + SPA + ISR Híbrido: Optimización por tipo de página
  routeRules: {
    // SPA — páginas privadas (instant load, no SEO). Localized too: with i18n
    // strategy 'prefix' the real paths are /{locale}/cart, /{locale}/payment/…
    // so the unprefixed rules alone never matched. robots:false = noindex.
    // payment (/es/payment/culqi) and booking-confirmation (/es/booking-confirmation/{code})
    // are 3-segment paths. The `/*/*/*` isr(300) rule below ALSO matches them, and in
    // Nitro/radix3 a param segment (`*`) outranks a wildcard (`**`), so a `/**/…`
    // rule LOSES to `/*/*/*` — that's why ssr:false never applied and the page was
    // SSR-cached by PATH, dropping the per-user `?token=`/`?email=` query (links
    // always hit the no-token branch → "verificación de email requerida", plus
    // personal data got cached). Use a STATIC 2nd segment (`/*/booking-confirmation/**`),
    // which DOES outrank `/*/*/*`, and isr:false to drop the inherited isr(300) so
    // they stay pure client-side SPA and read the token in the browser.
    '/**/cart': { ssr: false, robots: false, isr: false },
    '/**/checkout': { ssr: false, robots: false, isr: false },
    '/*/payment/**': { ssr: false, robots: false, isr: false },
    '/*/booking-confirmation/**': { ssr: false, robots: false, isr: false },
    '/**/saved': { ssr: false, robots: false, isr: false },

    // ISR — páginas públicas con cache (revalida en background). Prod-only.
    //
    // 15 minutes: a deliberate middle, arrived at by measuring rather than
    // assuming. The old 5-minute window left pages permanently cold (10 of 10
    // sampled tour pages were MISS at 1.3–2.5s versus 0.4s warm). An hour was
    // tried and rolled back: on-demand purging turns out to refresh the region
    // that receives the purge, and the site is cached PER EDGE REGION — so a
    // published change reached a traveller on another continent only when
    // their region's window expired. At an hour that is an hour of stale
    // prices. Fifteen minutes keeps pages warm enough to matter while capping
    // the worst case for everyone, purge or no purge.
    '/': isr(900),
    '/es': isr(900),
    '/en': isr(900),
    '/pt': isr(900),
    '/fr': isr(900),
    '/de': isr(900),
    '/it': isr(900),
    // Tour listing
    '/**/tours': isr(900),
    // Tour detail /{locale}/{city}/{slug}. The more-specific SPA rules above
    // (cart/payment/booking-confirmation) win over this 3-segment wildcard.
    '/*/*/*': isr(900),
    // Static copy: nothing here changes without a deploy, and a deploy clears
    // the cache anyway.
    '/**/about': isr(86400),
    '/**/contact': isr(86400),

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

  // i18n configuration for multilang URLs.
  // Lazy-loaded since June 2026: each locale lives in its own file under
  // i18n/locales/*.ts. Only the default locale (es) ships in the main
  // bundle; en/pt/fr/de/it fetch on demand when the user switches locale
  // or visits a /xx/* route. Cuts ~60-80KB from the homepage payload.
  i18n: {
    baseUrl: 'https://incalake.com',
    lazy: true,
    langDir: 'locales',
    locales: [
      // v10 uses `language` (not `iso`) for hreflang/<html lang>.
      // `file` tells the lazy loader which module to import for this locale.
      { code: 'es', language: 'es-PE', name: 'Español',    file: 'es.ts' },
      { code: 'en', language: 'en-US', name: 'English',    file: 'en.ts' },
      { code: 'pt', language: 'pt-BR', name: 'Português',  file: 'pt.ts' },
      { code: 'fr', language: 'fr-FR', name: 'Français',   file: 'fr.ts' },
      { code: 'de', language: 'de-DE', name: 'Deutsch',    file: 'de.ts' },
      { code: 'it', language: 'it-IT', name: 'Italiano',   file: 'it.ts' },
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
