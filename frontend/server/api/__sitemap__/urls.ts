// Dynamic sitemap source: emits every active tour as /{locale}/{city}/{slug}
// for all locales. Registered via nuxt.config `sitemap.sources`.
const LOCALES = ['es', 'en', 'pt', 'fr', 'de', 'it']

export default defineSitemapEventHandler(async () => {
  const { public: { apiBase } } = useRuntimeConfig()

  try {
    const res: any = await $fetch(`${apiBase}/tours`, {
      params: { per_page: 1000, active: 1 },
    })
    const tours: any[] = res?.data?.data ?? res?.data ?? []

    return tours.flatMap((t: any) => {
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
  } catch {
    // Never break the sitemap build if the API is briefly unavailable.
    return []
  }
})
