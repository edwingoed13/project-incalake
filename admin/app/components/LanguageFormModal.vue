<template>
  <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="$emit('close')">
    <div class="bg-default rounded-2xl shadow-2xl max-w-lg w-full">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-default flex items-center justify-between">
        <h2 class="text-xl font-bold text-highlighted">
          {{ language ? 'Editar Idioma' : 'Nuevo Idioma' }}
        </h2>
        <button @click="$emit('close')" class="p-2 text-muted hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
          <UIcon name="i-lucide-x" class="size-5" />
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="p-6">
        <div class="space-y-4">
          <!-- Country/Language Name -->
          <div>
            <label class="block text-sm font-semibold text-default mb-2">
              Nombre del Idioma <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.country"
              type="text"
              required
              class="w-full px-4 py-2.5 bg-elevated border border-default rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none text-highlighted"
              placeholder="Ej: Español, English, Français"
            />
            <p class="text-xs text-muted mt-1">Nombre completo del idioma</p>
          </div>

          <!-- Code -->
          <div>
            <label class="block text-sm font-semibold text-default mb-2">
              Código ISO <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.code"
              type="text"
              required
              maxlength="2"
              class="w-full px-4 py-2.5 bg-elevated border border-default rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none text-highlighted font-mono text-sm uppercase"
              placeholder="ES, EN, FR, DE..."
            />
            <p class="text-xs text-muted mt-1">Código ISO 639-1 (2 letras en mayúsculas)</p>
          </div>

          <!-- Error message -->
          <div v-if="error" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <p class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-default">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-default hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
          >
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="saving"
            class="px-6 py-2 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
          >
            <UIcon v-if="saving" name="i-lucide-loader-circle" class="animate-spin size-5" />
            {{ saving ? 'Guardando...' : (language ? 'Actualizar' : 'Crear') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'

interface Props {
  language?: any
}

const props = defineProps<Props>()
const emit = defineEmits(['close', 'saved'])

const config = useRuntimeConfig()
const saving = ref(false)
const error = ref('')

const form = ref({
  country: '',
  code: ''
})

// Load language data if editing
if (props.language) {
  form.value = {
    country: props.language.country || '',
    code: props.language.code || ''
  }
}

// Auto-uppercase code
watch(() => form.value.code, (newCode) => {
  form.value.code = newCode.toUpperCase()
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
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(form.value)
    })

    if (!response.ok) {
      const data = await response.json()
      throw new Error(data.message || 'Error al guardar idioma')
    }

    emit('saved')
  } catch (err: any) {
    error.value = err.message || 'Error al guardar el idioma'
    console.error('Error saving language:', err)
  } finally {
    saving.value = false
  }
}
</script>
