<template>
  <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="$emit('close')">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-lg w-full">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white">
          {{ user ? 'Editar Usuario' : 'Nuevo Usuario' }}
        </h2>
        <button @click="$emit('close')" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="p-6">
        <div class="space-y-4">
          <!-- Name -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
              Nombre <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none text-slate-900 dark:text-white"
              placeholder="Nombre completo"
            />
          </div>

          <!-- Email -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
              Email <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.email"
              type="email"
              required
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none text-slate-900 dark:text-white"
              placeholder="correo@ejemplo.com"
            />
          </div>

          <!-- Role -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
              Rol <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.role"
              required
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none text-slate-900 dark:text-white"
            >
              <option value="customer">Cliente</option>
              <option value="staff">Staff</option>
              <option value="admin">Administrador</option>
            </select>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Define los permisos del usuario</p>
          </div>

          <!-- Password (only for new users or if changing) -->
          <div v-if="!user">
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
              Contraseña <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.password"
              type="password"
              :required="!user"
              minlength="8"
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none text-slate-900 dark:text-white"
              placeholder="Mínimo 8 caracteres"
            />
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Mínimo 8 caracteres</p>
          </div>

          <div v-else>
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
              Nueva Contraseña (opcional)
            </label>
            <input
              v-model="form.password"
              type="password"
              minlength="8"
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none text-slate-900 dark:text-white"
              placeholder="Dejar en blanco para no cambiar"
            />
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Dejar en blanco para mantener la contraseña actual</p>
          </div>

          <!-- Error message -->
          <div v-if="error" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <p class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-slate-200 dark:border-slate-700">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
          >
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="saving"
            class="px-6 py-2 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
          >
            <span v-if="saving" class="animate-spin material-symbols-outlined text-base">progress_activity</span>
            {{ saving ? 'Guardando...' : (user ? 'Actualizar' : 'Crear') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Props {
  user?: any
}

const props = defineProps<Props>()
const emit = defineEmits(['close', 'saved'])

const config = useRuntimeConfig()
const saving = ref(false)
const error = ref('')

const form = ref({
  name: '',
  email: '',
  role: 'customer',
  password: ''
})

// Load user data if editing
if (props.user) {
  form.value = {
    name: props.user.name || '',
    email: props.user.email || '',
    role: props.user.role || 'customer',
    password: ''
  }
}

const handleSubmit = async () => {
  error.value = ''
  saving.value = true

  try {
    const url = props.user
      ? `${config.public.apiUrl}/admin/users/${props.user.id}`
      : `${config.public.apiUrl}/admin/users`

    const method = props.user ? 'PUT' : 'POST'

    // Only include password if it's set
    const payload: any = {
      name: form.value.name,
      email: form.value.email,
      role: form.value.role
    }

    if (form.value.password) {
      payload.password = form.value.password
    }

    const response = await fetch(url, {
      method,
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    })

    if (!response.ok) {
      const data = await response.json()
      throw new Error(data.message || 'Error al guardar usuario')
    }

    emit('saved')
  } catch (err: any) {
    error.value = err.message || 'Error al guardar el usuario'
    console.error('Error saving user:', err)
  } finally {
    saving.value = false
  }
}
</script>
