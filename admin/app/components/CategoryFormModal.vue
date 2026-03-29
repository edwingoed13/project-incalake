<template>
  <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="$emit('close')">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-2xl w-full">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white">
          {{ category ? 'Editar Categoría' : 'Nueva Categoría' }}
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
              placeholder="Ej: Tours en Puno"
            />
          </div>

          <!-- Slug -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
              Slug <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.slug"
              type="text"
              required
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none text-slate-900 dark:text-white font-mono text-sm"
              placeholder="tours-en-puno"
            />
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">URL amigable (solo letras minúsculas, números y guiones)</p>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
              Descripción
            </label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none text-slate-900 dark:text-white resize-none"
              placeholder="Descripción de la categoría..."
            ></textarea>
          </div>

          <!-- Icon -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
              Ícono (Material Symbols)
            </label>
            <input
              v-model="form.icon"
              type="text"
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none text-slate-900 dark:text-white"
              placeholder="tour, landscape, beach_access, etc."
            />
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
              Ver íconos en: <a href="https://fonts.google.com/icons" target="_blank" class="text-primary hover:underline">Google Material Symbols</a>
            </p>
          </div>

          <!-- Active -->
          <div class="flex items-center gap-3">
            <input
              v-model="form.active"
              type="checkbox"
              id="active"
              class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-primary focus:ring-primary/30"
            />
            <label for="active" class="text-sm font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
              Categoría activa
            </label>
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
            {{ saving ? 'Guardando...' : (category ? 'Actualizar' : 'Crear') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'

interface Props {
  category?: any
}

const props = defineProps<Props>()
const emit = defineEmits(['close', 'saved'])

const config = useRuntimeConfig()
const saving = ref(false)
const error = ref('')

const form = ref({
  name: '',
  slug: '',
  description: '',
  icon: 'category',
  active: true
})

// Load category data if editing
if (props.category) {
  // Get Spanish translation (language_id = 1)
  const spanishTranslation = props.category.translations?.find((t: any) => t.language_id === 1)

  form.value = {
    name: spanishTranslation?.name || props.category.name || '',
    slug: spanishTranslation?.slug || props.category.slug || '',
    description: spanishTranslation?.description || props.category.description || '',
    icon: props.category.icon || 'category',
    active: props.category.active !== undefined ? (props.category.active === 1 || props.category.active === true) : true
  }
}

// Auto-generate slug from name
watch(() => form.value.name, (newName) => {
  if (!props.category) { // Only auto-generate for new categories
    form.value.slug = newName
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '') // Remove accents
      .replace(/[^a-z0-9\s-]/g, '') // Remove special chars
      .replace(/\s+/g, '-') // Replace spaces with -
      .replace(/-+/g, '-') // Replace multiple - with single -
      .trim()
  }
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
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(form.value)
    })

    if (!response.ok) {
      const data = await response.json()
      throw new Error(data.message || 'Error al guardar categoría')
    }

    emit('saved')
  } catch (err: any) {
    error.value = err.message || 'Error al guardar la categoría'
    console.error('Error saving category:', err)
  } finally {
    saving.value = false
  }
}
</script>
