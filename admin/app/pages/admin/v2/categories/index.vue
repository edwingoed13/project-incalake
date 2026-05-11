<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import CategoryFormModalV2 from '~/components/v2/CategoryFormModalV2.vue'

definePageMeta({
  layout: 'admin-v2',
  middleware: 'auth',
})

interface Category {
  id: number
  code?: string
  name: string
  slug?: string
  description?: string
  active?: boolean | number
  tours_count?: number
  translations?: any[]
  created_at?: string
}

const config = useRuntimeConfig()
const toast = useToast()
const { confirm } = useConfirm()

const loading = ref(true)
const categories = ref<Category[]>([])
const searchQuery = ref('')
const statusFilter = ref<'all' | 'active' | 'inactive'>('all')

const showFormModal = ref(false)
const editingCategory = ref<Category | null>(null)

const statusOptions = [
  { label: 'Todas', value: 'all' },
  { label: 'Activas', value: 'active' },
  { label: 'Inactivas', value: 'inactive' },
]

const authHeader = () => ({
  Authorization: `Bearer ${localStorage.getItem('auth_token') || ''}`,
})

const loadCategories = async () => {
  loading.value = true
  try {
    const response = await fetch(`${config.public.apiUrl}/categories?per_page=200`, { headers: authHeader() })
    if (!response.ok) throw new Error()
    const data = await response.json()
    categories.value = data.data || data
  } catch {
    toast.add({ title: 'Error', description: 'No se pudieron cargar las categorías.', color: 'error', icon: 'i-lucide-alert-triangle' })
  } finally {
    loading.value = false
  }
}

const isActive = (c: Category) => c.active === 1 || c.active === true

const activeCount = computed(() => categories.value.filter(isActive).length)
const inactiveCount = computed(() => categories.value.length - activeCount.value)
const withToursCount = computed(() => categories.value.filter(c => (c.tours_count || 0) > 0).length)

const filtered = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  return categories.value.filter((c) => {
    if (statusFilter.value === 'active' && !isActive(c)) return false
    if (statusFilter.value === 'inactive' && isActive(c)) return false
    if (q) {
      const hay = `${c.name || ''} ${c.slug || ''} ${c.code || ''} ${c.description || ''}`.toLowerCase()
      if (!hay.includes(q)) return false
    }
    return true
  })
})

const getInitials = (name: string) => {
  if (!name) return '?'
  return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

const openCreate = () => {
  editingCategory.value = null
  showFormModal.value = true
}

const openEdit = async (category: Category) => {
  try {
    const response = await fetch(`${config.public.apiUrl}/categories/${category.id}`, { headers: authHeader() })
    if (!response.ok) throw new Error()
    const data = await response.json()
    editingCategory.value = data.data
    showFormModal.value = true
  } catch {
    toast.add({ title: 'Error', description: 'No se pudo cargar la categoría.', color: 'error', icon: 'i-lucide-alert-triangle' })
  }
}

const handleSaved = () => {
  showFormModal.value = false
  editingCategory.value = null
  loadCategories()
}

const handleClose = () => {
  showFormModal.value = false
  editingCategory.value = null
}

const deleteCategory = async (category: Category) => {
  const ok = await confirm({
    title: 'Eliminar categoría',
    description: `Vas a eliminar "${category.name}". Si tiene tours asociados, la operación fallará.`,
    confirmLabel: 'Eliminar',
    confirmColor: 'error',
    confirmIcon: 'i-lucide-trash-2',
    icon: 'i-lucide-triangle-alert',
    iconColor: 'error',
  })
  if (!ok) return
  try {
    const response = await fetch(`${config.public.apiUrl}/admin/categories/${category.id}`, {
      method: 'DELETE',
      headers: authHeader(),
    })
    if (!response.ok) throw new Error()
    toast.add({ title: 'Categoría eliminada', icon: 'i-lucide-circle-check', color: 'success' })
    await loadCategories()
  } catch {
    toast.add({
      title: 'Error al eliminar',
      description: 'Puede que tenga tours asociados.',
      color: 'error',
      icon: 'i-lucide-alert-triangle',
    })
  }
}

const rowActions = (category: Category) => [
  [
    { label: 'Editar', icon: 'i-lucide-pencil', onSelect: () => openEdit(category) },
  ],
  [
    { label: 'Eliminar', icon: 'i-lucide-trash-2', color: 'error' as const, onSelect: () => deleteCategory(category) },
  ],
]

onMounted(() => {
  loadCategories()
})
</script>

<template>
  <UDashboardPanel id="categories-v2">
    <template #header>
      <UDashboardNavbar title="Categorías">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            icon="i-lucide-refresh-cw"
            color="neutral"
            variant="ghost"
            :loading="loading"
            @click="loadCategories"
          >
            Actualizar
          </UButton>
          <UButton icon="i-lucide-plus" @click="openCreate">Nueva categoría</UButton>
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="p-6 space-y-4">
        <div>
          <h2 class="text-2xl font-bold">Gestión de categorías</h2>
          <p class="text-sm text-muted mt-1">Organiza los tours por temática</p>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
          <UCard :ui="{ body: 'p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Total categorías</p>
                <p class="text-3xl font-bold tabular-nums mt-2">{{ categories.length }}</p>
              </div>
              <div class="size-11 rounded-xl bg-info/10 flex items-center justify-center">
                <UIcon name="i-lucide-tags" class="size-6 text-info" />
              </div>
            </div>
          </UCard>
          <UCard :ui="{ body: 'p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Activas</p>
                <p class="text-3xl font-bold tabular-nums mt-2 text-success">{{ activeCount }}</p>
              </div>
              <div class="size-11 rounded-xl bg-success/10 flex items-center justify-center">
                <UIcon name="i-lucide-circle-check" class="size-6 text-success" />
              </div>
            </div>
          </UCard>
          <UCard :ui="{ body: 'p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Inactivas</p>
                <p class="text-3xl font-bold tabular-nums mt-2 text-muted">{{ inactiveCount }}</p>
              </div>
              <div class="size-11 rounded-xl bg-elevated flex items-center justify-center">
                <UIcon name="i-lucide-ban" class="size-6 text-muted" />
              </div>
            </div>
          </UCard>
          <UCard :ui="{ body: 'p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Con tours</p>
                <p class="text-3xl font-bold tabular-nums mt-2 text-primary">{{ withToursCount }}</p>
              </div>
              <div class="size-11 rounded-xl bg-primary/10 flex items-center justify-center">
                <UIcon name="i-lucide-map-pin" class="size-6 text-primary" />
              </div>
            </div>
          </UCard>
        </div>

        <!-- Toolbar (búsqueda + filtro) -->
        <div class="flex items-end justify-between gap-3 flex-wrap">
          <UInput
            v-model="searchQuery"
            placeholder="Buscar por nombre, slug, código..."
            icon="i-lucide-search"
            size="lg"
            class="flex-1 min-w-[280px]"
          >
            <template v-if="searchQuery" #trailing>
              <UButton icon="i-lucide-x" color="neutral" variant="link" size="xs" @click="searchQuery = ''" />
            </template>
          </UInput>
          <USelectMenu
            v-model="statusFilter"
            :items="statusOptions"
            value-key="value"
            size="lg"
            class="w-40"
          />
          <p class="text-xs text-muted ml-2">
            {{ filtered.length }} de {{ categories.length }}
          </p>
        </div>

        <!-- List -->
        <UCard :ui="{ body: 'p-0' }">
          <!-- Loading -->
          <div v-if="loading && categories.length === 0" class="p-6 space-y-3">
            <div v-for="i in 6" :key="i" class="flex items-center gap-4">
              <USkeleton class="size-10 rounded-lg" />
              <div class="flex-1 space-y-2">
                <USkeleton class="h-4 w-1/3" />
                <USkeleton class="h-3 w-1/4" />
              </div>
              <USkeleton class="h-6 w-16" />
              <USkeleton class="h-6 w-20" />
            </div>
          </div>

          <!-- Empty -->
          <div v-else-if="filtered.length === 0" class="p-12 flex flex-col items-center text-center gap-3">
            <UIcon name="i-lucide-tags" class="size-12 text-muted" />
            <h3 class="text-lg font-semibold">
              {{ categories.length === 0 ? 'No hay categorías' : 'Sin resultados' }}
            </h3>
            <p class="text-sm text-muted">
              {{ categories.length === 0 ? 'Crea tu primera categoría para organizar los tours' : 'Prueba con otros filtros o términos de búsqueda' }}
            </p>
            <UButton
              v-if="categories.length === 0"
              icon="i-lucide-plus"
              color="primary"
              @click="openCreate"
            >
              Nueva categoría
            </UButton>
            <UButton
              v-else
              icon="i-lucide-x"
              variant="outline"
              @click="() => { searchQuery = ''; statusFilter = 'all' }"
            >
              Limpiar filtros
            </UButton>
          </div>

          <!-- Rows -->
          <ul v-else class="divide-y divide-default">
            <li
              v-for="category in filtered"
              :key="category.id"
              class="px-5 py-3 flex items-center gap-3 hover:bg-elevated/30 transition-colors group"
            >
              <div class="size-10 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                <span class="text-sm font-black text-primary">{{ getInitials(category.name) }}</span>
              </div>

              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <p class="text-sm font-bold truncate">{{ category.name || '(Sin nombre)' }}</p>
                  <UBadge v-if="category.code" color="neutral" variant="subtle" size="xs" class="font-mono">{{ category.code }}</UBadge>
                </div>
                <p class="text-xs text-muted truncate mt-0.5">
                  {{ category.description || category.slug || '—' }}
                </p>
              </div>

              <UBadge
                color="primary"
                variant="subtle"
                size="sm"
                icon="i-lucide-map-pin"
                class="hidden md:inline-flex"
              >
                {{ category.tours_count || 0 }} tours
              </UBadge>

              <UBadge
                :color="isActive(category) ? 'success' : 'neutral'"
                variant="subtle"
                size="sm"
                :icon="isActive(category) ? 'i-lucide-circle-check' : 'i-lucide-circle-pause'"
              >
                {{ isActive(category) ? 'Activa' : 'Inactiva' }}
              </UBadge>

              <UDropdownMenu :items="rowActions(category)" :content="{ align: 'end' }">
                <UButton icon="i-lucide-ellipsis-vertical" color="neutral" variant="ghost" size="sm" />
              </UDropdownMenu>
            </li>
          </ul>
        </UCard>
      </div>
    </template>
  </UDashboardPanel>

  <CategoryFormModalV2
    :open="showFormModal"
    :category="editingCategory"
    @close="handleClose"
    @saved="handleSaved"
  />
</template>
