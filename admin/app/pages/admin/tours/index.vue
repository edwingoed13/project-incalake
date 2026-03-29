<template>
  <div>
    <div class="flex items-center justify-between mb-8">
      <div>
        <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Gestión de Tours</h3>
        <p class="text-slate-500 dark:text-slate-400">Administra los productos y experiencias de Incalake.</p>
      </div>
      <NuxtLink to="/admin/tours/new/edit" class="px-6 py-3 bg-primary text-white font-bold rounded-xl shadow-lg shadow-primary/20 hover:scale-[1.02] active:scale-95 transition-all flex items-center gap-2">
        <span class="material-symbols-outlined">add</span>
        Nuevo Tour
      </NuxtLink>
    </div>

    <!-- Tours Table -->
    <div class="glass-card rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden relative min-h-[400px]">
      <!-- Loading Overlay -->
      <div v-if="loading" class="absolute inset-0 bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm z-20 flex flex-col items-center justify-center gap-4">
         <div class="size-12 border-4 border-primary/20 border-t-primary rounded-full animate-spin"></div>
         <p class="text-sm font-bold text-primary animate-pulse">Sincronizando con la base de datos...</p>
      </div>

      <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex flex-col md:flex-row gap-4 justify-between bg-white/50 dark:bg-slate-900/50">
        <div class="relative flex-1">
          <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
            <span class="material-symbols-outlined text-lg">search</span>
          </span>
          <input
            v-model="searchQuery"
            @input="debounceSearch"
            class="w-full pl-10 pr-4 py-2.5 bg-slate-100 dark:bg-slate-800 border-none rounded-xl text-sm focus:ring-2 focus:ring-primary/20 transition-all"
            placeholder="Buscar por título o código..."
          />
        </div>
        <div class="flex gap-2">
          <button @click="refreshData" class="p-2.5 text-slate-400 hover:text-primary transition-colors hover:bg-primary/5 rounded-xl border border-slate-200 dark:border-slate-800">
            <span class="material-symbols-outlined text-lg" :class="{'animate-spin': loading}">refresh</span>
          </button>
          <!-- Toggle view mode -->
          <button
            @click="viewMode = viewMode === 'grouped' ? 'flat' : 'grouped'"
            class="px-4 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-all flex items-center gap-2"
            :title="viewMode === 'grouped' ? 'Vista plana' : 'Vista agrupada por idioma'"
          >
            <span class="material-symbols-outlined text-lg">{{ viewMode === 'grouped' ? 'view_list' : 'account_tree' }}</span>
            {{ viewMode === 'grouped' ? 'Vista Plana' : 'Vista Agrupada' }}
          </button>
        </div>
      </div>

      <!-- Grouped View -->
      <div v-if="viewMode === 'grouped'" class="divide-y divide-slate-100 dark:divide-slate-800">
        <div v-if="tours.length === 0 && !loading" class="px-6 py-20 text-center">
          <div class="flex flex-col items-center gap-3 text-slate-400">
            <span class="material-symbols-outlined text-5xl opacity-20">search_off</span>
            <p class="text-sm font-medium">No se encontraron tours con los criterios de búsqueda.</p>
          </div>
        </div>

        <div v-for="tour in tours" :key="tour.id" class="group/tour">
          <!-- Tour Group Header -->
          <div
            class="px-6 py-4 bg-slate-50/80 dark:bg-slate-800/30 flex items-center gap-4 cursor-pointer hover:bg-slate-100/80 dark:hover:bg-slate-800/50 transition-colors"
            @click="toggleExpand(tour.id)"
          >
            <!-- Thumbnail -->
            <div class="w-10 h-10 rounded-lg bg-slate-200 dark:bg-slate-700 overflow-hidden shrink-0 shadow-sm border border-slate-100 dark:border-slate-800">
              <img v-if="tour.thumbnail" :src="tour.thumbnail" class="w-full h-full object-cover" />
              <div v-else class="w-full h-full flex items-center justify-center text-slate-400">
                <span class="material-symbols-outlined text-sm">image</span>
              </div>
            </div>

            <!-- Tour Reference Name & Code -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <p class="text-sm font-black text-slate-900 dark:text-white truncate">
                  {{ getTourReferenceName(tour) }}
                </p>
                <span class="px-2 py-0.5 rounded-md bg-slate-200 dark:bg-slate-700 text-[9px] font-black font-mono text-slate-500 dark:text-slate-400">
                  {{ tour.code }}
                </span>
              </div>
              <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">
                {{ tour.service_type }} · {{ (tour.translations_summary || []).length }} {{ (tour.translations_summary || []).length === 1 ? 'idioma' : 'idiomas' }}
              </p>
            </div>

            <!-- Status -->
            <div class="flex items-center gap-2">
              <div class="size-2 rounded-full" :class="tour.active ? 'bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.5)]' : 'bg-slate-300'"></div>
              <span :class="tour.active ? 'text-green-600 dark:text-green-400' : 'text-slate-400'" class="text-[10px] font-black uppercase tracking-tighter">
                {{ tour.active ? 'Activo' : 'Inactivo' }}
              </span>
            </div>

            <!-- Language badges -->
            <div class="flex gap-1">
              <span
                v-for="lang in tour.available_languages || []"
                :key="lang.id"
                class="text-[9px] bg-primary/10 text-primary font-black px-1.5 py-0.5 rounded border border-primary/20"
                :title="lang.country"
              >
                {{ lang.code }}
              </span>
            </div>

            <!-- Actions: Clone + Delete Tour + Expand -->
            <div class="flex items-center gap-1">
              <button @click.stop="openCloneModal(tour)" class="p-1.5 text-slate-400 hover:text-green-500 transition-colors hover:bg-green-50 dark:hover:bg-green-500/10 rounded-lg" title="Agregar idioma">
                <span class="material-symbols-outlined text-base">translate</span>
              </button>
              <button @click.stop="confirmDeleteTour(tour)" class="p-1.5 text-slate-400 hover:text-red-500 transition-colors hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg" title="Eliminar tour completo">
                <span class="material-symbols-outlined text-base">delete</span>
              </button>
              <span class="material-symbols-outlined text-slate-400 transition-transform duration-200 text-lg" :class="expandedTours.has(tour.id) ? 'rotate-180' : ''">
                expand_more
              </span>
            </div>
          </div>

          <!-- Translation Rows (expanded) -->
          <transition name="expand">
            <div v-if="expandedTours.has(tour.id)" class="bg-white dark:bg-slate-900">
              <div
                v-for="tr in (tour.translations_summary || [])"
                :key="tr.translation_id"
                class="flex items-center gap-4 px-6 py-3 border-t border-slate-100/60 dark:border-slate-800/60 hover:bg-blue-50/40 dark:hover:bg-slate-800/20 transition-colors group/row"
              >
                <!-- Indent + Flag -->
                <div class="w-10 flex justify-center shrink-0">
                  <span class="text-lg">{{ getLanguageFlag(tr.language_code) }}</span>
                </div>

                <!-- Language Badge -->
                <span
                  class="px-2 py-1 rounded-md text-[10px] font-black uppercase shrink-0"
                  :class="tr.language_code === getPrimaryLanguageCode(tour)
                    ? 'bg-primary text-white'
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700'"
                >
                  {{ tr.language_code }}
                  <span v-if="tr.language_code === getPrimaryLanguageCode(tour)" class="ml-0.5 opacity-70">★</span>
                </span>

                <!-- Title -->
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 truncate">
                    {{ tr.title || '(Sin título)' }}
                  </p>
                  <p class="text-[10px] text-slate-400 font-mono truncate">
                    /{{ tr.language_code?.toLowerCase() }}/{{ tr.slug || '...' }}
                  </p>
                </div>

                <!-- Actions per translation -->
                <div class="flex items-center gap-1 sm:opacity-0 group-hover/row:opacity-100 transition-opacity">
                  <NuxtLink
                    :to="`/admin/tours/${tour.id}/edit?lang=${tr.language_code}`"
                    class="p-1.5 text-slate-400 hover:text-primary transition-colors hover:bg-primary/5 rounded-lg"
                    title="Editar esta traducción"
                  >
                    <span class="material-symbols-outlined text-base">edit</span>
                  </NuxtLink>
                  <button
                    v-if="(tour.translations_summary || []).length > 1"
                    @click="confirmDeleteTranslation(tour, tr)"
                    class="p-1.5 text-slate-400 hover:text-red-500 transition-colors hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg"
                    title="Eliminar esta traducción"
                  >
                    <span class="material-symbols-outlined text-base">delete</span>
                  </button>
                </div>
              </div>

              <!-- Add Translation Row -->
              <div
                @click="openCloneModal(tour)"
                class="flex items-center gap-4 px-6 py-2.5 border-t border-dashed border-slate-200/60 dark:border-slate-700/60 cursor-pointer hover:bg-green-50/40 dark:hover:bg-green-500/5 transition-colors"
              >
                <div class="w-10 flex justify-center shrink-0">
                  <span class="material-symbols-outlined text-green-500 text-base">add_circle</span>
                </div>
                <p class="text-xs font-semibold text-green-600 dark:text-green-400">Agregar idioma...</p>
              </div>
            </div>
          </transition>
        </div>
      </div>

      <!-- Flat View (original table) -->
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider">
            <tr>
              <th class="px-6 py-4">Tour / Producto</th>
              <th class="px-6 py-4">Código</th>
              <th class="px-6 py-4">Idiomas</th>
              <th class="px-6 py-4">Estado</th>
              <th class="px-6 py-4 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <tr v-if="tours.length === 0 && !loading">
              <td colspan="5" class="px-6 py-20 text-center">
                 <div class="flex flex-col items-center gap-3 text-slate-400">
                    <span class="material-symbols-outlined text-5xl opacity-20">search_off</span>
                    <p class="text-sm font-medium">No se encontraron tours con los criterios de búsqueda.</p>
                 </div>
              </td>
            </tr>
            <tr v-for="tour in tours" :key="tour.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors group">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-12 h-12 rounded-lg bg-slate-200 dark:bg-slate-700 overflow-hidden shrink-0 shadow-sm border border-slate-100 dark:border-slate-800">
                    <img v-if="tour.thumbnail" :src="tour.thumbnail" class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full flex items-center justify-center text-slate-400">
                      <span class="material-symbols-outlined">image</span>
                    </div>
                  </div>
                  <div>
                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ tour.title }}</p>
                    <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">{{ tour.service_type }}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="px-2.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-[10px] font-black font-mono text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                  {{ tour.code }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex gap-1.5 flex-wrap">
                   <span
                     v-for="lang in tour.available_languages || []"
                     :key="lang.id"
                     class="text-[9px] bg-primary/10 text-primary font-black px-1.5 py-0.5 rounded border border-primary/20"
                     :title="lang.country"
                   >
                     {{ lang.code }}
                   </span>
                   <span v-if="!tour.available_languages || tour.available_languages.length === 0" class="text-[9px] text-slate-400 italic">Sin idiomas</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                   <div class="size-2 rounded-full" :class="tour.active ? 'bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.5)]' : 'bg-slate-300'"></div>
                   <span :class="tour.active ? 'text-green-600 dark:text-green-400' : 'text-slate-400'" class="text-[10px] font-black uppercase tracking-tighter">
                     {{ tour.active ? 'Activo' : 'Inactivo' }}
                   </span>
                </div>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-1 sm:opacity-0 group-hover:opacity-100 transition-opacity">
                  <NuxtLink :to="`/admin/tours/${tour.id}/edit`" class="p-2 text-slate-400 hover:text-primary transition-colors hover:bg-primary/5 rounded-xl block" title="Editar">
                    <span class="material-symbols-outlined text-lg">edit</span>
                  </NuxtLink>
                  <button @click="openCloneModal(tour)" class="p-2 text-slate-400 hover:text-green-500 transition-colors hover:bg-green-50 rounded-xl" title="Clonar">
                    <span class="material-symbols-outlined text-lg">content_copy</span>
                  </button>
                  <button @click="confirmDeleteTour(tour)" class="p-2 text-slate-400 hover:text-red-500 transition-colors hover:bg-red-50 rounded-xl" title="Eliminar">
                    <span class="material-symbols-outlined text-lg">delete</span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="meta" class="p-6 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs font-bold text-slate-500 uppercase tracking-widest">
        <p>Mostrando {{ meta.from }}-{{ meta.to }} de {{ meta.total }} tours</p>
        <div class="flex gap-2">
          <button
            @click="changePage(meta.current_page - 1)"
            :disabled="meta.current_page === 1"
            class="w-10 h-10 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-center hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
          >
            <span class="material-symbols-outlined text-sm">chevron_left</span>
          </button>

          <button
            v-for="page in displayedPages"
            :key="page"
            @click="changePage(page)"
            class="w-10 h-10 rounded-xl font-black transition-all"
            :class="meta.current_page === page ? 'bg-primary text-white shadow-lg shadow-primary/30 scale-110' : 'border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800'"
          >
            {{ page }}
          </button>

          <button
            @click="changePage(meta.current_page + 1)"
            :disabled="meta.current_page === meta.last_page"
            class="w-10 h-10 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-center hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
          >
            <span class="material-symbols-outlined text-sm">chevron_right</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Clone Tour Modal -->
    <Teleport to="body">
      <div v-if="showCloneModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeCloneModal"></div>

        <!-- Modal -->
        <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-xl p-6 animate-in fade-in zoom-in duration-200">
          <!-- Header -->
          <div class="mb-6">
            <div class="flex items-center justify-between mb-2">
              <h3 class="text-lg font-black text-slate-900 dark:text-white">Agregar Idioma</h3>
              <button @click="closeCloneModal" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                <span class="material-symbols-outlined text-slate-400">close</span>
              </button>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400">
              Tour: <span class="font-bold">{{ selectedTour?.title }}</span>
            </p>
          </div>

          <!-- Language Selection -->
          <div class="mb-6">
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2 block">
              Idioma de destino
            </label>
            <div v-if="allLanguages.length === 0" class="text-center py-4 text-sm text-slate-400">
              Cargando idiomas disponibles...
            </div>
            <div v-else-if="cloneAvailableLanguages.length === 0" class="text-center py-8">
              <span class="material-symbols-outlined text-4xl text-green-500 mb-2">check_circle</span>
              <p class="text-sm font-bold text-slate-700 dark:text-slate-300">¡Este tour ya está traducido a todos los idiomas disponibles!</p>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">No hay más idiomas para agregar</p>
            </div>
            <div v-else class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-2">
              <button
                v-for="lang in cloneAvailableLanguages"
                :key="lang.id"
                @click="selectedLanguage = lang"
                :class="selectedLanguage?.id === lang.id
                  ? 'bg-primary text-white shadow-lg shadow-primary/20 border-primary'
                  : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600 border-slate-200 dark:border-slate-700'"
                class="p-3 rounded-lg font-bold transition-all flex flex-col items-center gap-1 border-2"
                :title="lang.country"
              >
                <span class="text-lg leading-none">{{ getLanguageFlag(lang.code) }}</span>
                <span class="text-[9px] font-mono font-black">{{ lang.code }}</span>
              </button>
            </div>
          </div>

          <!-- Clone Type Selection -->
          <div class="mb-6">
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2 block">
              Tipo de clonación
            </label>
            <div class="grid grid-cols-2 gap-3">
              <button
                @click="cloneType = 'manual'"
                :class="cloneType === 'manual'
                  ? 'border-2 border-primary bg-primary/5'
                  : 'border-2 border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'"
                class="p-4 rounded-lg transition-all text-left"
              >
                <div class="flex items-start gap-3">
                  <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-blue-500 text-base">edit_note</span>
                  </div>
                  <div class="flex-1">
                    <h4 class="font-bold text-sm text-slate-900 dark:text-white mb-1">Manual</h4>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed">Copia datos y permite traducir manualmente</p>
                  </div>
                </div>
              </button>
              <button
                @click="cloneType = 'ai'"
                :class="cloneType === 'ai'
                  ? 'border-2 border-primary bg-primary/5'
                  : 'border-2 border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'"
                class="p-4 rounded-lg transition-all text-left relative overflow-hidden"
              >
                <div class="absolute top-2 right-2">
                  <span class="px-1.5 py-0.5 bg-gradient-to-r from-purple-500 to-pink-500 text-white text-[8px] font-black rounded-full uppercase">IA</span>
                </div>
                <div class="flex items-start gap-3">
                  <div class="w-8 h-8 rounded-lg bg-purple-500/10 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-purple-500 text-base">auto_awesome</span>
                  </div>
                  <div class="flex-1">
                    <h4 class="font-bold text-sm text-slate-900 dark:text-white mb-1">Con IA</h4>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed">Traducción automática con inteligencia artificial</p>
                  </div>
                </div>
              </button>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-2">
            <button @click="closeCloneModal" class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-all">
              Cancelar
            </button>
            <button
              @click="performClone"
              :disabled="!selectedLanguage || !cloneType || cloning"
              class="px-4 py-2 bg-primary text-white text-sm font-bold rounded-lg shadow-lg shadow-primary/20 hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              <span v-if="cloning" class="material-symbols-outlined animate-spin text-base">progress_activity</span>
              <span v-else class="material-symbols-outlined text-base">translate</span>
              {{ cloning ? 'Agregando...' : 'Agregar Traducción' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

definePageMeta({
  layout: 'admin'
})

interface Tour {
  id: number
  code: string
  title: string
  thumbnail: string | null
  service_type: string
  active: boolean
  available_languages?: { id: number; code: string; country: string }[]
  translations_summary?: {
    translation_id: number
    language_id: number
    language_code: string
    language_country: string
    title: string
    slug: string
    short_description: string
  }[]
  primary_language?: { id: number; code: string }
}

interface Meta {
  current_page: number
  from: number
  last_page: number
  per_page: number
  to: number
  total: number
}

const config = useRuntimeConfig()
const API_BASE_URL = config.public.apiUrl

// States
const tours = ref<Tour[]>([])
const meta = ref<Meta | null>(null)
const loading = ref(false)
const searchQuery = ref('')
const currentPage = ref(1)
const viewMode = ref<'grouped' | 'flat'>('grouped')
const expandedTours = ref<Set<number>>(new Set())

// Clone Modal States
const showCloneModal = ref(false)
const selectedTour = ref<Tour | null>(null)
const selectedLanguage = ref<any>(null)
const cloneType = ref<'manual' | 'ai'>('manual')
const cloning = ref(false)

const allLanguages = ref<any[]>([])

const cloneAvailableLanguages = computed(() => {
    if (!selectedTour.value || !selectedTour.value.available_languages) {
        return allLanguages.value
    }
    const existingLanguageIds = selectedTour.value.available_languages.map((lang: any) => lang.id)
    return allLanguages.value.filter(lang => !existingLanguageIds.includes(lang.id))
})

const languageFlags: Record<string, string> = {
  'ES': '🇪🇸', 'EN': '🇬🇧', 'PT': '🇵🇹', 'FR': '🇫🇷',
  'DE': '🇩🇪', 'IT': '🇮🇹', 'RU': '🇷🇺', 'CN': '🇨🇳',
  'JP': '🇯🇵', 'KR': '🇰🇷'
}

const getLanguageFlag = (code: string) => languageFlags[code] || '🌐'

const getPrimaryLanguageCode = (tour: Tour) => {
  if (tour.primary_language?.code) return tour.primary_language.code
  // Fallback: extract from code prefix
  const match = tour.code?.match(/^([A-Z]{2})/)
  return match ? match[1] : 'ES'
}

const getTourReferenceName = (tour: Tour) => {
  // Use the primary language translation title, or the tour's default title
  const primaryCode = getPrimaryLanguageCode(tour)
  const primaryTr = (tour.translations_summary || []).find(t => t.language_code === primaryCode)
  return primaryTr?.title || tour.title || tour.code
}

const toggleExpand = (tourId: number) => {
  if (expandedTours.value.has(tourId)) {
    expandedTours.value.delete(tourId)
  } else {
    expandedTours.value.add(tourId)
  }
  // Force reactivity
  expandedTours.value = new Set(expandedTours.value)
}

// Fetch Logic
const fetchTours = async (page = 1, search = '') => {
  loading.value = true
  try {
    const params = new URLSearchParams({
      page: page.toString(),
      per_page: '10',
      search: search
    })

    const response: any = await $fetch(`${API_BASE_URL}/tours?${params.toString()}`)

    if (response && response.success) {
      tours.value = response.data
      meta.value = response.meta
      // Auto-expand all tours in grouped view
      if (viewMode.value === 'grouped') {
        tours.value.forEach(t => expandedTours.value.add(t.id))
        expandedTours.value = new Set(expandedTours.value)
      }
    }
  } catch (error) {
    console.error("Error fetching tours:", error)
  } finally {
    loading.value = false
  }
}

let debounceTimer: any = null
const debounceSearch = () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        currentPage.value = 1
        fetchTours(1, searchQuery.value)
    }, 500)
}

const refreshData = () => {
    fetchTours(currentPage.value, searchQuery.value)
}

const changePage = (page: number) => {
    if (page < 1 || (meta.value && page > meta.value.last_page)) return
    currentPage.value = page
    fetchTours(page, searchQuery.value)
}

const displayedPages = computed(() => {
    if (!meta.value) return []
    const total = meta.value.last_page
    const current = meta.value.current_page
    const pages = []
    for (let i = Math.max(1, current - 1); i <= Math.min(total, current + 1); i++) {
        pages.push(i)
    }
    return pages
})

// Delete entire tour
const confirmDeleteTour = (tour: any) => {
    if (confirm(`¿Eliminar el tour "${tour.title}" COMPLETO con todas sus traducciones? Esta acción no se puede deshacer.`)) {
        handleDeleteTour(tour.id)
    }
}

const handleDeleteTour = async (id: number) => {
    try {
        const response: any = await $fetch(`${API_BASE_URL}/tours/${id}`, { method: 'DELETE' })
        if (response && response.success) {
            alert('Tour eliminado correctamente')
            refreshData()
        }
    } catch (error) {
        console.error("Delete failed", error)
        alert('Error al intentar eliminar el tour')
    }
}

// Delete single translation
const confirmDeleteTranslation = (tour: Tour, tr: any) => {
    const langName = tr.language_country || tr.language_code
    if (confirm(`¿Eliminar la traducción en ${langName} de "${tr.title}"?`)) {
        handleDeleteTranslation(tour.id, tr.language_id)
    }
}

const handleDeleteTranslation = async (tourId: number, languageId: number) => {
    try {
        const response: any = await $fetch(`${API_BASE_URL}/tours/${tourId}/translation/${languageId}`, { method: 'DELETE' })
        if (response && response.success) {
            if (response.tour_deleted) {
                alert('Era la última traducción. El tour ha sido eliminado.')
            } else {
                alert('Traducción eliminada correctamente')
            }
            refreshData()
        }
    } catch (error: any) {
        console.error("Delete translation failed", error)
        alert(error.data?.message || 'Error al eliminar la traducción')
    }
}

// Fetch all available languages from API
const fetchLanguages = async () => {
    try {
        const response: any = await $fetch(`${API_BASE_URL}/languages`)
        if (response && response.success) {
            allLanguages.value = response.data.map((lang: any) => ({
                ...lang,
                flag: languageFlags[lang.code] || '🌐'
            }))
        }
    } catch (error) {
        console.error('Error fetching languages:', error)
        allLanguages.value = [
            { id: 1, code: 'ES', country: 'Español', flag: '🇪🇸' },
            { id: 2, code: 'EN', country: 'English', flag: '🇬🇧' },
            { id: 3, code: 'FR', country: 'Français', flag: '🇫🇷' },
            { id: 4, code: 'DE', country: 'Deutsch', flag: '🇩🇪' },
            { id: 5, code: 'PT', country: 'Português', flag: '🇵🇹' },
            { id: 6, code: 'IT', country: 'Italiano', flag: '🇮🇹' }
        ]
    }
}

// Clone Modal Functions
const openCloneModal = async (tour: Tour) => {
    selectedTour.value = tour
    selectedLanguage.value = null
    cloneType.value = 'manual'
    if (allLanguages.value.length === 0) {
        await fetchLanguages()
    }
    showCloneModal.value = true
}

const closeCloneModal = () => {
    showCloneModal.value = false
    selectedTour.value = null
    selectedLanguage.value = null
    cloneType.value = 'manual'
}

const performClone = async () => {
    if (!selectedTour.value || !selectedLanguage.value) {
        alert('Por favor selecciona un idioma')
        return
    }

    cloning.value = true

    try {
        const endpoint = cloneType.value === 'ai'
            ? `/tours/${selectedTour.value.id}/clone-ai`
            : `/tours/${selectedTour.value.id}/clone`

        const response: any = await $fetch(`${API_BASE_URL}${endpoint}`, {
            method: 'POST',
            body: {
                language_id: selectedLanguage.value.id,
                clone_type: cloneType.value
            }
        })

        if (response && response.success) {
            closeCloneModal()
            alert(response.message || `Traducción ${selectedLanguage.value.country} agregada exitosamente`)

            if (response.data?.redirect_url) {
                await navigateTo(response.data.redirect_url)
            } else if (response.data?.tour_id) {
                await navigateTo(`/admin/tours/${response.data.tour_id}/edit`)
            }

            await refreshData()
        } else {
            alert('Error: ' + (response.message || 'Error desconocido'))
        }
    } catch (error: any) {
        console.error('Error cloning tour:', error)
        if (error.data?.message) {
            alert('Error: ' + error.data.message)
        } else {
            alert('Error al agregar la traducción al tour. Por favor intenta de nuevo.')
        }
    } finally {
        cloning.value = false
    }
}

onMounted(() => {
  fetchTours()
})
</script>

<style scoped>
.glass-card {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(20px);
}
.dark .glass-card {
  background: rgba(15, 23, 42, 0.5);
}

.expand-enter-active,
.expand-leave-active {
  transition: all 0.2s ease;
  overflow: hidden;
}
.expand-enter-from,
.expand-leave-to {
  opacity: 0;
  max-height: 0;
}
.expand-enter-to,
.expand-leave-from {
  opacity: 1;
  max-height: 500px;
}

button:disabled {
    filter: grayscale(1);
    opacity: 0.5;
}
</style>
