<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import ReviewFormModalV2 from '~/components/v2/ReviewFormModalV2.vue'
import ReviewDetailModalV2 from '~/components/v2/ReviewDetailModalV2.vue'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: 'admin-v2',
  middleware: 'auth',
})

interface Review {
  id: number
  name: string
  review_date?: string
  rating: number
  title?: string
  comment: string
  opinion?: string
  language: string
  published: boolean
  featured: boolean
  tour_id?: number | null
  tour?: { id: number; code: string; title: string }
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
const auth = useAuthStore()
const toast = useToast()
const { confirm } = useConfirm()

const reviews = ref<Review[]>([])
const meta = ref<Meta | null>(null)
const stats = ref<any>(null)
const loading = ref(true)
const searchQuery = ref('')
const filterRating = ref('')
const filterPublished = ref('')
const filterFeatured = ref('')
const currentPage = ref(1)

const showFormModal = ref(false)
const editingReview = ref<Review | null>(null)
const showDetailModal = ref(false)
const detailReview = ref<Review | null>(null)

const ratingOptions = [
  { label: 'Todas las calificaciones', value: '' },
  { label: '⭐⭐⭐⭐⭐ 5 estrellas', value: '5' },
  { label: '⭐⭐⭐⭐ 4 estrellas', value: '4' },
  { label: '⭐⭐⭐ 3 estrellas', value: '3' },
  { label: '⭐⭐ 2 estrellas', value: '2' },
  { label: '⭐ 1 estrella', value: '1' },
]

const publishedOptions = [
  { label: 'Todos los estados', value: '' },
  { label: 'Publicadas', value: '1' },
  { label: 'Ocultas', value: '0' },
]

const featuredOptions = [
  { label: 'Todas', value: '' },
  { label: 'Solo destacadas', value: '1' },
]

const headers = () => ({
  Authorization: `Bearer ${auth.token || localStorage.getItem('auth_token') || ''}`,
  Accept: 'application/json',
})

const languageBadge: Record<string, { color: 'info' | 'neutral'; label: string }> = {
  es: { color: 'info', label: 'ES' },
  en: { color: 'info', label: 'EN' },
  fr: { color: 'info', label: 'FR' },
  de: { color: 'info', label: 'DE' },
  pt: { color: 'info', label: 'PT' },
  it: { color: 'info', label: 'IT' },
}

const statsCards = computed(() => [
  {
    label: 'Total reseñas',
    value: stats.value?.total || 0,
    icon: 'i-lucide-star',
    bgClass: 'bg-info/10',
    iconClass: 'text-info',
  },
  {
    label: 'Publicadas',
    value: stats.value?.published || 0,
    icon: 'i-lucide-eye',
    bgClass: 'bg-success/10',
    iconClass: 'text-success',
  },
  {
    label: 'Destacadas',
    value: stats.value?.featured || 0,
    icon: 'i-lucide-sparkles',
    bgClass: 'bg-primary/10',
    iconClass: 'text-primary',
  },
  {
    label: 'Promedio',
    value: stats.value?.avg_rating || '0.0',
    icon: 'i-lucide-trending-up',
    bgClass: 'bg-warning/10',
    iconClass: 'text-warning',
  },
  {
    label: 'Sin tour',
    value: stats.value?.unassigned || 0,
    icon: 'i-lucide-link-2-off',
    bgClass: 'bg-error/10',
    iconClass: 'text-error',
  },
])

const visiblePages = computed(() => {
  if (!meta.value) return []
  const c = meta.value.current_page
  const l = meta.value.last_page
  const pages: number[] = []
  for (let i = Math.max(1, c - 2); i <= Math.min(l, c + 2); i++) pages.push(i)
  return pages
})

const fetchReviews = async (page = 1) => {
  loading.value = true
  try {
    const params = new URLSearchParams({ page: String(page), per_page: '10' })
    if (searchQuery.value) params.set('search', searchQuery.value)
    if (filterRating.value) params.set('rating', filterRating.value)
    if (filterPublished.value !== '') params.set('published', filterPublished.value)
    if (filterFeatured.value) params.set('featured', filterFeatured.value)

    const res: any = await $fetch(`${config.public.apiUrl}/admin/reviews?${params}`, { headers: headers() })
    if (res.success) {
      reviews.value = res.data
      meta.value = res.meta
      currentPage.value = page
    }
  } catch {
    toast.add({ title: 'Error', description: 'No se pudieron cargar las reseñas.', color: 'error', icon: 'i-lucide-alert-triangle' })
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  try {
    const res: any = await $fetch(`${config.public.apiUrl}/admin/reviews/stats`, { headers: headers() })
    if (res.success) stats.value = res.data
  } catch {
    // optional
  }
}

let searchTimeout: any
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => fetchReviews(1), 300)
}

const changePage = (page: number) => {
  if (!meta.value || page < 1 || page > meta.value.last_page) return
  fetchReviews(page)
}

const resetFilters = () => {
  searchQuery.value = ''
  filterRating.value = ''
  filterPublished.value = ''
  filterFeatured.value = ''
  fetchReviews(1)
}

const togglePublished = async (review: Review) => {
  try {
    await $fetch(`${config.public.apiUrl}/admin/reviews/${review.id}`, {
      method: 'PUT',
      headers: headers(),
      body: { published: !review.published },
    })
    review.published = !review.published
    toast.add({
      title: review.published ? 'Reseña publicada' : 'Reseña oculta',
      icon: review.published ? 'i-lucide-eye' : 'i-lucide-eye-off',
      color: 'success',
    })
    fetchStats()
  } catch {
    toast.add({ title: 'Error', color: 'error', icon: 'i-lucide-alert-triangle' })
  }
}

const toggleFeatured = async (review: Review) => {
  try {
    await $fetch(`${config.public.apiUrl}/admin/reviews/${review.id}`, {
      method: 'PUT',
      headers: headers(),
      body: { featured: !review.featured },
    })
    review.featured = !review.featured
    toast.add({
      title: review.featured ? 'Marcada como destacada' : 'Quitada de destacadas',
      icon: 'i-lucide-sparkles',
      color: 'success',
    })
    fetchStats()
  } catch {
    toast.add({ title: 'Error', color: 'error', icon: 'i-lucide-alert-triangle' })
  }
}

const openCreate = () => {
  editingReview.value = null
  showFormModal.value = true
}

const openDetail = (review: Review) => {
  detailReview.value = review
  showDetailModal.value = true
}

const openEdit = (review: Review) => {
  editingReview.value = review
  showFormModal.value = true
}

const editFromDetail = () => {
  if (!detailReview.value) return
  const r = detailReview.value
  showDetailModal.value = false
  detailReview.value = null
  openEdit(r)
}

const closeDetail = () => {
  showDetailModal.value = false
  detailReview.value = null
}

const deleteFromDetail = () => {
  if (!detailReview.value) return
  const r = detailReview.value
  closeDetail()
  deleteReview(r)
}

const handleSaved = () => {
  showFormModal.value = false
  editingReview.value = null
  fetchReviews(currentPage.value)
  fetchStats()
}

const handleClose = () => {
  showFormModal.value = false
  editingReview.value = null
}

const deleteReview = async (review: Review) => {
  const ok = await confirm({
    title: 'Eliminar reseña',
    description: `Vas a eliminar la reseña de "${review.name}". Esta acción no se puede deshacer.`,
    confirmLabel: 'Eliminar',
    confirmColor: 'error',
    confirmIcon: 'i-lucide-trash-2',
    icon: 'i-lucide-triangle-alert',
    iconColor: 'error',
  })
  if (!ok) return
  try {
    await $fetch(`${config.public.apiUrl}/admin/reviews/${review.id}`, { method: 'DELETE', headers: headers() })
    toast.add({ title: 'Reseña eliminada', icon: 'i-lucide-circle-check', color: 'success' })
    fetchReviews(currentPage.value)
    fetchStats()
  } catch {
    toast.add({ title: 'Error al eliminar', color: 'error', icon: 'i-lucide-alert-triangle' })
  }
}

const rowActions = (review: Review) => [
  [
    { label: 'Ver completa', icon: 'i-lucide-eye', onSelect: () => openDetail(review) },
    { label: 'Editar', icon: 'i-lucide-pencil', onSelect: () => openEdit(review) },
  ],
  [
    {
      label: review.published ? 'Ocultar' : 'Publicar',
      icon: review.published ? 'i-lucide-eye-off' : 'i-lucide-eye',
      onSelect: () => togglePublished(review),
    },
    {
      label: review.featured ? 'Quitar destacada' : 'Marcar destacada',
      icon: 'i-lucide-sparkles',
      onSelect: () => toggleFeatured(review),
    },
  ],
  [
    { label: 'Eliminar', icon: 'i-lucide-trash-2', color: 'error' as const, onSelect: () => deleteReview(review) },
  ],
]

const getInitials = (name: string) => {
  if (!name) return '?'
  return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

onMounted(() => {
  fetchReviews()
  fetchStats()
})
</script>

<template>
  <UDashboardPanel id="reviews-v2">
    <template #header>
      <UDashboardNavbar title="Reviews">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            icon="i-lucide-refresh-cw"
            color="neutral"
            variant="ghost"
            :loading="loading"
            @click="fetchReviews(currentPage)"
          >
            Actualizar
          </UButton>
          <UButton icon="i-lucide-plus" @click="openCreate">Nueva reseña</UButton>
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="p-6 space-y-4">
        <div>
          <h1 class="admin-h1">Reseñas y testimonios</h1>
          <p class="text-sm text-muted mt-1">
            Modera las opiniones de los clientes y destaca las mejores
          </p>
        </div>

        <!-- KPIs (5 cards) -->
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4">
          <UCard v-for="card in statsCards" :key="card.label" :ui="{ body: 'p-5' }">
            <div class="flex items-center justify-between gap-3">
              <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">{{ card.label }}</p>
                <p class="text-2xl xl:text-3xl font-bold tabular-nums mt-2 truncate">{{ card.value }}</p>
              </div>
              <div :class="['size-11 rounded-xl flex items-center justify-center shrink-0', card.bgClass]">
                <UIcon :name="card.icon" :class="['size-6', card.iconClass]" />
              </div>
            </div>
          </UCard>
        </div>

        <!-- Filters -->
        <UCard :ui="{ body: 'p-4' }">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            <UFormField label="Buscar">
              <UInput
                v-model="searchQuery"
                placeholder="Nombre, comentario, tour..."
                icon="i-lucide-search"
                class="w-full"
                @input="debouncedSearch"
              />
            </UFormField>
            <UFormField label="Calificación">
              <USelectMenu
                v-model="filterRating"
                :items="ratingOptions"
                value-key="value"
                class="w-full"
                @update:model-value="fetchReviews(1)"
              />
            </UFormField>
            <UFormField label="Estado">
              <USelectMenu
                v-model="filterPublished"
                :items="publishedOptions"
                value-key="value"
                class="w-full"
                @update:model-value="fetchReviews(1)"
              />
            </UFormField>
            <UFormField label="Destacadas">
              <USelectMenu
                v-model="filterFeatured"
                :items="featuredOptions"
                value-key="value"
                class="w-full"
                @update:model-value="fetchReviews(1)"
              />
            </UFormField>
          </div>
          <div v-if="searchQuery || filterRating || filterPublished || filterFeatured" class="flex justify-end mt-3">
            <UButton variant="ghost" size="sm" icon="i-lucide-x" @click="resetFilters">Limpiar filtros</UButton>
          </div>
        </UCard>

        <!-- List -->
        <UCard :ui="{ body: 'p-0' }">
          <!-- Loading -->
          <div v-if="loading && reviews.length === 0" class="p-6 space-y-3">
            <div v-for="i in 5" :key="i" class="flex items-start gap-4">
              <USkeleton class="size-10 rounded-full" />
              <div class="flex-1 space-y-2">
                <USkeleton class="h-4 w-1/3" />
                <USkeleton class="h-3 w-3/4" />
                <USkeleton class="h-3 w-1/2" />
              </div>
              <USkeleton class="h-6 w-20" />
            </div>
          </div>

          <!-- Empty -->
          <div v-else-if="reviews.length === 0" class="p-12 flex flex-col items-center text-center gap-3">
            <UIcon name="i-lucide-star-off" class="size-12 text-muted" />
            <h3 class="text-lg font-semibold">No hay reseñas</h3>
            <p class="text-sm text-muted">No se encontraron reseñas con los filtros aplicados.</p>
            <UButton
              v-if="searchQuery || filterRating || filterPublished || filterFeatured"
              icon="i-lucide-x"
              variant="outline"
              @click="resetFilters"
            >
              Limpiar filtros
            </UButton>
          </div>

          <!-- Rows compactas (1 línea) -->
          <ul v-else class="divide-y divide-default">
            <li
              v-for="review in reviews"
              :key="review.id"
              class="px-5 py-3 flex items-center gap-3 hover:bg-elevated/30 transition-colors cursor-pointer"
              @click="openDetail(review)"
            >
              <div class="size-10 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                <span class="text-sm font-black text-primary">{{ getInitials(review.name) }}</span>
              </div>

              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <p class="text-sm font-bold truncate">{{ review.name }}</p>
                  <div class="flex items-center gap-0.5 shrink-0">
                    <UIcon
                      v-for="i in 5"
                      :key="i"
                      name="i-lucide-star"
                      :class="['size-3', i <= review.rating ? 'text-yellow-400 fill-yellow-400' : 'text-muted']"
                    />
                  </div>
                </div>
                <p class="text-xs text-muted truncate mt-0.5">
                  {{ review.title || review.comment }}
                </p>
              </div>

              <UBadge
                :color="languageBadge[review.language]?.color || 'neutral'"
                variant="subtle"
                size="xs"
                class="font-mono hidden md:inline-flex"
              >
                {{ languageBadge[review.language]?.label || review.language?.toUpperCase() }}
              </UBadge>

              <UBadge
                v-if="review.tour"
                color="success"
                variant="subtle"
                size="xs"
                icon="i-lucide-map-pin"
                class="font-mono hidden lg:inline-flex"
              >
                {{ review.tour.code }}
              </UBadge>
              <UBadge
                v-else
                color="warning"
                variant="subtle"
                size="xs"
                icon="i-lucide-link-2-off"
                class="hidden lg:inline-flex"
              >
                Sin tour
              </UBadge>

              <UBadge
                v-if="review.featured"
                color="primary"
                variant="subtle"
                size="xs"
                icon="i-lucide-sparkles"
                title="Destacada"
              />

              <UBadge
                :color="review.published ? 'success' : 'neutral'"
                variant="subtle"
                size="sm"
                :icon="review.published ? 'i-lucide-eye' : 'i-lucide-eye-off'"
              >
                {{ review.published ? 'Publicada' : 'Oculta' }}
              </UBadge>

              <UDropdownMenu :items="rowActions(review)" :content="{ align: 'end' }">
                <UButton
                  icon="i-lucide-ellipsis-vertical"
                  color="neutral"
                  variant="ghost"
                  size="sm"
                  @click.stop
                />
              </UDropdownMenu>
            </li>
          </ul>

          <!-- Pagination -->
          <div v-if="meta && meta.last_page > 1" class="p-4 border-t border-default flex items-center justify-between flex-wrap gap-3">
            <p class="text-xs text-muted">
              Mostrando <span class="font-semibold text-default">{{ meta.from }}-{{ meta.to }}</span>
              de <span class="font-semibold text-default">{{ meta.total }}</span> reseñas
            </p>
            <div class="flex items-center gap-1">
              <UButton
                icon="i-lucide-chevron-left"
                color="neutral"
                variant="ghost"
                size="sm"
                :disabled="meta.current_page === 1"
                @click="changePage(meta.current_page - 1)"
              />
              <UButton
                v-for="page in visiblePages"
                :key="page"
                :color="meta.current_page === page ? 'primary' : 'neutral'"
                :variant="meta.current_page === page ? 'solid' : 'ghost'"
                size="sm"
                @click="changePage(page)"
              >
                {{ page }}
              </UButton>
              <UButton
                icon="i-lucide-chevron-right"
                color="neutral"
                variant="ghost"
                size="sm"
                :disabled="meta.current_page === meta.last_page"
                @click="changePage(meta.current_page + 1)"
              />
            </div>
          </div>
        </UCard>
      </div>
    </template>
  </UDashboardPanel>

  <ReviewFormModalV2
    :open="showFormModal"
    :review="editingReview"
    @close="handleClose"
    @saved="handleSaved"
  />

  <ReviewDetailModalV2
    v-if="detailReview"
    :open="showDetailModal"
    :review="detailReview"
    @close="closeDetail"
    @edit="editFromDetail"
    @toggle-published="detailReview && togglePublished(detailReview)"
    @toggle-featured="detailReview && toggleFeatured(detailReview)"
    @delete="deleteFromDetail"
  />
</template>
