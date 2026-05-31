<template>
  <div class="bg-background-light dark:bg-background-dark min-h-screen">
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 md:pt-28 pb-16">
      <!-- Header -->
      <header class="mb-6 md:mb-8 flex items-end justify-between gap-4 flex-wrap">
        <div>
          <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
            <HeartSolidIcon class="size-7 text-red-500" aria-hidden="true" />
            Mis guardados
          </h1>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            {{ items.length === 0
              ? 'Aún no has guardado ningún tour'
              : `${items.length} tour${items.length === 1 ? '' : 's'} guardado${items.length === 1 ? '' : 's'} en este navegador` }}
          </p>
        </div>
        <button
          v-if="items.length > 0"
          @click="confirmClear"
          type="button"
          class="text-xs font-bold text-slate-500 hover:text-red-500 transition-colors inline-flex items-center gap-1.5 px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800"
        >
          <TrashIcon class="size-4" aria-hidden="true" />
          Vaciar lista
        </button>
      </header>

      <!-- Empty state -->
      <div
        v-if="items.length === 0"
        class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-10 md:p-16 text-center"
      >
        <div class="mx-auto size-16 rounded-full bg-red-50 dark:bg-red-900/20 flex items-center justify-center mb-4">
          <HeartIcon class="size-8 text-red-400" aria-hidden="true" />
        </div>
        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100 mb-1">Tu lista está vacía</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
          Cuando encuentres un tour que te interese, toca el corazón para guardarlo aquí y volver más tarde.
        </p>
        <NuxtLink
          :to="localePath('/')"
          class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:brightness-110 text-white rounded-xl text-sm font-bold transition-all"
        >
          Explorar tours
          <ArrowRightIcon class="size-4" aria-hidden="true" />
        </NuxtLink>
      </div>

      <!-- Saved tours (toolbar + grid) -->
      <div v-else>
        <!-- Bulk select toolbar (only with 2+ items) -->
        <div v-if="items.length > 1" class="flex items-center justify-between gap-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 px-3 py-2 mb-3">
          <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-600 dark:text-slate-300">
            <input type="checkbox" :checked="allSelected" @change="toggleSelectAll" class="w-4 h-4 accent-primary rounded" />
            {{ allSelected ? 'Deseleccionar todos' : 'Seleccionar todos' }}
          </label>
          <div v-if="selectedIds.length > 0" class="flex items-center gap-2">
            <span class="text-xs font-semibold text-slate-500">{{ selectedIds.length }} {{ selectedIds.length === 1 ? 'seleccionado' : 'seleccionados' }}</span>
            <button @click="bulkDelete" type="button" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50 rounded-lg transition-colors">
              <TrashIcon class="size-4" /> Quitar
            </button>
          </div>
        </div>

        <!-- Grid (tight OTA-style cards) -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4">
        <article
          v-for="item in items"
          :key="item.id"
          class="group relative bg-white dark:bg-slate-900 rounded-xl border overflow-hidden shadow-sm hover:shadow-md transition-all flex flex-col"
          :class="isSelected(item.id) ? 'border-primary/50 ring-1 ring-primary/20' : 'border-slate-200 dark:border-slate-800'"
        >
          <!-- Selection checkbox (only with 2+ items) -->
          <label v-if="items.length > 1" class="absolute top-2 left-2 z-10 flex items-center justify-center w-6 h-6 bg-white/90 backdrop-blur rounded-md cursor-pointer">
            <input type="checkbox" :checked="isSelected(item.id)" @change="toggleSelect(item.id)" class="w-4 h-4 accent-primary rounded" :aria-label="`Seleccionar ${item.title || 'tour'}`" />
          </label>

          <NuxtLink :to="tourLink(item)" class="relative block aspect-[3/2] overflow-hidden bg-slate-100 dark:bg-slate-800">
            <img
              v-if="item.image"
              :src="imageSrc(item.image)"
              :alt="item.title || 'Tour'"
              loading="lazy"
              decoding="async"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-slate-300">
              <PhotoIcon class="size-10" aria-hidden="true" />
            </div>
            <!-- Offer badge (live from current listing) -->
            <div v-if="item.offer" class="absolute bottom-2 left-2 px-2 py-0.5 bg-green-500 text-white text-[10px] font-bold rounded-full shadow flex items-center gap-0.5">
              <HeartSolidIcon class="size-2.5" aria-hidden="true" />
              {{ item.offer.label }}
            </div>
            <button
              @click.stop.prevent="wishlistStore.remove(item.id)"
              type="button"
              class="absolute top-2 right-2 p-1.5 rounded-full bg-white/90 text-red-500 hover:bg-white active:scale-90 transition-all shadow"
              aria-label="Quitar de guardados"
            >
              <HeartSolidIcon class="size-4" aria-hidden="true" />
            </button>
          </NuxtLink>

          <div class="p-3 flex-1 flex flex-col">
            <p v-if="item.city_slug" class="text-[9px] text-slate-500 font-bold uppercase tracking-wider mb-1">
              {{ item.city_slug }}
            </p>
            <NuxtLink :to="tourLink(item)" class="block">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-primary transition-colors line-clamp-2 leading-snug">
                {{ item.title || 'Tour' }}
              </h3>
            </NuxtLink>

            <div class="mt-auto pt-3 flex items-end justify-between gap-2">
              <div v-if="item.min_price" class="min-w-0 leading-tight">
                <span class="text-[9px] text-slate-400 font-medium block leading-none mb-0.5">Desde</span>
                <span class="flex items-baseline gap-1 flex-wrap">
                  <span v-if="showDiscountedPrice(item)" class="text-[10px] line-through text-slate-400">
                    {{ currencyStore.formatConverted(item.min_price) }}
                  </span>
                  <span class="text-base font-black" :class="showDiscountedPrice(item) ? 'text-trust' : 'text-primary'">
                    {{ currencyStore.formatConverted(showDiscountedPrice(item) ? item.offer.discounted_min_price : item.min_price) }}
                  </span>
                </span>
              </div>
              <NuxtLink
                :to="tourLink(item)"
                class="ml-auto inline-flex items-center gap-0.5 text-[11px] font-bold text-primary hover:underline whitespace-nowrap"
              >
                Ver tour
                <ArrowRightIcon class="size-3" aria-hidden="true" />
              </NuxtLink>
            </div>

            <p class="text-[9px] text-slate-400 mt-1.5">
              Guardado el {{ formatSaved(item.savedAt) }}
            </p>
          </div>
        </article>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { HeartIcon, ArrowRightIcon, PhotoIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { HeartIcon as HeartSolidIcon } from '@heroicons/vue/24/solid'

definePageMeta({ title: 'Mis guardados' })

useHead({ title: 'Mis guardados - Incalake' })

const wishlistStore = useWishlistStore()
const currencyStore = useCurrencyStore()
const localePath = useLocalePath()
const config = useRuntimeConfig()
const { locale } = useI18n()
const { api } = useApi()

// Live-offer enrichment: the wishlist only persists minimal fields (no offer
// info, because offers expire/change daily). Pull the current listing (already
// cached server-side) and merge offer + current min_price by tour id.
const liveById = ref<Record<number, any>>({})
onMounted(async () => {
  if (!wishlistStore.items.length) return
  try {
    const res: any = await api(`/tours?light=1&language=${(locale.value || 'es').toUpperCase()}&per_page=300`)
    const list = Array.isArray(res?.data) ? res.data : []
    const map: Record<number, any> = {}
    for (const t of list) if (t?.id) map[Number(t.id)] = t
    liveById.value = map
  } catch { /* silent: page still works with the stored data */ }
})

const items = computed(() =>
  wishlistStore.items.map((it: any) => {
    const live = liveById.value[Number(it.id)]
    if (!live) return it
    return {
      ...it,
      min_price: live.min_price ?? it.min_price,
      offer: live.offer || null,
    }
  })
)

const showDiscountedPrice = (item: any) => !!(item?.offer && item?.offer?.discounted_min_price)

// --- Bulk select + delete --------------------------------------------------
const selectedIds = ref<number[]>([])
const isSelected = (id: number) => selectedIds.value.includes(id)
function toggleSelect(id: number) {
  const i = selectedIds.value.indexOf(id)
  if (i >= 0) selectedIds.value.splice(i, 1)
  else selectedIds.value.push(id)
}
const allSelected = computed(() =>
  wishlistStore.items.length > 0 && selectedIds.value.length === wishlistStore.items.length
)
function toggleSelectAll() {
  selectedIds.value = allSelected.value ? [] : wishlistStore.items.map(i => i.id)
}
function bulkDelete() {
  if (!selectedIds.value.length) return
  if (typeof window !== 'undefined' && !window.confirm(`¿Quitar ${selectedIds.value.length} ${selectedIds.value.length === 1 ? 'tour' : 'tours'} de tu lista?`)) return
  for (const id of [...selectedIds.value]) wishlistStore.remove(id)
  selectedIds.value = []
}
// Auto-prune selection when items disappear (individual remove).
watch(() => wishlistStore.items.map(i => i.id), (ids) => {
  selectedIds.value = selectedIds.value.filter(id => ids.includes(id))
})

function tourLink(item: any) {
  if (item.city_slug && item.slug) return localePath(`/${item.city_slug}/${item.slug}`)
  if (item.slug) return localePath(`/tours/${item.slug}`)
  return localePath('/')
}

// Mirrors the local getImageUrl helper used in cart.vue / index.vue.
// `getImageUrl` from utils/formatters.ts is not auto-imported in this
// project, so calling it bare throws ReferenceError and breaks the page
// render (which is what made the saved view appear under the footer).
function imageSrc(path?: string) {
  if (!path) return ''
  if (/^https?:\/\//.test(path)) return path
  const base = String(config.public.storageBase || '').replace(/\/$/, '')
  return `${base}/${path.replace(/^\//, '')}`
}

function formatSaved(ts?: number) {
  if (!ts) return ''
  try {
    return new Date(ts).toLocaleDateString('es-PE', { day: 'numeric', month: 'short', year: 'numeric' })
  } catch { return '' }
}

function confirmClear() {
  if (typeof window === 'undefined') return
  if (window.confirm('¿Vaciar toda tu lista de guardados? Esta acción no se puede deshacer.')) {
    wishlistStore.clear()
  }
}
</script>
