// Dynamic sitemap source: emits every active tour as /{locale}/{city}/{slug}
// for all locales. Registered via nuxt.config `sitemap.sources`.
const LOCALES = ['es', 'en', 'pt', 'fr', 'de', 'it']

// The deployed NUXT_PUBLIC_API_BASE carries a trailing newline, so its value is
// the API URL followed by a line break. useApi() has always scrubbed that, so
// the site worked and nothing looked wrong — but this handler used the value
// raw, built a URL with a newline inside it, and every request threw. The catch
// below turned that into an empty list, so the sitemap shipped with zero tours
// and said nothing. Scrub it here too rather than trust the env var to be clean.
const clean = (raw: unknown): string =>
  String(raw ?? '').trim().replace(/\s+/g, '').replace(/\/+$/, '')

export default defineSitemapEventHandler(async () => {
  const { public: { apiBase } } = useRuntimeConfig()
  const base = clean(apiBase)

  try {
    const res: any = await $fetch(`${base}/tours`, {
      params: { per_page: 1000, active: 1 },
    })
    const tours: any[] = res?.data?.data ?? res?.data ?? []

    const urls = tours.flatMap((t: any) => {
      const city = t.city?.slug || 'puno'
      const slug = t.slug
      if (!slug) return []
      return LOCALES.map(locale => ({
        loc: `/${locale}/${city}/${slug}`,
        changefreq: 'weekly' as const,
        priority: 0.8,
        lastmod: t.updated_at || undefined,
      }))
    })

    // A sitemap with no tours in it is a failure wearing a success costume:
    // it returns 200, Google accepts it, and the catalogue stays undiscovered.
    // Say so where someone will see it.
    if (!urls.length) {
      console.warn(`[sitemap] ${tours.length} tours from ${base}/tours produced no URLs`)
    }

    return urls
  } catch (e: any) {
    // Never break the sitemap build if the API is briefly unavailable — but
    // never fail silently either. Swallowing this is what hid the newline for
    // as long as it did.
    console.error(`[sitemap] could not read tours from ${base}/tours:`, e?.message || e)
    return []
  }
})
