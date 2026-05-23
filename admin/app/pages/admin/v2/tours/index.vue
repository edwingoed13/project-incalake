<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

definePageMeta({
  layout: 'admin-v2',
  middleware: 'auth',
})

interface Translation {
  translation_id: number
  language_id: number
  language_code: string
  language_country: string
  title: string
  slug: string
  short_description: string
}

interface Tour {
  id: number
  code: string
  title: string
  thumbnail: string | null
  service_type: string
  active: boolean
  status?: 'draft' | 'published' | 'archived' | null
  city?: { id: number; name: string; slug?: string }
  available_languages?: { id: number; code: string; country: string }[]
  translations_summary?: Translation[]
  primary_language?: { id: number; code: string }
}

type StatusFilter = 'all' | 'draft' | 'published' | 'archived'

interface Meta {
  current_page: number
  from: number
  last_page: number
  per_page: number
  to: number
  total: number
}

interface Language {
  id: number
  code: string
  country: string
  flag?: string
}

const config = useRuntimeConfig()
const API_BASE_URL = config.public.apiUrl
const FRONTEND_URL = (config.public as any).frontendUrl || 'https://incalake-frontend.vercel.app'
const toast = useToast()
const { confirm } = useConfirm()

const slugifyCity = (name: string) => (name || '')
  .toLowerCase()
  .normalize('NFD').replace(/\p{Diacritic}/gu, '')
  .replace(/[^a-z0-9\s-]/g, '')
  .trim()
  .replace(/\s+/g, '-')

// Build the public preview URL for a specific translation of a tour.
// Frontend uses /{lang}/{city.slug}/{tour.slug}
const getTranslationPreviewUrl = (tour: any, tr: Translation): string => {
  if (!tr?.slug) return ''
  const lang = (tr.language_code || 'es').toLowerCase()
  const citySlug = tour?.city?.slug || slugifyCity(tour?.city?.name || '')
  if (!citySlug) return ''
  return `${FRONTEND_URL}/${lang}/${citySlug}/${tr.slug}`
}

const tours = ref<Tour[]>([])
const meta = ref<Meta | null>(null)
const loading = ref(false)
const searchQuery = ref('')
const statusFilter = ref<StatusFilter>('all')
const currentPage = ref(1)
const expandedTours = ref<Set<number>>(new Set())
const statusCounts = ref<Record<string, number>>({ all: 0, draft: 0, published: 0, archived: 0 })

const statusBadge = (s?: Tour['status']) => {
  if (s === 'published') return { label: 'Publicado', color: 'success' as const, icon: 'i-lucide-circle-check' }
  if (s === 'archived') return { label: 'Archivado', color: 'neutral' as const, icon: 'i-lucide-archive' }
  return { label: 'Borrador', color: 'warning' as const, icon: 'i-lucide-file-text' }
}

const statusTabs: { id: StatusFilter; label: string; icon: string }[] = [
  { id: 'all', label: 'Todos', icon: 'i-lucide-list' },
  { id: 'draft', label: 'Borradores', icon: 'i-lucide-file-text' },
  { id: 'published', label: 'Publicados', icon: 'i-lucide-circle-check' },
  { id: 'archived', label: 'Archivados', icon: 'i-lucide-archive' },
]

const showCloneModal = ref(false)
const selectedTour = ref<Tour | null>(null)
const selectedLanguage = ref<Language | null>(null)
const cloneType = ref<'manual' | 'ai'>('manual')
const cloning = ref(false)
const allLanguages = ref<Language[]>([])

const languageFlags: Record<string, string> = {
  ES: '🇪🇸', EN: '🇬🇧', PT: '🇵🇹', FR: '🇫🇷',
  DE: '🇩🇪', IT: '🇮🇹', RU: '🇷🇺', CN: '🇨🇳', JP: '🇯🇵', KR: '🇰🇷',
}
const getLanguageFlag = (code: string) => languageFlags[code] || '🌐'

const getPrimaryLanguageCode = (tour: Tour) => {
  if (tour.primary_language?.code) return tour.primary_language.code
  const match = tour.code?.match(/^([A-Z]{2})/)
  return match ? match[1] : 'ES'
}

const getTourReferenceName = (tour: Tour) => {
  const primaryCode = getPrimaryLanguageCode(tour)
  const primaryTr = (tour.translations_summary || []).find(t => t.language_code === primaryCode)
  return primaryTr?.title || tour.title || tour.code
}

const cloneAvailableLanguages = computed<Language[]>(() => {
  if (!selectedTour.value || !selectedTour.value.available_languages) return allLanguages.value
  const existing = selectedTour.value.available_languages.map(l => l.id)
  return allLanguages.value.filter(l => !existing.includes(l.id))
})

const toggleExpand = (tourId: number) => {
  const next = new Set(expandedTours.value)
  next.has(tourId) ? next.delete(tourId) : next.add(tourId)
  expandedTours.value = next
}

const fetchTours = async (page = 1, search = '') => {
  loading.value = true
  try {
    const params = new URLSearchParams({ page: String(page), per_page: '10', search })
    if (statusFilter.value !== 'all') params.set('status', statusFilter.value)
    const response: any = await $fetch(`${API_BASE_URL}/tours?${params}`)
    if (response?.success) {
      tours.value = response.data
      meta.value = response.meta
      if (response.status_counts) statusCounts.value = response.status_counts
    }
  } catch (err) {
    console.error('Error fetching tours:', err)
    toast.add({ title: 'Error', description: 'No se pudieron cargar los tours.', color: 'error', icon: 'i-lucide-alert-triangle' })
  } finally {
    loading.value = false
  }
}

const setStatusFilter = (id: StatusFilter) => {
  if (statusFilter.value === id) return
  statusFilter.value = id
  currentPage.value = 1
  fetchTours(1, searchQuery.value)
}

let debounceTimer: any = null
const debounceSearch = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    currentPage.value = 1
    fetchTours(1, searchQuery.value)
  }, 400)
}

const refreshData = () => fetchTours(currentPage.value, searchQuery.value)

const changePage = (page: number) => {
  if (!meta.value || page < 1 || page > meta.value.last_page) return
  currentPage.value = page
  fetchTours(page, searchQuery.value)
}

const confirmDeleteTour = async (tour: Tour) => {
  const langCount = (tour.translations_summary || []).length
  const langList = (tour.translations_summary || []).map(t => t.language_code).join(', ')

  // Primera confirmación
  const first = await confirm({
    title: `Eliminar tour ${tour.code}`,
    description: `Vas a eliminar "${tour.title}" y todas sus ${langCount} traducción${langCount === 1 ? '' : 'es'} (${langList}). Esta acción no se puede deshacer.`,
    confirmLabel: 'Continuar',
    confirmColor: 'error',
    confirmIcon: 'i-lucide-triangle-alert',
    icon: 'i-lucide-triangle-alert',
    iconColor: 'error',
  })
  if (!first) return

  // Segunda confirmación (doble seguridad para acción destructiva)
  const second = await confirm({
    title: '¿Estás completamente seguro?',
    description: `Se eliminarán: contenido en ${langCount} idiomas, imágenes, precios, disponibilidad, bloqueos, ofertas y reseñas asociadas. NO HAY MARCHA ATRÁS.`,
    confirmLabel: 'Sí, eliminar definitivamente',
    cancelLabel: 'No, mejor no',
    confirmColor: 'error',
    confirmIcon: 'i-lucide-trash-2',
    icon: 'i-lucide-skull',
    iconColor: 'error',
  })
  if (!second) return

  try {
    const response: any = await $fetch(`${API_BASE_URL}/tours/${tour.id}`, { method: 'DELETE' })
    if (response?.success) {
      toast.add({ title: 'Tour eliminado', description: `${tour.title} (${tour.code})`, icon: 'i-lucide-check-circle', color: 'success' })
      refreshData()
    }
  } catch (err) {
    toast.add({ title: 'Error', description: 'No se pudo eliminar el tour.', color: 'error', icon: 'i-lucide-alert-triangle' })
  }
}

const confirmDeleteTranslation = async (tour: Tour, tr: Translation) => {
  const langName = tr.language_country || tr.language_code
  const ok = await confirm({
    title: `Eliminar traducción en ${langName}`,
    description: `Vas a eliminar la traducción de "${tr.title}". Si es la última, el tour completo será eliminado.`,
    confirmLabel: 'Eliminar traducción',
    confirmColor: 'error',
    confirmIcon: 'i-lucide-trash-2',
    icon: 'i-lucide-triangle-alert',
    iconColor: 'error',
  })
  if (!ok) return
  try {
    const response: any = await $fetch(`${API_BASE_URL}/tours/${tour.id}/translation/${tr.language_id}`, { method: 'DELETE' })
    if (response?.success) {
      toast.add({
        title: response.tour_deleted ? 'Tour eliminado' : 'Traducción eliminada',
        description: response.tour_deleted ? 'Era la última traducción del tour.' : undefined,
        icon: 'i-lucide-check-circle',
        color: 'success',
      })
      refreshData()
    }
  } catch (err: any) {
    toast.add({ title: 'Error', description: err.data?.message || 'No se pudo eliminar.', color: 'error', icon: 'i-lucide-alert-triangle' })
  }
}

const fetchLanguages = async () => {
  try {
    const response: any = await $fetch(`${API_BASE_URL}/languages`)
    if (response?.success) {
      allLanguages.value = response.data.map((lang: Language) => ({ ...lang, flag: languageFlags[lang.code] || '🌐' }))
    }
  } catch {
    allLanguages.value = [
      { id: 1, code: 'ES', country: 'Español', flag: '🇪🇸' },
      { id: 2, code: 'EN', country: 'English', flag: '🇬🇧' },
      { id: 3, code: 'FR', country: 'Français', flag: '🇫🇷' },
      { id: 4, code: 'DE', country: 'Deutsch', flag: '🇩🇪' },
      { id: 5, code: 'PT', country: 'Português', flag: '🇵🇹' },
      { id: 6, code: 'IT', country: 'Italiano', flag: '🇮🇹' },
    ]
  }
}

const openCloneModal = async (tour: Tour) => {
  selectedTour.value = tour
  selectedLanguage.value = null
  cloneType.value = 'manual'
  if (allLanguages.value.length === 0) await fetchLanguages()
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
    toast.add({ title: 'Selecciona un idioma', color: 'warning', icon: 'i-lucide-info' })
    return
  }
  cloning.value = true
  try {
    const endpoint = cloneType.value === 'ai'
      ? `/tours/${selectedTour.value.id}/clone-ai`
      : `/tours/${selectedTour.value.id}/clone`
    const response: any = await $fetch(`${API_BASE_URL}${endpoint}`, {
      method: 'POST',
      body: { language_id: selectedLanguage.value.id, clone_type: cloneType.value },
    })
    if (response?.success) {
      toast.add({
        title: 'Traducción agregada',
        description: `${selectedLanguage.value.country} fue añadida exitosamente.`,
        icon: 'i-lucide-check-circle',
        color: 'success',
      })
      closeCloneModal()
      if (response.data?.redirect_url) await navigateTo(response.data.redirect_url)
      else if (response.data?.tour_id) await navigateTo(`/admin/v2/tours/${response.data.tour_id}/edit`)
      await refreshData()
    } else {
      toast.add({ title: 'Error', description: response.message || 'Error desconocido', color: 'error', icon: 'i-lucide-alert-triangle' })
    }
  } catch (err: any) {
    toast.add({ title: 'Error al clonar', description: err.data?.message || 'Intenta de nuevo.', color: 'error', icon: 'i-lucide-alert-triangle' })
  } finally {
    cloning.value = false
  }
}

const displayedPages = computed(() => {
  if (!meta.value) return []
  const total = meta.value.last_page
  const current = meta.value.current_page
  const pages: number[] = []
  for (let i = Math.max(1, current - 2); i <= Math.min(total, current + 2); i++) pages.push(i)
  return pages
})

const changeStatus = async (tour: Tour, status: 'draft' | 'published' | 'archived') => {
  const verb = status === 'published' ? 'Publicar' : status === 'archived' ? 'Archivar' : 'Mover a borrador'
  const ok = await confirm({
    title: `${verb} tour`,
    description: `Vas a ${verb.toLowerCase()} "${getTourReferenceName(tour)}".${status === 'published' ? ' Quedará visible en el sitio público.' : ''}`,
    confirmLabel: verb,
    confirmColor: status === 'archived' ? 'warning' : 'primary',
    confirmIcon: status === 'published' ? 'i-lucide-circle-check' : status === 'archived' ? 'i-lucide-archive' : 'i-lucide-file-text',
    icon: 'i-lucide-refresh-cw',
  })
  if (!ok) return
  try {
    await $fetch(`${API_BASE_URL}/admin/tours/${tour.id}/status`, { method: 'POST', body: { status } })
    toast.add({ title: 'Estado actualizado', icon: 'i-lucide-circle-check', color: 'success' })
    fetchTours(currentPage.value, searchQuery.value)
  } catch {
    toast.add({ title: 'Error', description: 'No se pudo cambiar el estado.', color: 'error', icon: 'i-lucide-alert-triangle' })
  }
}

const rowActions = (tour: Tour) => {
  const status: any = []
  if (tour.status !== 'published') {
    status.push({ label: 'Publicar', icon: 'i-lucide-circle-check', color: 'success' as const, onSelect: () => changeStatus(tour, 'published') })
  } else {
    status.push({ label: 'Despublicar (borrador)', icon: 'i-lucide-file-text', onSelect: () => changeStatus(tour, 'draft') })
  }
  if (tour.status !== 'archived') {
    status.push({ label: 'Archivar', icon: 'i-lucide-archive', color: 'warning' as const, onSelect: () => changeStatus(tour, 'archived') })
  } else {
    status.push({ label: 'Restaurar (borrador)', icon: 'i-lucide-archive-restore', onSelect: () => changeStatus(tour, 'draft') })
  }
  return [
    [{ label: 'Editar tour', icon: 'i-lucide-edit', to: `/admin/v2/tours/${tour.id}/edit` }],
    status,
    [{ label: 'Agregar idioma', icon: 'i-lucide-languages', onSelect: () => openCloneModal(tour) }],
    [{ label: 'Eliminar tour', icon: 'i-lucide-trash-2', color: 'error' as const, onSelect: () => confirmDeleteTour(tour) }],
  ]
}

onMounted(() => {
  fetchTours()
})
</script>

<template>
  <UDashboardPanel id="tours-v2">
    <template #header>
      <UDashboardNavbar title="Tours">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            icon="i-lucide-refresh-cw"
            color="neutral"
            variant="ghost"
            :loading="loading"
            @click="refreshData"
          >
            Actualizar
          </UButton>
          <UButton icon="i-lucide-plus" to="/admin/v2/tours/new/edit">Nuevo tour</UButton>
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="p-6 space-y-4">
        <!-- Header card -->
        <div class="flex items-end justify-between gap-4 flex-wrap">
          <div>
            <h2 class="text-2xl font-bold">Gestión de tours</h2>
            <p class="text-sm text-muted mt-1">
              <span v-if="meta">{{ meta.total }} tours · {{ meta.from }}-{{ meta.to }} mostrados</span>
              <span v-else>Cargando...</span>
            </p>
          </div>
          <UInput
            v-model="searchQuery"
            placeholder="Buscar por título o código..."
            icon="i-lucide-search"
            size="lg"
            class="w-full sm:w-96"
            @input="debounceSearch"
          >
            <template v-if="searchQuery" #trailing>
              <UButton
                icon="i-lucide-x"
                color="neutral"
                variant="link"
                size="xs"
                @click="searchQuery = ''; debounceSearch()"
              />
            </template>
          </UInput>
        </div>

        <!-- Status filter tabs -->
        <div class="flex gap-1 border-b border-default -mb-px overflow-x-auto">
          <button
            v-for="tab in statusTabs"
            :key="tab.id"
            type="button"
            class="px-3 py-2 text-xs font-bold flex items-center gap-1.5 border-b-2 transition-colors whitespace-nowrap"
            :class="statusFilter === tab.id
              ? 'border-primary text-primary'
              : 'border-transparent text-muted hover:text-default'"
            @click="setStatusFilter(tab.id)"
          >
            <UIcon :name="tab.icon" class="size-3.5" />
            {{ tab.label }}
            <span
              class="text-[10px] font-bold rounded-full px-1.5 min-w-[18px] text-center"
              :class="statusFilter === tab.id ? 'bg-primary/15 text-primary' : 'bg-elevated text-muted'"
            >
              {{ statusCounts[tab.id] ?? 0 }}
            </span>
          </button>
        </div>

        <!-- Tours list -->
        <UCard :ui="{ body: 'p-0' }">
          <!-- Loading state -->
          <div v-if="loading && tours.length === 0" class="p-6 space-y-3">
            <div v-for="i in 5" :key="i" class="flex items-center gap-4">
              <USkeleton class="size-10 rounded-lg" />
              <div class="flex-1 space-y-2">
                <USkeleton class="h-4 w-1/2" />
                <USkeleton class="h-3 w-1/4" />
              </div>
              <USkeleton class="h-6 w-20" />
            </div>
          </div>

          <!-- Empty state -->
          <div v-else-if="tours.length === 0" class="p-12 flex flex-col items-center text-center gap-3">
            <UIcon name="i-lucide-search-x" class="size-12 text-muted" />
            <p class="text-sm text-muted">No se encontraron tours con los criterios de búsqueda.</p>
            <UButton v-if="searchQuery" variant="outline" size="sm" @click="searchQuery = ''; debounceSearch()">
              Limpiar búsqueda
            </UButton>
          </div>

          <!-- Tour rows -->
          <ul v-else class="divide-y divide-default">
            <li v-for="tour in tours" :key="tour.id">
              <!-- Tour main row -->
              <div
                class="px-5 py-3 flex items-center gap-3 hover:bg-elevated/50 transition-colors cursor-pointer"
                @click="toggleExpand(tour.id)"
              >
                <UAvatar
                  v-if="tour.thumbnail"
                  :src="tour.thumbnail"
                  size="md"
                  :ui="{ root: 'rounded-lg' }"
                />
                <div v-else class="size-10 rounded-lg bg-elevated flex items-center justify-center shrink-0">
                  <UIcon name="i-lucide-image" class="size-5 text-muted" />
                </div>

                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <p class="text-sm font-bold truncate">{{ getTourReferenceName(tour) }}</p>
                    <UBadge color="neutral" variant="subtle" size="xs" class="font-mono">{{ tour.code }}</UBadge>
                  </div>
                  <p class="text-[10px] text-muted font-bold uppercase tracking-wider mt-0.5">
                    {{ tour.service_type }} ·
                    {{ (tour.translations_summary || []).length }}
                    {{ (tour.translations_summary || []).length === 1 ? 'idioma' : 'idiomas' }}
                  </p>
                </div>

                <UBadge
                  :color="statusBadge(tour.status).color"
                  variant="subtle"
                  size="sm"
                  :icon="statusBadge(tour.status).icon"
                >
                  {{ statusBadge(tour.status).label }}
                </UBadge>

                <UButton
                  v-if="tour.status === 'draft'"
                  :to="`/admin/v2/tours/${tour.id}/edit`"
                  icon="i-lucide-pencil-line"
                  color="warning"
                  variant="soft"
                  size="xs"
                  class="hidden lg:inline-flex"
                  title="Continuar editando este borrador"
                  @click.stop
                >
                  Continuar
                </UButton>

                <div class="hidden md:flex gap-1 max-w-[200px] flex-wrap">
                  <UBadge
                    v-for="lang in tour.available_languages || []"
                    :key="lang.id"
                    color="primary"
                    variant="subtle"
                    size="xs"
                    :title="lang.country"
                  >
                    {{ lang.code }}
                  </UBadge>
                </div>

                <UDropdownMenu :items="rowActions(tour)" :content="{ align: 'end' }">
                  <UButton
                    icon="i-lucide-ellipsis-vertical"
                    color="neutral"
                    variant="ghost"
                    size="sm"
                    @click.stop
                  />
                </UDropdownMenu>

                <UIcon
                  name="i-lucide-chevron-down"
                  class="size-4 text-muted transition-transform"
                  :class="{ 'rotate-180': expandedTours.has(tour.id) }"
                />
              </div>

              <!-- Translations (expanded) -->
              <Transition
                enter-active-class="transition-all duration-200 ease-out overflow-hidden"
                leave-active-class="transition-all duration-200 ease-in overflow-hidden"
                enter-from-class="max-h-0 opacity-0"
                enter-to-class="max-h-[600px] opacity-100"
                leave-from-class="max-h-[600px] opacity-100"
                leave-to-class="max-h-0 opacity-0"
              >
                <div v-if="expandedTours.has(tour.id)" class="bg-elevated/30 border-t border-default">
                  <div
                    v-for="tr in (tour.translations_summary || [])"
                    :key="tr.translation_id"
                    class="flex items-center gap-3 pl-16 pr-5 py-2.5 border-b border-default last:border-b-0 hover:bg-elevated/50 transition-colors group"
                  >
                    <span class="text-base">{{ getLanguageFlag(tr.language_code) }}</span>

                    <UBadge
                      :color="tr.language_code === getPrimaryLanguageCode(tour) ? 'primary' : 'neutral'"
                      :variant="tr.language_code === getPrimaryLanguageCode(tour) ? 'solid' : 'subtle'"
                      size="xs"
                    >
                      {{ tr.language_code }}
                      <UIcon v-if="tr.language_code === getPrimaryLanguageCode(tour)" name="i-lucide-star" class="size-3 ml-0.5" />
                    </UBadge>

                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium truncate">{{ tr.title || '(Sin título)' }}</p>
                      <p class="text-[10px] text-muted font-mono truncate">
                        /{{ tr.language_code?.toLowerCase() }}/{{ tr.slug || '...' }}
                      </p>
                    </div>

                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                      <UButton
                        :to="getTranslationPreviewUrl(tour, tr) || undefined"
                        target="_blank"
                        rel="noopener noreferrer"
                        icon="i-lucide-eye"
                        color="primary"
                        variant="ghost"
                        size="xs"
                        :disabled="!getTranslationPreviewUrl(tour, tr)"
                        :title="getTranslationPreviewUrl(tour, tr)
                          ? `Ver en el sitio público (${tr.language_code})`
                          : 'Necesita slug + ciudad para previsualizar'"
                      />
                      <UButton
                        :to="`/admin/v2/tours/${tour.id}/edit?lang=${tr.language_code}`"
                        icon="i-lucide-edit"
                        color="neutral"
                        variant="ghost"
                        size="xs"
                        title="Editar esta traducción"
                      />
                      <UButton
                        v-if="(tour.translations_summary || []).length > 1"
                        icon="i-lucide-trash-2"
                        color="error"
                        variant="ghost"
                        size="xs"
                        title="Eliminar esta traducción"
                        @click="confirmDeleteTranslation(tour, tr)"
                      />
                    </div>
                  </div>

                  <button
                    class="w-full flex items-center gap-3 pl-16 pr-5 py-2.5 text-left hover:bg-success/5 transition-colors border-t border-dashed border-default"
                    @click="openCloneModal(tour)"
                  >
                    <UIcon name="i-lucide-circle-plus" class="size-4 text-success" />
                    <span class="text-xs font-semibold text-success">Agregar idioma...</span>
                  </button>
                </div>
              </Transition>
            </li>
          </ul>

          <!-- Pagination -->
          <div v-if="meta && meta.last_page > 1" class="p-4 border-t border-default flex items-center justify-between flex-wrap gap-3">
            <p class="text-xs text-muted">
              Mostrando <span class="font-semibold text-default">{{ meta.from }}-{{ meta.to }}</span>
              de <span class="font-semibold text-default">{{ meta.total }}</span> tours
            </p>
            <UPagination
              :page="meta.current_page"
              :total="meta.total"
              :items-per-page="meta.per_page"
              @update:page="changePage"
            />
          </div>
        </UCard>
      </div>
    </template>
  </UDashboardPanel>

  <!-- Clone Modal -->
  <UModal v-model:open="showCloneModal" :ui="{ content: 'max-w-xl' }">
    <template #content>
      <UCard :ui="{ body: 'p-6 space-y-6' }">
        <template #header>
          <div class="flex items-start justify-between gap-3">
            <div>
              <h3 class="text-lg font-bold">Agregar idioma</h3>
              <p class="text-xs text-muted mt-1">
                Tour: <span class="font-semibold text-default">{{ selectedTour?.title }}</span>
              </p>
            </div>
            <UButton icon="i-lucide-x" color="neutral" variant="ghost" size="sm" @click="closeCloneModal" />
          </div>
        </template>

        <!-- Language Picker -->
        <div>
          <p class="text-[10px] font-black uppercase tracking-widest text-muted mb-2">Idioma de destino</p>
          <div v-if="allLanguages.length === 0" class="text-center py-4 text-sm text-muted">
            Cargando idiomas disponibles...
          </div>
          <UAlert
            v-else-if="cloneAvailableLanguages.length === 0"
            icon="i-lucide-check-circle"
            color="success"
            variant="subtle"
            title="Tour completo"
            description="Este tour ya está traducido a todos los idiomas disponibles."
          />
          <div v-else class="grid grid-cols-3 sm:grid-cols-4 gap-2">
            <button
              v-for="lang in cloneAvailableLanguages"
              :key="lang.id"
              type="button"
              class="p-3 rounded-lg border-2 transition-all flex flex-col items-center gap-1"
              :class="selectedLanguage?.id === lang.id
                ? 'bg-primary text-white border-primary shadow-md'
                : 'bg-default text-default border-default hover:border-muted'"
              :title="lang.country"
              @click="selectedLanguage = lang"
            >
              <span class="text-lg leading-none">{{ getLanguageFlag(lang.code) }}</span>
              <span class="text-[10px] font-mono font-black">{{ lang.code }}</span>
            </button>
          </div>
        </div>

        <!-- Clone Type -->
        <div>
          <p class="text-[10px] font-black uppercase tracking-widest text-muted mb-2">Tipo de clonación</p>
          <div class="grid grid-cols-2 gap-3">
            <button
              type="button"
              class="p-3 rounded-lg border-2 transition-all text-left"
              :class="cloneType === 'manual' ? 'border-primary bg-primary/5' : 'border-default hover:border-muted'"
              @click="cloneType = 'manual'"
            >
              <div class="flex items-start gap-2">
                <div class="size-7 rounded-lg bg-info/10 flex items-center justify-center shrink-0">
                  <UIcon name="i-lucide-edit-3" class="size-4 text-info" />
                </div>
                <div>
                  <p class="font-bold text-sm">Manual</p>
                  <p class="text-[10px] text-muted leading-relaxed mt-0.5">Copia los datos y permite traducir manualmente</p>
                </div>
              </div>
            </button>
            <button
              type="button"
              class="p-3 rounded-lg border-2 transition-all text-left relative"
              :class="cloneType === 'ai' ? 'border-primary bg-primary/5' : 'border-default hover:border-muted'"
              @click="cloneType = 'ai'"
            >
              <UBadge color="secondary" variant="solid" size="xs" class="absolute top-2 right-2">IA</UBadge>
              <div class="flex items-start gap-2">
                <div class="size-7 rounded-lg bg-secondary/10 flex items-center justify-center shrink-0">
                  <UIcon name="i-lucide-sparkles" class="size-4 text-secondary" />
                </div>
                <div>
                  <p class="font-bold text-sm">Con IA</p>
                  <p class="text-[10px] text-muted leading-relaxed mt-0.5">Traducción automática con inteligencia artificial</p>
                </div>
              </div>
            </button>
          </div>
        </div>

        <template #footer>
          <div class="flex justify-end gap-2">
            <UButton color="neutral" variant="ghost" @click="closeCloneModal">Cancelar</UButton>
            <UButton
              color="primary"
              :icon="cloneType === 'ai' ? 'i-lucide-sparkles' : 'i-lucide-languages'"
              :loading="cloning"
              :disabled="!selectedLanguage || cloneAvailableLanguages.length === 0"
              @click="performClone"
            >
              {{ cloning ? 'Agregando...' : 'Agregar traducción' }}
            </UButton>
          </div>
        </template>
      </UCard>
    </template>
  </UModal>
</template>
