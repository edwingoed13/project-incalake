<template>
  <div class="p-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between mb-2">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Usuarios</h1>
        <button
          @click="showCreateModal = true"
          class="px-4 py-2.5 bg-primary text-white rounded-lg font-semibold hover:bg-primary/90 transition-colors inline-flex items-center gap-2"
        >
          <span class="material-symbols-outlined">add</span>
          Nuevo Usuario
        </button>
      </div>
      <p class="text-slate-600 dark:text-slate-400">Gestiona los usuarios del sistema</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
            <span class="material-symbols-outlined text-3xl text-blue-600 dark:text-blue-400">group</span>
          </div>
          <div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Total Usuarios</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ users.length }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
            <span class="material-symbols-outlined text-3xl text-purple-600 dark:text-purple-400">admin_panel_settings</span>
          </div>
          <div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Administradores</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ adminCount }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
            <span class="material-symbols-outlined text-3xl text-green-600 dark:text-green-400">badge</span>
          </div>
          <div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Staff</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ staffCount }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
            <span class="material-symbols-outlined text-3xl text-orange-600 dark:text-orange-400">person</span>
          </div>
          <div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Clientes</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ customerCount }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 mb-6 border border-slate-200 dark:border-slate-700">
      <div class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Buscar por nombre o email..."
            class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none text-slate-900 dark:text-white"
          />
        </div>
        <select
          v-model="filters.role"
          class="px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none text-slate-900 dark:text-white"
        >
          <option value="">Todos los roles</option>
          <option value="admin">Administrador</option>
          <option value="staff">Staff</option>
          <option value="customer">Cliente</option>
        </select>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="bg-white dark:bg-slate-800 rounded-xl p-12 text-center border border-slate-200 dark:border-slate-700">
      <div class="animate-spin inline-block w-12 h-12 border-4 border-primary border-t-transparent rounded-full mb-4"></div>
      <p class="text-slate-600 dark:text-slate-400">Cargando usuarios...</p>
    </div>

    <!-- Users Table -->
    <div v-else-if="filteredUsers.length > 0" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Usuario</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Email</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Rol</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Verificado</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Fecha Registro</th>
              <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
            <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary">{{ getRoleIcon(user.role) }}</span>
                  </div>
                  <div>
                    <div class="font-semibold text-slate-900 dark:text-white">{{ user.name }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">ID: {{ user.id }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-slate-900 dark:text-white">{{ user.email }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getRoleBadgeClass(user.role)" class="px-3 py-1 rounded-full text-xs font-semibold">
                  {{ getRoleLabel(user.role) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span v-if="user.email_verified_at" class="inline-flex items-center gap-1 text-green-600 dark:text-green-400">
                  <span class="material-symbols-outlined text-sm">check_circle</span>
                  <span class="text-xs">Verificado</span>
                </span>
                <span v-else class="inline-flex items-center gap-1 text-slate-400 dark:text-slate-500">
                  <span class="material-symbols-outlined text-sm">cancel</span>
                  <span class="text-xs">No verificado</span>
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                {{ formatDate(user.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="editUser(user)"
                    class="p-2 text-slate-600 dark:text-slate-400 hover:text-primary hover:bg-primary/10 rounded-lg transition-colors"
                    title="Editar"
                  >
                    <span class="material-symbols-outlined text-base">edit</span>
                  </button>
                  <button
                    @click="deleteUser(user)"
                    class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                    title="Eliminar"
                  >
                    <span class="material-symbols-outlined text-base">delete</span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="bg-white dark:bg-slate-800 rounded-xl p-12 text-center border border-slate-200 dark:border-slate-700">
      <span class="material-symbols-outlined text-6xl text-slate-300 dark:text-slate-600 mb-4">group</span>
      <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">No hay usuarios</h3>
      <p class="text-slate-600 dark:text-slate-400 mb-6">{{ filters.search || filters.role ? 'No se encontraron usuarios con los filtros aplicados' : 'Crea tu primer usuario' }}</p>
      <button
        v-if="!filters.search && !filters.role"
        @click="showCreateModal = true"
        class="px-6 py-3 bg-primary text-white rounded-lg font-semibold hover:bg-primary/90 transition-colors inline-flex items-center gap-2"
      >
        <span class="material-symbols-outlined">add</span>
        Nuevo Usuario
      </button>
    </div>

    <!-- Create/Edit Modal -->
    <UserFormModal
      v-if="showCreateModal || editingUser"
      :user="editingUser"
      @close="closeModal"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

definePageMeta({
  layout: 'admin',
  middleware: 'auth'
})

const config = useRuntimeConfig()
const loading = ref(true)
const users = ref([])
const showCreateModal = ref(false)
const editingUser = ref(null)

const filters = ref({
  search: '',
  role: ''
})

const adminCount = computed(() => users.value.filter((u: any) => u.role === 'admin').length)
const staffCount = computed(() => users.value.filter((u: any) => u.role === 'staff').length)
const customerCount = computed(() => users.value.filter((u: any) => u.role === 'customer').length)

const filteredUsers = computed(() => {
  let result = users.value

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    result = result.filter((u: any) =>
      u.name.toLowerCase().includes(search) ||
      u.email.toLowerCase().includes(search)
    )
  }

  if (filters.value.role) {
    result = result.filter((u: any) => u.role === filters.value.role)
  }

  return result
})

const loadUsers = async () => {
  loading.value = true
  try {
    const response = await fetch(`${config.public.apiUrl}/admin/users`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
      }
    })

    if (!response.ok) throw new Error('Error al cargar usuarios')

    const data = await response.json()
    users.value = data.data || data
  } catch (error) {
    console.error('Error loading users:', error)
  } finally {
    loading.value = false
  }
}

const editUser = async (user: any) => {
  try {
    const response = await fetch(`${config.public.apiUrl}/admin/users/${user.id}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
      }
    })

    if (!response.ok) throw new Error('Error al cargar usuario')

    const data = await response.json()
    editingUser.value = data.data
  } catch (error) {
    console.error('Error loading user:', error)
    alert('Error al cargar los datos del usuario')
  }
}

const deleteUser = async (user: any) => {
  if (!confirm(`¿Eliminar el usuario "${user.name}"?`)) return

  try {
    const response = await fetch(`${config.public.apiUrl}/admin/users/${user.id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
      }
    })

    if (!response.ok) throw new Error('Error al eliminar usuario')

    await loadUsers()
  } catch (error) {
    console.error('Error deleting user:', error)
    alert('Error al eliminar el usuario.')
  }
}

const closeModal = () => {
  showCreateModal.value = false
  editingUser.value = null
}

const handleSaved = () => {
  closeModal()
  loadUsers()
}

const getRoleIcon = (role: string) => {
  const icons: any = {
    admin: 'admin_panel_settings',
    staff: 'badge',
    customer: 'person'
  }
  return icons[role] || 'person'
}

const getRoleLabel = (role: string) => {
  const labels: any = {
    admin: 'Administrador',
    staff: 'Staff',
    customer: 'Cliente'
  }
  return labels[role] || role
}

const getRoleBadgeClass = (role: string) => {
  const classes: any = {
    admin: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    staff: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    customer: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
  }
  return classes[role] || 'bg-slate-100 text-slate-700 dark:bg-slate-900/30 dark:text-slate-400'
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

onMounted(() => {
  loadUsers()
})
</script>
