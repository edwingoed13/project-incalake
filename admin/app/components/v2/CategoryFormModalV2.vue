<script setup lang="ts">
import { ref, watch } from 'vue'

interface Props {
  category?: any
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
  slug: '',
  description: '',
  active: true,
})

const close = () => {
  emit('update:open', false)
  emit('close')
}

watch(
  () => props.open,
  (open) => {
    if (!open) return
    error.value = ''
    if (props.category) {
      const spanish = props.category.translations?.find((t: any) => t.language_id === 1)
      form.value = {
        name: spanish?.name || props.category.name || '',
        slug: spanish?.slug || props.category.slug || '',
        description: spanish?.description || props.category.description || '',
        active: props.category.active !== undefined
          ? (props.category.active === 1 || props.category.active === true)
          : true,
      }
    } else {
      form.value = { name: '', slug: '', description: '', active: true }
    }
  },
  { immediate: true },
)

watch(() => form.value.name, (newName) => {
  if (props.category) return
  form.value.slug = newName
    .toLowerCase()
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .trim()
})

const handleSubmit = async () => {
  error.value = ''
  saving.value = true
  try {
    const url = props.category
      ? `${config.public.apiUrl}/admin/categories/${props.category.id}`
      : `${config.public.apiUrl}/admin/categories`
    const method = props.category ? 'PUT' : 'POST'

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
      throw new Error(data.message || 'Error al guardar categoría')
    }
    toast.add({
      title: props.category ? 'Categoría actualizada' : 'Categoría creada',
      icon: 'i-lucide-circle-check',
      color: 'success',
    })
    emit('saved')
    close()
  } catch (err: any) {
    error.value = err.message || 'Error al guardar la categoría'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <UModal :open="open" :ui="{ content: 'max-w-2xl' }" :dismissible="!saving" @update:open="(v) => !v && close()">
    <template #content>
      <div class="bg-default rounded-lg">
        <div class="px-6 py-4 border-b border-default flex items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center">
              <UIcon :name="category ? 'i-lucide-pencil' : 'i-lucide-plus'" class="size-5 text-primary" />
            </div>
            <h2 class="text-lg font-bold">{{ category ? 'Editar categoría' : 'Nueva categoría' }}</h2>
          </div>
          <UButton icon="i-lucide-x" color="neutral" variant="ghost" size="sm" :disabled="saving" @click="close" />
        </div>

        <form class="p-6 space-y-4" @submit.prevent="handleSubmit">
          <UFormField label="Nombre" required>
            <UInput
              v-model="form.name"
              placeholder="Ej: Tours en Puno"
              class="w-full"
              required
            />
          </UFormField>

          <UFormField label="Slug" required hint="URL amigable (solo letras minúsculas, números y guiones)">
            <UInput
              v-model="form.slug"
              placeholder="tours-en-puno"
              class="w-full font-mono"
              required
            />
          </UFormField>

          <UFormField label="Descripción">
            <UTextarea
              v-model="form.description"
              :rows="3"
              placeholder="Descripción de la categoría..."
              class="w-full"
            />
          </UFormField>

          <UFormField>
            <USwitch v-model="form.active" label="Categoría activa" />
          </UFormField>

          <UAlert v-if="error" color="error" variant="subtle" icon="i-lucide-triangle-alert" :description="error" />

          <div class="flex justify-end gap-2 pt-4 border-t border-default">
            <UButton color="neutral" variant="ghost" :disabled="saving" @click="close">Cancelar</UButton>
            <UButton
              type="submit"
              color="primary"
              :icon="category ? 'i-lucide-save' : 'i-lucide-plus'"
              :loading="saving"
            >
              {{ saving ? 'Guardando...' : (category ? 'Actualizar' : 'Crear') }}
            </UButton>
          </div>
        </form>
      </div>
    </template>
  </UModal>
</template>
