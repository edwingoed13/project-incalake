<script setup lang="ts">
import { ref, watch, computed } from 'vue'

interface Props {
  language?: any
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
  country: '',
  code: '',
})

const isEdit = computed(() => !!props.language)

const close = () => {
  emit('update:open', false)
  emit('close')
}

watch(
  () => props.open,
  (open) => {
    if (!open) return
    error.value = ''
    if (props.language) {
      form.value = {
        country: props.language.country || '',
        code: props.language.code || '',
      }
    } else {
      form.value = { country: '', code: '' }
    }
  },
  { immediate: true },
)

watch(() => form.value.code, (v) => {
  if (v && v !== v.toUpperCase()) form.value.code = v.toUpperCase()
})

const handleSubmit = async () => {
  error.value = ''
  saving.value = true
  try {
    const url = props.language
      ? `${config.public.apiUrl}/admin/languages/${props.language.id}`
      : `${config.public.apiUrl}/admin/languages`
    const method = props.language ? 'PUT' : 'POST'

    const response = await fetch(url, {
      method,
      headers: {
        Authorization: `Bearer ${localStorage.getItem('auth_token') || ''}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(form.value),
    })
    if (!response.ok) {
      const data = await response.json()
      throw new Error(data.message || 'Error al guardar idioma')
    }
    toast.add({
      title: props.language ? 'Idioma actualizado' : 'Idioma creado',
      icon: 'i-lucide-circle-check',
      color: 'success',
    })
    emit('saved')
    close()
  } catch (err: any) {
    error.value = err.message || 'Error al guardar el idioma'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <UModal :open="open" :ui="{ content: 'max-w-md' }" :dismissible="!saving" @update:open="(v) => !v && close()">
    <template #content>
      <div class="bg-default rounded-lg">
        <div class="px-6 py-4 border-b border-default flex items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center">
              <UIcon :name="isEdit ? 'i-lucide-pencil' : 'i-lucide-languages'" class="size-5 text-primary" />
            </div>
            <h2 class="text-lg font-bold">{{ isEdit ? 'Editar idioma' : 'Nuevo idioma' }}</h2>
          </div>
          <UButton icon="i-lucide-x" color="neutral" variant="ghost" size="sm" :disabled="saving" @click="close" />
        </div>

        <form class="p-6 space-y-4" @submit.prevent="handleSubmit">
          <UFormField label="Nombre del idioma" required hint="Tal como se mostrará a los clientes (ej: Español, English)">
            <UInput v-model="form.country" placeholder="Español" icon="i-lucide-flag" class="w-full" required />
          </UFormField>

          <UFormField label="Código ISO" required hint="2 letras en mayúscula (ES, EN, FR, DE, IT, PT, ZH...)">
            <UInput
              v-model="form.code"
              placeholder="ES"
              icon="i-lucide-hash"
              class="w-full font-mono uppercase"
              maxlength="3"
              required
            />
          </UFormField>

          <UAlert v-if="error" color="error" variant="subtle" icon="i-lucide-triangle-alert" :description="error" />

          <div class="flex justify-end gap-2 pt-4 border-t border-default">
            <UButton color="neutral" variant="ghost" :disabled="saving" @click="close">Cancelar</UButton>
            <UButton
              type="submit"
              color="primary"
              :icon="isEdit ? 'i-lucide-save' : 'i-lucide-plus'"
              :loading="saving"
            >
              {{ saving ? 'Guardando...' : (isEdit ? 'Actualizar' : 'Crear') }}
            </UButton>
          </div>
        </form>
      </div>
    </template>
  </UModal>
</template>
