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

      <!-- Saved tours grid (tight OTA-style cards) -->
      <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4">
        <article
          v-for="item in items"
          :key="item.id"
          class="group bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm hover:shadow-md transition-all flex flex-col"
        >
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
              <div v-if="item.min_price" class="min-w-0">
                <span class="text-[9px] text-slate-400 font-medium block leading-none">Desde</span>
                <span class="text-base font-black text-primary">
                  {{ currencyStore.formatConverted(item.min_price) }}
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

const items = computed(() => wishlistStore.items)

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
