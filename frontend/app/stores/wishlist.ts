// Guest wishlist persisted to localStorage (OTA pattern: no expiration,
// survives deploys, no backend round trip, no login required). When customer
// auth ships, merge `items` into the user's server-side wishlist on login.
import { defineStore } from 'pinia'
import { computed, ref, watch } from 'vue'

const STORAGE_KEY = 'incalake.wishlist.v1'

export interface WishlistItem {
  id: number
  slug?: string
  city_slug?: string
  title?: string
  image?: string
  min_price?: number
  currency?: string
  savedAt: number
}

export const useWishlistStore = defineStore('wishlist', () => {
  const items = ref<WishlistItem[]>([])
  const hydrated = ref(false)

  const count = computed(() => items.value.length)
  const ids = computed(() => new Set(items.value.map(i => i.id)))

  function ensureHydrated() {
    if (!hydrated.value && import.meta.client) hydrate()
  }

  function has(id: number | undefined | null): boolean {
    ensureHydrated()
    return id != null && ids.value.has(Number(id))
  }

  function add(tour: Partial<WishlistItem> & { id: number }) {
    ensureHydrated()
    if (!tour?.id || has(tour.id)) return
    items.value.unshift({
      id: Number(tour.id),
      slug: tour.slug,
      city_slug: tour.city_slug,
      title: tour.title,
      image: tour.image,
      min_price: tour.min_price,
      currency: tour.currency,
      savedAt: Date.now(),
    })
  }

  function remove(id: number) {
    ensureHydrated()
    items.value = items.value.filter(i => i.id !== Number(id))
  }

  function toggle(tour: Partial<WishlistItem> & { id: number }): boolean {
    if (has(tour.id)) { remove(tour.id); return false }
    add(tour)
    return true
  }

  function clear() { items.value = [] }

  function hydrate() {
    if (hydrated.value || !import.meta.client) return
    try {
      const raw = localStorage.getItem(STORAGE_KEY)
      if (raw) {
        const parsed = JSON.parse(raw)
        if (Array.isArray(parsed)) items.value = parsed
      }
    } catch {
      // corrupted entry — start fresh
    }
    hydrated.value = true

    // Persist on every change (deep watch covers item field updates too).
    watch(items, (val) => {
      try { localStorage.setItem(STORAGE_KEY, JSON.stringify(val)) } catch {}
    }, { deep: true })
  }

  return { items, count, has, add, remove, toggle, clear, hydrate, ids }
})
