// Main navigation menu, editable from the admin (CMS `page='menu'`) with a
// hardcoded fallback so the header never ends up empty if the CMS is missing,
// unpublished, or the API is unreachable.
//
// Fetched lazily (never blocks page SSR) and keyed per-locale. The Navbar lives
// in app.vue (outside <NuxtPage>), so this runs once and only refetches when the
// language changes.
export interface NavItem {
  label: string
  url: string
  type: 'internal' | 'external'
}

// Safety-net defaults. Labels are i18n keys resolved here via t(), matching the
// site's existing fallback behavior.
const DEFAULT_ITEMS: { key: string; url: string }[] = [
  { key: 'tours', url: '/tours' },
  { key: 'about', url: '/about' },
  { key: 'contact', url: '/contact' },
]

export function useNavigation() {
  const { api } = useApi()
  const { locale, t } = useI18n()

  const { data } = useAsyncData(
    () => `nav-menu-${locale.value}`,
    () => api(`/pages/menu?language=${locale.value.toUpperCase()}`).catch(() => null),
    { lazy: true, default: () => null, watch: [locale] },
  )

  const menuItems = computed<NavItem[]>(() => {
    const items = (data.value as any)?.data?.content?.items
    if (Array.isArray(items)) {
      const usable = items
        .filter((i: any) => i && i.visible !== false && i.label && i.url)
        .map((i: any): NavItem => ({
          label: String(i.label),
          url: String(i.url),
          type: i.type === 'external' ? 'external' : 'internal',
        }))
      if (usable.length) return usable
    }
    // Fallback: the hardcoded defaults with i18n labels.
    return DEFAULT_ITEMS.map((d): NavItem => ({ label: t(d.key), url: d.url, type: 'internal' }))
  })

  return { menuItems }
}
