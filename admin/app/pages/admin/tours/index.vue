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
          <button class="px-4 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-all flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">filter_list</span>
            Filtros
          </button>
        </div>
      </div>

      <div class="overflow-x-auto">
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
                  <button @click="confirmDelete(tour)" class="p-2 text-slate-400 hover:text-red-500 transition-colors hover:bg-red-50 rounded-xl" title="Eliminar">
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

        <!-- Modal - More Compact -->
        <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-xl p-6 animate-in fade-in zoom-in duration-200">
          <!-- Header - Smaller -->
          <div class="mb-6">
            <div class="flex items-center justify-between mb-2">
              <h3 class="text-lg font-black text-slate-900 dark:text-white">Clonar Tour</h3>
              <button @click="closeCloneModal" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                <span class="material-symbols-outlined text-slate-400">close</span>
              </button>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400">
              Clonando: <span class="font-bold">{{ selectedTour?.title }}</span>
            </p>
          </div>

          <!-- Language Selection - Compact Grid -->
          <div class="mb-6">
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2 block">
              Idioma de destino
            </label>
            <div v-if="allLanguages.length === 0" class="text-center py-4 text-sm text-slate-400">
              Cargando idiomas disponibles...
            </div>
            <div v-else-if="availableLanguages.length === 0" class="text-center py-8">
              <span class="material-symbols-outlined text-4xl text-green-500 mb-2">check_circle</span>
              <p class="text-sm font-bold text-slate-700 dark:text-slate-300">¡Este tour ya está traducido a todos los idiomas disponibles!</p>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">No hay más idiomas para agregar</p>
            </div>
            <div v-else class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-2">
              <button
                v-for="lang in availableLanguages"
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

          <!-- Clone Type Selection - Compact -->
          <div class="mb-6">
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2 block">
              Tipo de clonación
            </label>
            <div class="grid grid-cols-2 gap-3">
              <!-- Manual Clone -->
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
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed">
                      Copia datos y permite traducir manualmente
                    </p>
                  </div>
                </div>
              </button>

              <!-- AI Translation Clone -->
              <button
                @click="cloneType = 'ai'"
                :class="cloneType === 'ai'
                  ? 'border-2 border-primary bg-primary/5'
                  : 'border-2 border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'"
                class="p-4 rounded-lg transition-all text-left relative overflow-hidden"
              >
                <div class="absolute top-2 right-2">
                  <span class="px-1.5 py-0.5 bg-gradient-to-r from-purple-500 to-pink-500 text-white text-[8px] font-black rounded-full uppercase">
                    IA
                  </span>
                </div>
                <div class="flex items-start gap-3">
                  <div class="w-8 h-8 rounded-lg bg-purple-500/10 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-purple-500 text-base">auto_awesome</span>
                  </div>
                  <div class="flex-1">
                    <h4 class="font-bold text-sm text-slate-900 dark:text-white mb-1">Con IA</h4>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed">
                      Traducción automática con inteligencia artificial
                    </p>
                  </div>
                </div>
              </button>
            </div>
          </div>

          <!-- Actions - Compact -->
          <div class="flex justify-end gap-2">
            <button
              @click="closeCloneModal"
              class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-all"
            >
              Cancelar
            </button>
            <button
              @click="performClone"
              :disabled="!selectedLanguage || !cloneType || cloning"
              class="px-4 py-2 bg-primary text-white text-sm font-bold rounded-lg shadow-lg shadow-primary/20 hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              <span v-if="cloning" class="material-symbols-outlined animate-spin text-base">progress_activity</span>
              <span v-else class="material-symbols-outlined text-base">content_copy</span>
              {{ cloning ? 'Clonando...' : 'Clonar' }}
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

// Interfaces for API response
interface Tour {
  id: number
  code: string
  title: string
  thumbnail: string | null
  service_type: string
  active: boolean
  // Add other fields if needed
}

interface Meta {
  current_page: number
  from: number
  last_page: number
  per_page: number
  to: number
  total: number
}

// Centralized API Configuration
const config = useRuntimeConfig()
const API_BASE_URL = config.public.apiUrl

// States
const tours = ref<Tour[]>([])
const meta = ref<Meta | null>(null)
const loading = ref(false)
const searchQuery = ref('')
const currentPage = ref(1)

// Clone Modal States
const showCloneModal = ref(false)
const selectedTour = ref<Tour | null>(null)
const selectedLanguage = ref<any>(null)
const cloneType = ref<'manual' | 'ai'>('manual')
const cloning = ref(false)

// All available languages - will be loaded from API
const allLanguages = ref<any[]>([])
// Available languages for cloning (filtered by tour's existing translations)
const availableLanguages = computed(() => {
    if (!selectedTour.value || !selectedTour.value.available_languages) {
        return allLanguages.value
    }

    // Filter out languages that already have translations for this tour
    const existingLanguageIds = selectedTour.value.available_languages.map((lang: any) => lang.id)
    return allLanguages.value.filter(lang => !existingLanguageIds.includes(lang.id))
})

// Language flag mapping
const languageFlags: Record<string, string> = {
  'ES': '🇪🇸',
  'EN': '🇬🇧',
  'PT': '🇵🇹',
  'FR': '🇫🇷',
  'DE': '🇩🇪',
  'IT': '🇮🇹',
  'RU': '🇷🇺',
  'CN': '🇨🇳',
  'JP': '🇯🇵',
  'KR': '🇰🇷'
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
    
    // In a real app, inject Sanctum token from authStore
    const response: any = await $fetch(`${API_BASE_URL}/tours?${params.toString()}`)
    
    if (response && response.success) {
      tours.value = response.data
      meta.value = response.meta
    }
  } catch (error) {
    console.error("Error fetching tours:", error)
    // Fallback to empty state or alert
  } finally {
    loading.value = false
  }
}

// Interactivity
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

// Pagination UI Helpers
const displayedPages = computed(() => {
    if (!meta.value) return []
    const total = meta.value.last_page
    const current = meta.value.current_page
    const pages = []
    
    // Simple pagination logic: show current, and one before/after
    for (let i = Math.max(1, current - 1); i <= Math.min(total, current + 1); i++) {
        pages.push(i)
    }
    return pages
})

const confirmDelete = (tour: any) => {
    if (confirm(`¿Estás seguro de que deseas eliminar el tour "${tour.title}"? Esta acción no se puede deshacer.`)) {
        handleDelete(tour.id)
    }
}

const handleDelete = async (id: number) => {
    try {
        const response: any = await $fetch(`${API_BASE_URL}/tours/${id}`, {
            method: 'DELETE'
        })
        if (response && response.success) {
            alert('Tour eliminado correctamente')
            refreshData()
        }
    } catch (error) {
        console.error("Delete failed", error)
        alert('Error al intentar eliminar el tour')
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
        // Fallback to basic languages if API fails
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
const getLanguageFlag = (code: string) => {
    return languageFlags[code] || '🌐'
}

const openCloneModal = async (tour: Tour) => {
    selectedTour.value = tour
    selectedLanguage.value = null
    cloneType.value = 'manual'

    // Load all available languages if not already loaded
    if (allLanguages.value.length === 0) {
        await fetchLanguages()
    }

    // The computed property 'availableLanguages' will automatically
    // filter out languages that already have translations for this tour

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

            // Show success message
            alert(response.message || `Traducción ${selectedLanguage.value.country} agregada exitosamente`)

            // Redirect to edit the tour with the new language
            if (response.data?.redirect_url) {
                await navigateTo(response.data.redirect_url)
            } else if (response.data?.tour_id) {
                // Fallback to tour edit page
                await navigateTo(`/admin/tours/${response.data.tour_id}/edit`)
            }

            // Refresh the list to show any updates
            await refreshData()
        } else {
            alert('Error: ' + (response.message || 'Error desconocido'))
        }
    } catch (error: any) {
        console.error('Error cloning tour:', error)

        // Handle specific error messages
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

.material-symbols-outlined.filled {
  font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}

button:disabled {
    filter: grayscale(1);
    opacity: 0.5;
}
</style>
