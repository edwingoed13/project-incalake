<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useAuthStore } from '~/stores/auth'

interface Props {
  review?: any
  open: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:open': [value: boolean]
  close: []
  saved: []
}>()

const config = useRuntimeConfig()
const auth = useAuthStore()
const toast = useToast()

const saving = ref(false)
const error = ref('')

const defaultForm = () => ({
  name: '',
  review_date: '',
  rating: 5,
  title: '',
  comment: '',
  language: 'en',
  opinion: '',
  published: true,
  featured: false,
  tour_id: null as number | null,
})

const form = ref(defaultForm())
const tourSearchQuery = ref('')
const tourResults = ref<any[]>([])
const selectedTourName = ref('')
let tourSearchTimer: any = null

const isEdit = computed(() => !!props.review)

const languageOptions = [
  { label: 'Inglés', value: 'en' },
  { label: 'Español', value: 'es' },
  { label: 'Francés', value: 'fr' },
  { label: 'Alemán', value: 'de' },
  { label: 'Portugués', value: 'pt' },
  { label: 'Italiano', value: 'it' },
]

const headers = () => ({
  Authorization: `Bearer ${auth.token || localStorage.getItem('auth_token') || ''}`,
  Accept: 'application/json',
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
    if (props.review) {
      form.value = { ...defaultForm(), ...props.review, tour_id: props.review.tour_id || null }
      selectedTourName.value = props.review.tour
        ? `[${props.review.tour.code}] ${props.review.opinion || ''}`
        : ''
    } else {
      form.value = defaultForm()
      selectedTourName.value = ''
    }
    tourSearchQuery.value = ''
    tourResults.value = []
  },
  { immediate: true },
)

const searchTours = () => {
  clearTimeout(tourSearchTimer)
  if (tourSearchQuery.value.length < 2) {
    tourResults.value = []
    return
  }
  tourSearchTimer = setTimeout(async () => {
    try {
      const res: any = await $fetch(`${config.public.apiUrl}/tours?search=${encodeURIComponent(tourSearchQuery.value)}&per_page=8&active=1`)
      tourResults.value = res.data || []
    } catch {
      tourResults.value = []
    }
  }, 200)
}

const selectTour = (tour: any) => {
  form.value.tour_id = tour.id
  selectedTourName.value = `[${tour.code}] ${tour.title}`
  tourResults.value = []
  tourSearchQuery.value = ''
}

const clearTour = () => {
  form.value.tour_id = null
  selectedTourName.value = ''
}

const handleSubmit = async () => {
  if (!form.value.name || !form.value.comment) {
    error.value = 'Nombre y comentario son obligatorios.'
    return
  }
  error.value = ''
  saving.value = true
  try {
    const url = props.review
      ? `${config.public.apiUrl}/admin/reviews/${props.review.id}`
      : `${config.public.apiUrl}/admin/reviews`
    const method = props.review ? 'PUT' : 'POST'

    const res: any = await $fetch(url, { method, headers: headers(), body: form.value })
    if (res.success === false) throw new Error(res.message || 'Error al guardar')
    toast.add({
      title: props.review ? 'Reseña actualizada' : 'Reseña creada',
      icon: 'i-lucide-circle-check',
      color: 'success',
    })
    emit('saved')
    close()
  } catch (err: any) {
    error.value = err.data?.message || err.message || 'Error al guardar la reseña'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <UModal :open="open" :ui="{ content: 'max-w-2xl' }" :dismissible="!saving" @update:open="(v) => !v && close()">
    <template #content>
      <div class="bg-default rounded-lg flex flex-col max-h-[90vh]">
        <div class="px-6 py-4 border-b border-default flex items-center justify-between gap-3 shrink-0">
          <div class="flex items-center gap-3">
            <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center">
              <UIcon :name="isEdit ? 'i-lucide-pencil' : 'i-lucide-star'" class="size-5 text-primary" />
            </div>
            <h2 class="text-lg font-bold">{{ isEdit ? 'Editar reseña' : 'Nueva reseña' }}</h2>
          </div>
          <UButton icon="i-lucide-x" color="neutral" variant="ghost" size="sm" :disabled="saving" @click="close" />
        </div>

        <form class="flex-1 overflow-y-auto p-6 space-y-4" @submit.prevent="handleSubmit">
          <div class="grid grid-cols-2 gap-4">
            <UFormField label="Nombre del cliente" required>
              <UInput v-model="form.name" placeholder="Cliente" icon="i-lucide-user" class="w-full" required />
            </UFormField>
            <UFormField label="Fecha (texto libre)" hint="Ej: mar. 2026">
              <UInput v-model="form.review_date" placeholder="mar. 2026" icon="i-lucide-calendar" class="w-full" />
            </UFormField>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <UFormField label="Calificación" required>
              <div class="flex gap-1">
                <button
                  v-for="i in 5"
                  :key="i"
                  type="button"
                  class="p-1 transition-transform hover:scale-110"
                  @click="form.rating = i"
                >
                  <UIcon
                    name="i-lucide-star"
                    :class="[
                      'size-7 transition-colors',
                      i <= form.rating ? 'text-yellow-400 fill-yellow-400' : 'text-muted',
                    ]"
                  />
                </button>
                <span class="text-sm font-bold tabular-nums ml-2 self-center">{{ form.rating }}/5</span>
              </div>
            </UFormField>
            <UFormField label="Idioma">
              <USelectMenu v-model="form.language" :items="languageOptions" value-key="value" class="w-full" />
            </UFormField>
          </div>

          <UFormField label="Título">
            <UInput v-model="form.title" placeholder="Título de la reseña" class="w-full" />
          </UFormField>

          <UFormField label="Comentario" required>
            <UTextarea
              v-model="form.comment"
              :rows="4"
              placeholder="Reseña del cliente..."
              class="w-full"
              required
            />
          </UFormField>

          <UFormField label="Tour asignado" hint="Busca por título o código del tour">
            <div class="relative">
              <UInput
                v-if="!form.tour_id"
                v-model="tourSearchQuery"
                placeholder="Escribe al menos 2 caracteres..."
                icon="i-lucide-search"
                class="w-full"
                @input="searchTours"
              />
              <UAlert
                v-else
                color="success"
                variant="subtle"
                icon="i-lucide-circle-check"
                :title="selectedTourName"
                :actions="[
                  { label: 'Quitar', icon: 'i-lucide-x', color: 'neutral', variant: 'ghost', onClick: clearTour },
                ]"
              />

              <div
                v-if="tourResults.length > 0"
                class="absolute z-50 left-0 right-0 mt-1 bg-default border border-default rounded-lg shadow-xl max-h-60 overflow-y-auto"
              >
                <button
                  v-for="tour in tourResults"
                  :key="tour.id"
                  type="button"
                  class="w-full text-left px-3 py-2 hover:bg-primary/5 transition-colors flex items-center gap-2 border-b border-default last:border-b-0"
                  @click="selectTour(tour)"
                >
                  <UBadge color="neutral" variant="subtle" size="xs" class="font-mono shrink-0">{{ tour.code }}</UBadge>
                  <span class="text-xs font-semibold truncate">{{ tour.title }}</span>
                </button>
              </div>
            </div>
          </UFormField>

          <UFormField v-if="form.opinion" label="Opinión original (referencia)" hint="Solo lectura, importado de OTA">
            <UInput v-model="form.opinion" class="w-full text-muted" readonly />
          </UFormField>

          <div class="flex items-center gap-6 pt-2">
            <USwitch v-model="form.published" label="Publicada" />
            <USwitch v-model="form.featured" label="Destacada" />
          </div>

          <UAlert v-if="error" color="error" variant="subtle" icon="i-lucide-triangle-alert" :description="error" />
        </form>

        <div class="px-6 py-4 bg-elevated/30 border-t border-default flex justify-end gap-2 shrink-0">
          <UButton color="neutral" variant="ghost" :disabled="saving" @click="close">Cancelar</UButton>
          <UButton
            color="primary"
            :icon="isEdit ? 'i-lucide-save' : 'i-lucide-plus'"
            :loading="saving"
            @click="handleSubmit"
          >
            {{ saving ? 'Guardando...' : (isEdit ? 'Actualizar' : 'Crear') }}
          </UButton>
        </div>
      </div>
    </template>
  </UModal>
</template>
