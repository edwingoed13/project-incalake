<script setup lang="ts">
// The site-wide wishlist heart. One component instead of ~10 inline copies:
// derives the wishlist payload from a raw tour object, toggles the store and
// flies the heart to the navbar counter on add. Position it from the call
// site (e.g. class="absolute top-3 right-3").
const props = withDefaults(defineProps<{
  tour: any
  size?: 'sm' | 'md'          // sm = 36px (dense lists) · md = 40px
  overlay?: 'light' | 'dark'  // dark = over photos/galleries
}>(), { size: 'md', overlay: 'light' })

const wishlistStore = useWishlistStore()
const { flyTo } = useFlyTo()
const config = useRuntimeConfig()

const saved = computed(() => wishlistStore.has(props.tour?.id))

function imageUrl(path: string) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${config.public.storageBase}/${path}`
}

function toggle(ev?: MouseEvent) {
  const t = props.tour || {}
  if (!t.id) return
  const wasAdded = !wishlistStore.has(t.id)
  // Capture the button synchronously — currentTarget nulls after dispatch.
  const src = ev
    ? ((ev.currentTarget as HTMLElement) || ((ev.target as HTMLElement)?.closest('button') as HTMLElement | null))
    : null
  wishlistStore.toggle({
    id: t.id,
    title: t.title,
    slug: t.slug,
    city_slug: t.city?.slug || t.city_slug || 'puno',
    image: imageUrl(t.featured_image || t.thumbnail || ''),
    min_price: t.min_price || 0,
    currency: t.currency || 'USD',
  })
  if (wasAdded && src) flyTo(src, '#nav-wishlist', 'heart')
}
</script>

<template>
  <button
    type="button"
    @click.stop.prevent="toggle($event)"
    class="rounded-full flex items-center justify-center shadow-sm active:scale-90 transition-transform z-10"
    :class="[
      size === 'sm' ? 'size-11 md:size-9' : 'size-11 md:size-10',
      overlay === 'dark' ? 'bg-black/45 backdrop-blur-sm' : 'bg-white/90 backdrop-blur',
      saved ? 'text-red-500' : (overlay === 'dark' ? 'text-white' : 'text-slate-400 hover:text-red-500'),
    ]"
    :aria-label="saved ? 'Quitar de guardados' : 'Guardar'"
    :aria-pressed="saved"
  >
    <Icon :name="saved ? 'material-symbols:favorite' : 'material-symbols:favorite-outline'" :class="size === 'sm' ? 'text-lg' : 'text-xl'" />
  </button>
</template>
