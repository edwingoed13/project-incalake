// Prefetch a tour's detail data on hover/focus of its card, so clicking it
// navigates instantly (no 1-2s API wait, no skeleton). We warm the exact
// useAsyncData key the detail page reads (getCachedData uses
// nuxtApp.payload.data[key]); the key + URL mirror getTourLink:
// route is /{lang}/{citySlug}/{slug}, key is `tour-{LANG}-{citySlug}-{slug}`.
export function useTourPrefetch() {
  const { api } = useApi()
  const { locale } = useI18n()
  const nuxtApp = useNuxtApp()
  const inflight = new Set<string>()

  function prefetchTour(tour: any) {
    if (!import.meta.client || !tour) return
    const citySlug = tour.city?.slug || 'puno'
    const slug = tour.slug || tour.id
    if (!slug) return
    const lang = locale.value.toUpperCase()
    const key = `tour-${lang}-${citySlug}-${slug}`
    if (inflight.has(key) || nuxtApp.payload.data[key]) return
    inflight.add(key)
    api(`/tours/${lang.toLowerCase()}/${citySlug}/${slug}`)
      .then((d: any) => { nuxtApp.payload.data[key] = d })
      .catch(() => { inflight.delete(key) })
  }

  return { prefetchTour }
}
