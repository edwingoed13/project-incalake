import { ref, watch } from 'vue'

/**
 * Manages a Set of expanded section IDs, persisting it to localStorage so
 * refreshing the page keeps each dropdown's open/closed state.
 *
 * Usage:
 *   const { toggleSection, isSectionExpanded } = useCollapsibles('wizard:step3')
 */
export const useCollapsibles = (storageKey: string) => {
  const expandedSections = ref<Set<string>>(new Set())

  // Hydrate from localStorage on client
  if (import.meta.client) {
    try {
      const raw = localStorage.getItem(storageKey)
      if (raw) {
        const arr = JSON.parse(raw)
        if (Array.isArray(arr)) expandedSections.value = new Set(arr)
      }
    } catch {
      // ignore malformed json
    }
  }

  // Persist on change
  watch(
    expandedSections,
    (val) => {
      if (!import.meta.client) return
      try {
        localStorage.setItem(storageKey, JSON.stringify(Array.from(val)))
      } catch {
        // localStorage may be unavailable (incognito, quota, etc.)
      }
    },
    { deep: true },
  )

  const toggleSection = (id: string) => {
    const next = new Set(expandedSections.value)
    next.has(id) ? next.delete(id) : next.add(id)
    expandedSections.value = next
  }

  const isSectionExpanded = (id: string) => expandedSections.value.has(id)

  return { expandedSections, toggleSection, isSectionExpanded }
}
