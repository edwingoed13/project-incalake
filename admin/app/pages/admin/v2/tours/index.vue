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
// clone / delete-translation are admin-gated server-side now.
const authHeaders = () => ({ Authorization: `Bearer ${localStorage.getItem('auth_token') || ''}` })

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

// Which languages share their title with another tour, and with which one. The
// API works this out across the whole catalogue, which a single page of ten
// rows cannot.
//
// The first attempt read it off the slug: TourService appends -1, -2 … when a
// generated slug is taken, so a suffix looked like a reliable fingerprint of a
// copy. It was not. A suffix only records that a collision happened once, maybe
// with a tour since renamed or deleted, and it flagged five of the first ten
// tours — while the catalogue actually holds 13 duplicated titles. A badge that
// fires on half the list teaches people to ignore it.
const duplicateTitles = (tour: Tour): Record<string, string[]> =>
  ((tour as any).duplicate_title_languages || {}) as Record<string, string[]>

const clonedLanguages = (tour: Tour): string[] => Object.keys(duplicateTitles(tour))

/** The tours a given language collides with, e.g. ["ES007"]. */
const duplicateOf = (tour: Tour, code?: string | null): string[] =>
  (code && duplicateTitles(tour)[code.toUpperCase()]) || []

/**
 * Jump to the tour a duplicated title collides with. The badge only carries
 * that tour's CODE — the API compares titles across the whole catalogue, so it
 * is usually not on the page in front of you. Searching by code is what makes
 * the warning actionable instead of a dead end; the other tour carries the
 * mirror-image warning, so you can bounce back and decide which one to fix.
 */
const findTourByCode = (code?: string) => {
  if (!code) return
  searchQuery.value = code
  fetchTours(1, code)
}

/**
 * Languages the parked draft changes, as uppercase codes.
 *
 * null from the API means UNKNOWN, not "none": drafts parked before the wizard
 * started recording this carry no summary, and an empty list would tell the
 * operator no language is waiting — a confident lie about someone's work. Both
 * callers below treat the empty result as "say nothing".
 */
const draftLanguages = (tour: Tour): string[] =>
  ((tour as any).pending_draft_languages || []) as string[]

const draftSections = (tour: Tour): string[] =>
  ((tour as any).pending_draft_sections || []) as string[]

/** Does this specific translation row have edits waiting to be published? */
const hasDraftChanges = (tour: Tour, code?: string | null): boolean =>
  !!code && draftLanguages(tour).includes(code.toUpperCase())

/**
 * The tour's state, including the one case that has to shout.
 *
 * Takes the whole tour, not just `status`: a PUBLISHED tour with zero
 * translations has no title, no slug and no content, so the public listing
 * shows a card bearing its CODE that links nowhere. Twenty-three tours were in
 * that state, and every one of them wore the same reassuring green as a
 * healthy tour — the listing was actively hiding the problem. A draft with no
 * translations is just a tour being built, so it stays neutral.
 */
const statusBadge = (tour: Tour) => {
  const s = tour.status
  const sinContenido = !(tour.translations_summary || []).length
  if (s === 'published' && sinContenido) {
    return { label: 'Publicado · sin contenido', color: 'error' as const, icon: 'i-lucide-triangle-alert' }
  }
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

/**
 * The language this tour is authored in — the one whose title the row shows.
 *
 * This used to fall back to the first two letters of the tour CODE, which is a
 * business code and not a language: "LAKE006" produced "LA" and "BR088" (a
 * Portuguese tour) produced "BR", so the star landed on nothing and the row
 * claimed a language that does not exist. Falling back through the tour's own
 * translations keeps the answer inside real data.
 */
const getPrimaryLanguageCode = (tour: Tour) => {
  if (tour.primary_language?.code) return tour.primary_language.code
  const codes = (tour.translations_summary || [])
    .map(t => t.language_code)
    .filter(Boolean) as string[]
  if (codes.length === 1) return codes[0]!
  return codes.includes('ES') ? 'ES' : (codes[0] || 'ES')
}

const getTourReferenceName = (tour: Tour) => {
  const primaryCode = getPrimaryLanguageCode(tour)
  const primaryTr = (tour.translations_summary || []).find(t => t.language_code === primaryCode)
  return primaryTr?.title || tour.title || tour.code
}

/**
 * city.name is free text from the wizard, so some tours carry a whole
 * location ("Isla de los Uros, Puno, Perú") next to siblings that just say
 * "Puno". Keep the first segment so the column scans as one column.
 */
const cityLabel = (tour: Tour) => String((tour as any).city?.name || '').split(',')[0]!.trim()

const formatPrice = (value: number | string | null) => {
  const n = Number(value)
  if (!Number.isFinite(n) || n <= 0) return ''
  return `$${n % 1 === 0 ? n : n.toFixed(2)}`
}

/** "hace 3 días" reads faster than a timestamp when scanning a long list. */
const timeAgo = (iso?: string | null) => {
  if (!iso) return '—'
  const diff = Date.now() - new Date(iso).getTime()
  if (!Number.isFinite(diff) || diff < 0) return '—'
  const mins = Math.round(diff / 60000)
  if (mins < 60) return mins <= 1 ? 'recién' : `hace ${mins} min`
  const hours = Math.round(mins / 60)
  if (hours < 24) return hours === 1 ? 'hace 1 h' : `hace ${hours} h`
  const days = Math.round(hours / 24)
  if (days < 30) return days === 1 ? 'ayer' : `hace ${days} días`
  const months = Math.round(days / 30)
  return months === 1 ? 'hace 1 mes' : `hace ${months} meses`
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

const loadError = ref(false)

// Preferencias del listado, recordadas por navegador. per_page fijo en 10
// significaba 29 páginas para recorrer los 290 tours de producción.
const perPageOptions = [
  { label: '10 / pág.', value: 10 },
  { label: '25 / pág.', value: 25 },
  { label: '50 / pág.', value: 50 },
]
const sortOptions = [
  { label: 'Recientes', value: 'created_at:desc' },
  { label: 'Últ. modificación', value: 'updated_at:desc' },
  { label: 'Código A-Z', value: 'code:asc' },
  { label: 'Estado', value: 'status:asc' },
  { label: 'Capacidad', value: 'capacity:desc' },
]
const perPage = ref<number>(Number(localStorage.getItem('tours:perPage')) || 10)
const sortKey = ref<string>(localStorage.getItem('tours:sort') || 'created_at:desc')

// --- City / language filters ------------------------------------------------
// 'all' as the sentinel, not '': Nuxt UI's Select rejects an empty-string
// option value (it reserves it for "cleared / show placeholder").
const cityFilter = ref<string>('all')
const languageFilter = ref<string>('all')
// Editorial triage. The list could always SHOW that a tour was empty, had
// parked edits, or carried a copied title — but never filter by it, so finding
// them meant paging 29 pages. That is how 23 tours stayed published with no
// content at all.
const attentionFilter = ref<string>('all')
const attentionOptions = [
  { label: 'Todo el catálogo', value: 'all' },
  { label: 'Necesita atención', value: 'any' },
  { label: '· Publicado sin contenido', value: 'no_content' },
  { label: '· Con cambios sin publicar', value: 'pending_draft' },
  { label: '· Título copiado de otro tour', value: 'duplicate_titles' },
]


/** One click filters to the work waiting; another click clears it. */
const toggleAttention = () => {
  attentionFilter.value = attentionFilter.value === 'all' ? 'any' : 'all'
  onFilterChange()
}

const cities = ref<Array<{ id: number; name: string; slug: string }>>([])

const cityOptions = computed(() => [
  { label: 'Todas las ciudades', value: 'all' },
  ...cities.value.map(c => ({ label: c.name, value: c.slug })),
])

const languageOptions = computed(() => [
  { label: 'Todos los idiomas', value: 'all' },
  ...allLanguages.value.map(l => ({ label: `Con ${l.code.toUpperCase()}`, value: l.code.toUpperCase() })),
])

const onFilterChange = () => {
  currentPage.value = 1
  selectedIds.value = []
  fetchTours(1, searchQuery.value)
}

// --- Bulk selection ---------------------------------------------------------
// Publishing or archiving 30 tours meant 30 round trips through the editor.
const selectedIds = ref<number[]>([])
const bulkWorking = ref(false)

const isSelected = (id: number) => selectedIds.value.includes(id)
const toggleSelect = (id: number) => {
  const i = selectedIds.value.indexOf(id)
  if (i >= 0) selectedIds.value.splice(i, 1)
  else selectedIds.value.push(id)
}
const allOnPageSelected = computed(() =>
  tours.value.length > 0 && tours.value.every(t => selectedIds.value.includes(t.id))
)
const toggleSelectAll = () => {
  selectedIds.value = allOnPageSelected.value ? [] : tours.value.map(t => t.id)
}

/**
 * Reuses the per-tour status endpoint instead of adding a bulk one: that
 * endpoint already purges the public cache on every change, and duplicating
 * that logic is how a tour ends up taken down but still served.
 */
const bulkSetStatus = async (status: 'published' | 'draft' | 'archived') => {
  if (!selectedIds.value.length || bulkWorking.value) return
  const labels: Record<string, string> = { published: 'Publicar', draft: 'Pasar a borrador', archived: 'Archivar' }
  const ids = [...selectedIds.value]
  const ok = await confirm({
    title: `${labels[status]} ${ids.length} tour(s)`,
    description: status === 'published'
      ? 'Se publicarán y quedarán visibles en la web.'
      : 'Dejarán de mostrarse en la web pública.',
    confirmLabel: labels[status],
  })
  if (!ok) return

  bulkWorking.value = true
  let done = 0
  let failed = 0
  for (const id of ids) {
    try {
      await $fetch(`${API_BASE_URL}/admin/tours/${id}/status`, {
        method: 'POST',
        headers: authHeaders(),
        body: { status },
      })
      done++
    } catch { failed++ }
  }
  bulkWorking.value = false
  selectedIds.value = []
  toast.add({
    title: failed ? `${done} actualizados, ${failed} con error` : `${done} tour(s) actualizados`,
    color: failed ? 'warning' : 'success',
    icon: failed ? 'i-lucide-triangle-alert' : 'i-lucide-check',
  })
  fetchTours(currentPage.value, searchQuery.value)
}

const fetchCities = async () => {
  try {
    const res: any = await $fetch(`${API_BASE_URL}/cities`, { headers: authHeaders() })
    cities.value = (res?.data || []).filter((c: any) => c?.slug)
  } catch { /* el filtro simplemente queda con "Todas" */ }
}

const onListPrefsChange = () => {
  try {
    localStorage.setItem('tours:perPage', String(perPage.value))
    localStorage.setItem('tours:sort', sortKey.value)
  } catch { /* almacenamiento lleno o bloqueado — no es fatal */ }
  currentPage.value = 1
  fetchTours(1, searchQuery.value)
}

const fetchTours = async (page = 1, search = '') => {
  loading.value = true
  loadError.value = false
  try {
    const [sortBy, sortOrder] = sortKey.value.split(':')
    const params = new URLSearchParams({
      page: String(page),
      per_page: String(perPage.value),
      search,
      sort_by: sortBy || 'created_at',
      sort_order: sortOrder || 'desc',
      // Costs a query per row, so only this listing asks for it — the public
      // one fetches a thousand tours at a time.
      with_duplicates: '1',
    })
    if (statusFilter.value !== 'all') params.set('status', statusFilter.value)
    if (cityFilter.value !== 'all') params.set('city_slug', cityFilter.value)
    if (languageFilter.value !== 'all') params.set('language', languageFilter.value)
    if (attentionFilter.value !== 'all') params.set('attention', attentionFilter.value)
    // Authenticated: /tours only returns published tours to anonymous callers
    // now, so without the token the admin list would show no drafts at all.
    const response: any = await $fetch(`${API_BASE_URL}/tours?${params}`, { headers: authHeaders() })
    if (response?.success) {
      tours.value = response.data
      meta.value = response.meta
      if (response.status_counts) statusCounts.value = response.status_counts
    }
  } catch (err) {
    console.error('Error fetching tours:', err)
    loadError.value = true
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

  // Single deliberate confirmation: lists the full impact and requires the
  // operator to type the tour CODE to enable the delete button. Replaces the
  // old two-dialog flow (less annoying, equally safe — you can't fat-finger
  // your way through typing the exact code).
  const ok = await confirm({
    title: `Eliminar tour ${tour.code}`,
    description: `Se eliminará "${tour.title}" y todo lo asociado: contenido en ${langCount} idioma${langCount === 1 ? '' : 's'} (${langList}), imágenes, precios, disponibilidad, bloqueos, ofertas y reseñas. Esta acción no se puede deshacer.`,
    requireText: tour.code,
    requireTextLabel: 'Escribe el código',
    confirmLabel: 'Eliminar definitivamente',
    cancelLabel: 'Cancelar',
    confirmColor: 'error',
    confirmIcon: 'i-lucide-trash-2',
    icon: 'i-lucide-triangle-alert',
    iconColor: 'error',
  })
  if (!ok) return

  try {
    const response: any = await apiWrite(`${API_BASE_URL}/tours/${tour.id}`, 'DELETE')
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
    const response: any = await apiWrite(`${API_BASE_URL}/tours/${tour.id}/translation/${tr.language_id}`, 'DELETE')
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
      headers: authHeaders(),
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
  // Both feed the filter dropdowns; neither blocks the list.
  fetchCities()
  fetchLanguages()
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
            <h1 class="admin-h1">Gestión de tours</h1>
            <p class="text-sm text-muted mt-1">
              <span v-if="meta">{{ meta.total }} tours · {{ meta.from }}-{{ meta.to }} mostrados</span>
              <span v-else>Cargando...</span>
            </p>
          </div>
          <div class="flex items-center gap-2 flex-wrap w-full sm:w-auto">
            <!-- Con 290 tours, las 4 pestañas de estado no alcanzan: "los de
                 Cusco" o "los que no están en inglés" son las preguntas
                 reales. El API ya soportaba ambos filtros. -->
            <USelect
              v-model="cityFilter"
              :items="cityOptions"
              size="lg"
              class="w-44"
              icon="i-lucide-map-pin"
              @update:model-value="onFilterChange"
            />
            <USelect
              v-model="languageFilter"
              :items="languageOptions"
              size="lg"
              class="w-40"
              icon="i-lucide-languages"
              @update:model-value="onFilterChange"
            />
            <!-- The question the list could not answer: "what needs work?".
                 Coloured when active, because leaving it set silently filters
                 the catalogue and the next person wonders where the tours went. -->
            <USelect
              v-model="attentionFilter"
              :items="attentionOptions"
              size="lg"
              class="w-56"
              icon="i-lucide-triangle-alert"
              :color="attentionFilter !== 'all' ? 'warning' : undefined"
              @update:model-value="onFilterChange"
            />
            <!-- 290 tours de 10 en 10 eran 29 páginas de clics: orden y tamaño
                 de página, recordados por navegador. -->
            <USelect
              v-model="sortKey"
              :items="sortOptions"
              size="lg"
              class="w-44"
              icon="i-lucide-arrow-up-down"
              @update:model-value="onListPrefsChange"
            />
            <USelect
              v-model="perPage"
              :items="perPageOptions"
              size="lg"
              class="w-36"
              icon="i-lucide-rows-3"
              @update:model-value="onListPrefsChange"
            />
          <UInput
            v-model="searchQuery"
            placeholder="Buscar por título o código..."
            icon="i-lucide-search"
            size="lg"
            class="w-full sm:w-80"
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

          <!-- Not a status: a shortcut to the work waiting. It sits with the
               tabs because it answers the same kind of question ("show me a
               slice of the catalogue"), and apart from them — separated, in
               amber — because it filters ACROSS statuses instead of by one.
               Hidden at zero: a permanent "0 · Necesita atención" trains
               people to stop reading it. -->
          <button
            v-if="(statusCounts.attention ?? 0) > 0"
            type="button"
            class="ml-auto px-3 py-2 text-xs font-bold flex items-center gap-1.5 border-b-2 transition-colors whitespace-nowrap"
            :class="attentionFilter !== 'all'
              ? 'border-warning text-warning'
              : 'border-transparent text-muted hover:text-warning'"
            :title="`${statusCounts.attention} tours con contenido vacío, cambios sin publicar o títulos copiados`"
            @click="toggleAttention()"
          >
            <UIcon name="i-lucide-triangle-alert" class="size-3.5" />
            Necesita atención
            <span
              class="text-[10px] font-bold rounded-full px-1.5 min-w-[18px] text-center"
              :class="attentionFilter !== 'all' ? 'bg-warning/15 text-warning' : 'bg-elevated text-muted'"
            >
              {{ statusCounts.attention }}
            </span>
          </button>
        </div>

        <!-- Bulk actions: appears only with a selection, so the list stays
             calm when nobody is doing batch work. -->
        <div
          v-if="selectedIds.length"
          class="flex items-center justify-between gap-3 flex-wrap rounded-xl border border-primary/30 bg-primary/5 px-3 py-2"
        >
          <span class="text-xs font-bold text-primary">
            {{ selectedIds.length }} seleccionado{{ selectedIds.length === 1 ? '' : 's' }}
          </span>
          <div class="flex items-center gap-2 flex-wrap">
            <UButton size="xs" color="success" variant="soft" icon="i-lucide-globe" :loading="bulkWorking" @click="bulkSetStatus('published')">
              Publicar
            </UButton>
            <UButton size="xs" color="neutral" variant="soft" icon="i-lucide-file-text" :loading="bulkWorking" @click="bulkSetStatus('draft')">
              Pasar a borrador
            </UButton>
            <UButton size="xs" color="warning" variant="soft" icon="i-lucide-archive" :loading="bulkWorking" @click="bulkSetStatus('archived')">
              Archivar
            </UButton>
            <UButton size="xs" color="neutral" variant="ghost" @click="selectedIds = []">
              Cancelar
            </UButton>
          </div>
        </div>

        <!-- Select-all for the current page -->
        <label
          v-if="tours.length"
          class="flex items-center gap-2 px-1 text-xs font-semibold text-muted cursor-pointer select-none"
        >
          <UCheckbox :model-value="allOnPageSelected" @update:model-value="toggleSelectAll" />
          Seleccionar los {{ tours.length }} de esta página
        </label>

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

          <!-- Error ≠ vacío -->
          <div v-else-if="loadError" class="p-12 flex flex-col items-center text-center gap-3">
            <UIcon name="i-lucide-wifi-off" class="size-12 text-error" />
            <p class="text-sm text-highlighted font-semibold">No se pudieron cargar los tours</p>
            <UButton variant="outline" size="sm" icon="i-lucide-refresh-cw" @click="fetchTours(currentPage, searchQuery)">
              Reintentar
            </UButton>
          </div>

          <!-- Empty state -->
          <div v-else-if="tours.length === 0" class="p-12 flex flex-col items-center text-center gap-3">
            <UIcon name="i-lucide-search-x" class="size-12 text-muted" />
            <p class="text-sm text-muted">No se encontraron tours con los criterios de búsqueda.</p>
            <UButton v-if="searchQuery" variant="outline" size="sm" @click="searchQuery = ''; debounceSearch()">
              Limpiar búsqueda
            </UButton>
          </div>

          <ul v-else class="divide-y divide-default">
            <li v-for="tour in tours" :key="tour.id">
              <!-- Tour main row. Two groups so phones get TWO lines (title at
                   full width, chips beneath) instead of the fixed right-side
                   controls crushing the flexible column until the title
                   truncated to nothing and rows became indistinguishable. -->
              <div
                class="px-4 sm:px-5 py-3 flex flex-wrap sm:flex-nowrap items-center gap-x-3 gap-y-2 hover:bg-elevated/50 transition-colors cursor-pointer"
                @click="toggleExpand(tour.id)"
              >
                <div class="flex items-center gap-3 w-full sm:w-auto sm:flex-1 min-w-0">
                  <!-- @click.stop so ticking a row doesn't also expand it -->
                  <UCheckbox
                    :model-value="isSelected(tour.id)"
                    class="shrink-0"
                    @click.stop
                    @update:model-value="toggleSelect(tour.id)"
                  />
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
                    <div class="flex items-center gap-2 min-w-0">
                      <p class="text-sm font-bold truncate">{{ getTourReferenceName(tour) }}</p>
                      <UBadge color="neutral" variant="subtle" size="xs" class="font-mono shrink-0">{{ tour.code }}</UBadge>
                      <!-- Shell tours (zero translations) used to be invisible
                           here; now that they list, say what's wrong with them
                           instead of showing a row with a code and nothing else. -->
                      <UBadge
                        v-if="!(tour.translations_summary || []).length"
                        color="warning"
                        variant="subtle"
                        size="xs"
                        icon="i-lucide-triangle-alert"
                        class="shrink-0"
                      >
                        Sin contenido
                      </UBadge>
                      <!-- "Publiqué y no se ve" suele ser esto: el borrador
                           quedó sin publicar. Visible sin abrir el tour.
                           It used to read "Sin publicar", which sat next to a
                           green "Publicado" and looked like a flat
                           contradiction: one is the tour's status, the other is
                           whether its latest EDITS have been published. Naming
                           the changes is what separates them. -->
                      <UBadge
                        v-if="(tour as any).has_pending_draft"
                        color="info"
                        variant="subtle"
                        size="xs"
                        icon="i-lucide-file-clock"
                        class="shrink-0"
                        :title="`Hay ediciones guardadas${(tour as any).pending_draft_at ? ' desde ' + timeAgo((tour as any).pending_draft_at) : ''} que el público todavía no ve. Ábrelo y pulsa «Actualizar» para aplicarlas.`
                          + (draftLanguages(tour).length || draftSections(tour).length
                            ? ` Afecta a: ${[...draftLanguages(tour), ...draftSections(tour)].join(', ')}.`
                            : ' Este borrador se guardó antes de que se registrara el detalle, así que no se sabe qué idioma cambió; ábrelo para verlo.')"
                      >
                        Cambios sin publicar<!--
                        Naming the languages is the whole point: with six of
                        them, "algo cambió" left the operator opening each one.
                        Silent when the API sends null (an older draft), which
                        means unknown — not "nothing changed".
                        --><span v-if="draftLanguages(tour).length"> en {{ draftLanguages(tour).join(', ') }}</span><span v-if="(tour as any).pending_draft_at"> · {{ timeAgo((tour as any).pending_draft_at) }}</span>
                      </UBadge>
                      <!-- A language whose title is byte-identical to another
                           tour's is a translation that was never rewritten
                           after a copy — which is how a Uyuni tour ended up
                           selling the Uros tour at the Uyuni price in five
                           languages. The summary names the languages; the
                           expanded rows name the tour each collides with.
                           Neither claims which side is the copy: both tours in
                           a collision get flagged, and identical titles carry
                           no direction. -->
                      <UBadge
                        v-if="clonedLanguages(tour).length"
                        color="warning"
                        variant="subtle"
                        size="xs"
                        icon="i-lucide-copy"
                        class="shrink-0"
                        :title="`Estas traducciones tienen exactamente el mismo título que otro tour: ${clonedLanguages(tour).map(l => l + ' = tour ' + duplicateOf(tour, l).join(' y ')).join('; ')}. Casi siempre es una copia que quedó sin reescribir. Despliega el tour para verlas.`"
                      >
                        {{ clonedLanguages(tour).join(', ') }} sin reescribir
                      </UBadge>
                    </div>
                    <!-- The row had ~500px of dead space on a wide screen while
                         the data an operator needs to triage 290 tours (where,
                         how much, when was it last touched) lived only inside
                         the editor. -->
                    <p class="text-[11px] text-muted mt-0.5 truncate">
                      <span v-if="cityLabel(tour)" class="font-semibold text-default">{{ cityLabel(tour) }}</span>
                      <span v-if="cityLabel(tour)"> · </span>
                      <span v-if="tour.min_price">{{ formatPrice(tour.min_price) }}</span>
                      <span v-if="tour.min_price"> · </span>
                      <span>{{ (tour.translations_summary || []).length }} {{ (tour.translations_summary || []).length === 1 ? 'idioma' : 'idiomas' }}</span>
                      <span class="hidden sm:inline"> · editado {{ timeAgo(tour.updated_at) }}</span>
                    </p>
                  </div>
                </div>

                <!-- pl aligns the second line with the title above (40px thumb + 12px gap) -->
                <div class="flex items-center gap-2 flex-wrap pl-[52px] sm:pl-0 sm:shrink-0" @click.stop="toggleExpand(tour.id)">
                <UBadge
                  :color="statusBadge(tour).color"
                  variant="subtle"
                  size="sm"
                  :icon="statusBadge(tour).icon"
                >
                  {{ statusBadge(tour).label }}
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

                <!-- md+: individual language code badges. Below md they were
                     hidden entirely (info loss); now a compact count badge
                     keeps the "how many languages" signal on phones. -->
                <UBadge
                  v-if="(tour.available_languages || []).length"
                  class="md:hidden"
                  color="primary"
                  variant="subtle"
                  size="xs"
                  icon="i-lucide-languages"
                  :title="(tour.available_languages || []).map(l => l.code).join(', ')"
                >
                  {{ (tour.available_languages || []).length }}
                </UBadge>
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

                <!-- A real button, and one that says what it opens.
                     This was a bare <UIcon> inside a div with @click: it could
                     not be reached or activated by keyboard, announced nothing
                     to a screen reader, and gave a sighted operator no way to
                     tell it apart from the ⋮ beside it other than by guessing
                     what a chevron means here. The count doubles as the answer
                     to "how many languages does this tour have". -->
                <UButton
                  color="neutral"
                  variant="ghost"
                  size="xs"
                  :aria-expanded="expandedTours.has(tour.id)"
                  :aria-controls="`traducciones-${tour.id}`"
                  :title="expandedTours.has(tour.id) ? 'Ocultar los idiomas' : 'Ver los idiomas de este tour'"
                  trailing-icon="i-lucide-chevron-down"
                  :ui="{ trailingIcon: expandedTours.has(tour.id) ? 'rotate-180 transition-transform' : 'transition-transform' }"
                  @click.stop="toggleExpand(tour.id)"
                >
                  <span class="hidden sm:inline">{{ (tour.translations_summary || []).length }} {{ (tour.translations_summary || []).length === 1 ? 'idioma' : 'idiomas' }}</span>
                </UButton>
                </div>
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
                <div v-if="expandedTours.has(tour.id)" :id="`traducciones-${tour.id}`" class="bg-elevated/30 border-t border-default">
                  <!-- These rows used to open with no explanation at all, so
                       they read as separate tours nested inside a tour — the
                       single most common misreading of this screen. Say what
                       they are, and where the big title above comes from, at
                       the moment someone actually opens them. -->
                  <div class="pl-16 pr-5 py-2 border-b border-default/60">
                    <p class="text-[11px] text-muted">
                      <span class="font-semibold uppercase tracking-wide text-default">Traducciones ({{ (tour.translations_summary || []).length }})</span>
                      · el mismo tour en cada idioma, no son tours aparte. Arriba se muestra el título del idioma principal ({{ getPrimaryLanguageCode(tour) }}<UIcon name="i-lucide-star" class="size-3 inline-block align-text-bottom" />).
                    </p>
                  </div>

                  <!-- The left rail makes the parent/child relationship
                       structural instead of something you infer from
                       indentation. Stacked rows form one continuous line. -->
                  <div
                    v-for="tr in (tour.translations_summary || [])"
                    :key="tr.translation_id"
                    class="flex items-center gap-3 ml-[38px] border-l-2 pl-6 pr-5 py-2.5 border-b border-default last:border-b-0 hover:bg-elevated/50 transition-colors group"
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
                      <div class="flex items-center gap-2 min-w-0">
                        <p class="text-sm font-medium truncate">{{ tr.title || '(Sin título)' }}</p>
                        <!-- The row the operator was looking for: which of the
                             six languages is actually waiting to be published.
                             Absent on older drafts, where the answer is
                             unknown rather than "no". -->
                        <UBadge
                          v-if="hasDraftChanges(tour, tr.language_code)"
                          color="info"
                          variant="subtle"
                          size="xs"
                          icon="i-lucide-file-clock"
                          class="shrink-0"
                          :title="`Este idioma tiene cambios guardados que el público todavía no ve. Ábrelo y pulsa «Actualizar» para publicarlos.`"
                        >
                          Sin publicar
                        </UBadge>
                        <!-- On the row, not only summarised above it: this is
                             where the operator can see WHICH title is repeated,
                             so it is where the warning belongs. -->
                        <UBadge
                          v-if="duplicateOf(tour, tr.language_code).length"
                          color="warning"
                          variant="subtle"
                          size="xs"
                          icon="i-lucide-copy"
                          as="button"
                          type="button"
                          class="shrink-0 cursor-pointer hover:bg-warning/20 transition-colors"
                          :title="`Este título es idéntico, letra por letra, al del tour ${duplicateOf(tour, tr.language_code).join(' y ')}. Casi siempre significa que uno de los dos se copió del otro y esta traducción quedó sin reescribir. Haz clic para buscar ese tour y decidir cuál corregir.`"
                          @click.stop="findTourByCode(duplicateOf(tour, tr.language_code)[0])"
                        >
                          Mismo título que {{ duplicateOf(tour, tr.language_code).join(', ') }}
                        </UBadge>
                      </div>
                      <p class="text-[10px] text-muted font-mono truncate">
                        /{{ tr.language_code?.toLowerCase() }}/{{ tr.slug || '...' }}
                      </p>
                    </div>

                    <!-- Always visible on touch (no hover); hover-reveal only on
                         real mouse devices via can-hover. The old lg: gate broke
                         on landscape tablets (≥1024px but no hover). -->
                    <div class="flex items-center gap-1 opacity-100 can-hover:opacity-0 can-hover:group-hover:opacity-100 can-hover:focus-within:opacity-100 transition-opacity">
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
  <V2FormModalShell
    :open="showCloneModal"
    title="Agregar idioma"
    icon="i-lucide-languages"
    width="max-w-xl"
    :busy="cloning"
    @close="closeCloneModal"
  >
    <template #subtitle>
      Tour: <span class="font-semibold text-default">{{ selectedTour?.title }}</span>
    </template>
    <div class="p-6 space-y-6">

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

      <div class="flex justify-end gap-2 pt-2 border-t border-default">
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
    </div>
  </V2FormModalShell>
</template>
