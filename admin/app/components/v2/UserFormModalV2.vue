<script setup lang="ts">
import { ref, watch, computed } from 'vue'

interface Props {
  user?: any
  open: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:open': [value: boolean]
  close: []
  saved: []
}>()

const config = useRuntimeConfig()
const toast = useToast()
const saving = ref(false)
const error = ref('')

const form = ref({
  name: '',
  email: '',
  role: 'customer',
  password: '',
})

const roleOptions = [
  { label: 'Cliente', value: 'customer' },
  { label: 'Staff', value: 'staff' },
  { label: 'Administrador', value: 'admin' },
]

const isEdit = computed(() => !!props.user)

const close = () => {
  emit('update:open', false)
  emit('close')
}

watch(
  () => props.open,
  (open) => {
    if (!open) return
    error.value = ''
    if (props.user) {
      form.value = {
        name: props.user.name || '',
        email: props.user.email || '',
        role: props.user.role || 'customer',
        password: '',
      }
    } else {
      form.value = { name: '', email: '', role: 'customer', password: '' }
    }
  },
  { immediate: true },
)

const handleSubmit = async () => {
  error.value = ''
  saving.value = true
  try {
    const url = props.user
      ? `${config.public.apiUrl}/admin/users/${props.user.id}`
      : `${config.public.apiUrl}/admin/users`
    const method = props.user ? 'PUT' : 'POST'

    const payload: any = {
      name: form.value.name,
      email: form.value.email,
      role: form.value.role,
    }
    if (form.value.password) payload.password = form.value.password

    const response = await fetch(url, {
      method,
      headers: {
        Authorization: `Bearer ${localStorage.getItem('auth_token') || ''}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    })
    if (!response.ok) {
      const data = await response.json()
      throw new Error(data.message || 'Error al guardar usuario')
    }
    toast.add({
      title: props.user ? 'Usuario actualizado' : 'Usuario creado',
      icon: 'i-lucide-circle-check',
      color: 'success',
    })
    emit('saved')
    close()
  } catch (err: any) {
    error.value = err.message || 'Error al guardar el usuario'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <V2FormModalShell
    :open="open"
    :title="isEdit ? 'Editar usuario' : 'Nuevo usuario'"
    :icon="isEdit ? 'i-lucide-pencil' : 'i-lucide-user-plus'"
    width="max-w-lg"
    :busy="saving"
    @close="close"
  >
    <form class="p-6 space-y-4" @submit.prevent="handleSubmit">
          <UFormField label="Nombre" required>
            <UInput
              v-model="form.name"
              placeholder="Nombre completo"
              icon="i-lucide-user"
              class="w-full"
              required
            />
          </UFormField>

          <UFormField label="Email" required>
            <UInput
              v-model="form.email"
              type="email"
              placeholder="correo@ejemplo.com"
              icon="i-lucide-mail"
              class="w-full"
              required
            />
          </UFormField>

          <UFormField label="Rol" required hint="Define los permisos del usuario">
            <USelectMenu
              v-model="form.role"
              :items="roleOptions"
              value-key="value"
              class="w-full"
            />
          </UFormField>

          <UFormField
            :label="isEdit ? 'Nueva contraseña (opcional)' : 'Contraseña'"
            :required="!isEdit"
            :hint="isEdit ? 'Dejar en blanco para mantener la contraseña actual' : 'Mínimo 8 caracteres'"
          >
            <UInput
              v-model="form.password"
              type="password"
              :placeholder="isEdit ? 'Dejar en blanco para no cambiar' : 'Mínimo 8 caracteres'"
              icon="i-lucide-lock"
              class="w-full"
              :required="!isEdit"
              minlength="8"
            />
          </UFormField>

          <UAlert v-if="error" color="error" variant="subtle" icon="i-lucide-triangle-alert" :description="error" />

          <div class="flex justify-end gap-2 pt-4 border-t border-default">
            <UButton color="neutral" variant="ghost" :disabled="saving" @click="close">Cancelar</UButton>
            <UButton
              type="submit"
              color="primary"
              :icon="isEdit ? 'i-lucide-save' : 'i-lucide-user-plus'"
              :loading="saving"
            >
              {{ saving ? 'Guardando...' : (isEdit ? 'Actualizar' : 'Crear') }}
            </UButton>
          </div>
        </form>
  </V2FormModalShell>
</template>
