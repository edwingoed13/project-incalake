<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import UserFormModalV2 from '~/components/v2/UserFormModalV2.vue'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: 'admin-v2',
  middleware: 'auth',
})

interface User {
  id: number
  name: string
  email: string
  role: string
  email_verified_at?: string | null
  created_at?: string
}

const config = useRuntimeConfig()
const toast = useToast()
const { confirm } = useConfirm()
const auth = useAuthStore()

const loading = ref(true)
const users = ref<User[]>([])
const searchQuery = ref('')
const roleFilter = ref<'all' | 'admin' | 'staff' | 'customer'>('all')

const showFormModal = ref(false)
const editingUser = ref<User | null>(null)

const roleOptions = [
  { label: 'Todos los roles', value: 'all' },
  { label: 'Administradores', value: 'admin' },
  { label: 'Staff', value: 'staff' },
  { label: 'Clientes', value: 'customer' },
]

const authHeader = () => ({
  Authorization: `Bearer ${localStorage.getItem('auth_token') || ''}`,
})

const loadUsers = async () => {
  loading.value = true
  try {
    const response = await fetch(`${config.public.apiUrl}/admin/users`, { headers: authHeader() })
    if (!response.ok) throw new Error()
    const data = await response.json()
    users.value = data.data || data
  } catch {
    toast.add({ title: 'Error', description: 'No se pudieron cargar los usuarios.', color: 'error', icon: 'i-lucide-alert-triangle' })
  } finally {
    loading.value = false
  }
}

const adminCount = computed(() => users.value.filter(u => u.role === 'admin').length)
const staffCount = computed(() => users.value.filter(u => u.role === 'staff').length)
const customerCount = computed(() => users.value.filter(u => u.role === 'customer').length)

const filtered = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  return users.value.filter((u) => {
    if (roleFilter.value !== 'all' && u.role !== roleFilter.value) return false
    if (q) {
      const hay = `${u.name || ''} ${u.email || ''}`.toLowerCase()
      if (!hay.includes(q)) return false
    }
    return true
  })
})

const roleBadge: Record<string, { color: 'secondary' | 'success' | 'info' | 'neutral'; label: string; icon: string }> = {
  admin: { color: 'secondary', label: 'Administrador', icon: 'i-lucide-shield-check' },
  staff: { color: 'success', label: 'Staff', icon: 'i-lucide-badge-check' },
  customer: { color: 'info', label: 'Cliente', icon: 'i-lucide-user' },
}

const getInitials = (name: string) => {
  if (!name) return '?'
  return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

const formatDate = (date?: string) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric' })
}

const openCreate = () => {
  editingUser.value = null
  showFormModal.value = true
}

const openEdit = async (user: User) => {
  try {
    const response = await fetch(`${config.public.apiUrl}/admin/users/${user.id}`, { headers: authHeader() })
    if (!response.ok) throw new Error()
    const data = await response.json()
    editingUser.value = data.data
    showFormModal.value = true
  } catch {
    toast.add({ title: 'Error', description: 'No se pudo cargar el usuario.', color: 'error', icon: 'i-lucide-alert-triangle' })
  }
}

const handleSaved = () => {
  showFormModal.value = false
  editingUser.value = null
  loadUsers()
}

const handleClose = () => {
  showFormModal.value = false
  editingUser.value = null
}

const deleteUser = async (user: User) => {
  if (user.id === auth.user?.id) {
    toast.add({
      title: 'Operación no permitida',
      description: 'No puedes eliminar tu propia cuenta.',
      color: 'warning',
      icon: 'i-lucide-shield-x',
    })
    return
  }
  const ok = await confirm({
    title: 'Eliminar usuario',
    description: `Vas a eliminar a "${user.name}" (${user.email}). Esta acción no se puede deshacer.`,
    confirmLabel: 'Eliminar',
    confirmColor: 'error',
    confirmIcon: 'i-lucide-trash-2',
    icon: 'i-lucide-triangle-alert',
    iconColor: 'error',
  })
  if (!ok) return
  try {
    const response = await fetch(`${config.public.apiUrl}/admin/users/${user.id}`, {
      method: 'DELETE',
      headers: authHeader(),
    })
    if (!response.ok) throw new Error()
    toast.add({ title: 'Usuario eliminado', icon: 'i-lucide-circle-check', color: 'success' })
    await loadUsers()
  } catch {
    toast.add({ title: 'Error al eliminar', color: 'error', icon: 'i-lucide-alert-triangle' })
  }
}

const rowActions = (user: User) => [
  [
    { label: 'Editar', icon: 'i-lucide-pencil', onSelect: () => openEdit(user) },
  ],
  [
    {
      label: 'Eliminar',
      icon: 'i-lucide-trash-2',
      color: 'error' as const,
      disabled: user.id === auth.user?.id,
      onSelect: () => deleteUser(user),
    },
  ],
]

onMounted(() => {
  loadUsers()
})
</script>

<template>
  <UDashboardPanel id="users-v2">
    <template #header>
      <UDashboardNavbar title="Usuarios y Roles">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            icon="i-lucide-refresh-cw"
            color="neutral"
            variant="ghost"
            :loading="loading"
            @click="loadUsers"
          >
            Actualizar
          </UButton>
          <UButton icon="i-lucide-user-plus" @click="openCreate">Nuevo usuario</UButton>
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="p-6 space-y-4">
        <div>
          <h2 class="text-2xl font-bold">Gestión de usuarios</h2>
          <p class="text-sm text-muted mt-1">Administra los accesos al sistema y los clientes registrados</p>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
          <UCard :ui="{ body: 'p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Total usuarios</p>
                <p class="text-3xl font-bold tabular-nums mt-2">{{ users.length }}</p>
              </div>
              <div class="size-11 rounded-xl bg-info/10 flex items-center justify-center">
                <UIcon name="i-lucide-users" class="size-6 text-info" />
              </div>
            </div>
          </UCard>
          <UCard :ui="{ body: 'p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Administradores</p>
                <p class="text-3xl font-bold tabular-nums mt-2 text-secondary">{{ adminCount }}</p>
              </div>
              <div class="size-11 rounded-xl bg-secondary/10 flex items-center justify-center">
                <UIcon name="i-lucide-shield-check" class="size-6 text-secondary" />
              </div>
            </div>
          </UCard>
          <UCard :ui="{ body: 'p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Staff</p>
                <p class="text-3xl font-bold tabular-nums mt-2 text-success">{{ staffCount }}</p>
              </div>
              <div class="size-11 rounded-xl bg-success/10 flex items-center justify-center">
                <UIcon name="i-lucide-badge-check" class="size-6 text-success" />
              </div>
            </div>
          </UCard>
          <UCard :ui="{ body: 'p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Clientes</p>
                <p class="text-3xl font-bold tabular-nums mt-2 text-primary">{{ customerCount }}</p>
              </div>
              <div class="size-11 rounded-xl bg-primary/10 flex items-center justify-center">
                <UIcon name="i-lucide-user" class="size-6 text-primary" />
              </div>
            </div>
          </UCard>
        </div>

        <!-- Toolbar -->
        <div class="flex items-end justify-between gap-3 flex-wrap">
          <UInput
            v-model="searchQuery"
            placeholder="Buscar por nombre o email..."
            icon="i-lucide-search"
            size="lg"
            class="flex-1 min-w-[280px]"
          >
            <template v-if="searchQuery" #trailing>
              <UButton icon="i-lucide-x" color="neutral" variant="link" size="xs" @click="searchQuery = ''" />
            </template>
          </UInput>
          <USelectMenu
            v-model="roleFilter"
            :items="roleOptions"
            value-key="value"
            size="lg"
            class="w-48"
          />
          <p class="text-xs text-muted ml-2">
            {{ filtered.length }} de {{ users.length }}
          </p>
        </div>

        <!-- List -->
        <UCard :ui="{ body: 'p-0' }">
          <!-- Loading -->
          <div v-if="loading && users.length === 0" class="p-6 space-y-3">
            <div v-for="i in 6" :key="i" class="flex items-center gap-4">
              <USkeleton class="size-10 rounded-full" />
              <div class="flex-1 space-y-2">
                <USkeleton class="h-4 w-1/3" />
                <USkeleton class="h-3 w-1/4" />
              </div>
              <USkeleton class="h-6 w-24" />
              <USkeleton class="h-6 w-20" />
            </div>
          </div>

          <!-- Empty -->
          <div v-else-if="filtered.length === 0" class="p-12 flex flex-col items-center text-center gap-3">
            <UIcon name="i-lucide-users" class="size-12 text-muted" />
            <h3 class="text-lg font-semibold">
              {{ users.length === 0 ? 'No hay usuarios' : 'Sin resultados' }}
            </h3>
            <p class="text-sm text-muted">
              {{ users.length === 0 ? 'Crea el primer usuario del sistema' : 'Prueba con otros filtros o términos' }}
            </p>
            <UButton
              v-if="users.length === 0"
              icon="i-lucide-user-plus"
              color="primary"
              @click="openCreate"
            >
              Nuevo usuario
            </UButton>
            <UButton
              v-else
              icon="i-lucide-x"
              variant="outline"
              @click="() => { searchQuery = ''; roleFilter = 'all' }"
            >
              Limpiar filtros
            </UButton>
          </div>

          <!-- Rows -->
          <ul v-else class="divide-y divide-default">
            <li
              v-for="user in filtered"
              :key="user.id"
              class="px-5 py-3 flex items-center gap-3 hover:bg-elevated/30 transition-colors group"
            >
              <div class="size-10 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                <span class="text-sm font-black text-primary">{{ getInitials(user.name) }}</span>
              </div>

              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <p class="text-sm font-bold truncate">{{ user.name || '(Sin nombre)' }}</p>
                  <UBadge v-if="user.id === auth.user?.id" color="primary" variant="subtle" size="xs">Tú</UBadge>
                </div>
                <p class="text-xs text-muted truncate mt-0.5">{{ user.email }}</p>
              </div>

              <UBadge
                v-if="user.email_verified_at"
                color="success"
                variant="subtle"
                size="sm"
                icon="i-lucide-circle-check"
                class="hidden lg:inline-flex"
              >
                Verificado
              </UBadge>
              <UBadge
                v-else
                color="neutral"
                variant="subtle"
                size="sm"
                icon="i-lucide-circle-help"
                class="hidden lg:inline-flex"
              >
                Sin verificar
              </UBadge>

              <span class="hidden md:inline text-xs text-muted whitespace-nowrap">
                {{ formatDate(user.created_at) }}
              </span>

              <UBadge
                :color="roleBadge[user.role]?.color || 'neutral'"
                variant="subtle"
                size="sm"
                :icon="roleBadge[user.role]?.icon"
              >
                {{ roleBadge[user.role]?.label || user.role }}
              </UBadge>

              <UDropdownMenu :items="rowActions(user)" :content="{ align: 'end' }">
                <UButton icon="i-lucide-ellipsis-vertical" color="neutral" variant="ghost" size="sm" />
              </UDropdownMenu>
            </li>
          </ul>
        </UCard>
      </div>
    </template>
  </UDashboardPanel>

  <UserFormModalV2
    :open="showFormModal"
    :user="editingUser"
    @close="handleClose"
    @saved="handleSaved"
  />
</template>
