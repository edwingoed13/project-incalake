<script setup lang="ts">
interface Review {
  id: number
  name: string
  review_date?: string
  rating: number
  title?: string
  comment: string
  opinion?: string
  language: string
  published: boolean
  featured: boolean
  tour_id?: number | null
  tour?: { id: number; code: string; title: string }
  created_at?: string
}

interface Props {
  review: Review
  open: boolean
}

defineProps<Props>()
const emit = defineEmits<{
  'update:open': [value: boolean]
  close: []
  edit: []
  togglePublished: []
  toggleFeatured: []
  delete: []
}>()

const close = () => {
  emit('update:open', false)
  emit('close')
}

const languageLabels: Record<string, string> = {
  es: 'Español', en: 'English', fr: 'Français',
  de: 'Deutsch', pt: 'Português', it: 'Italiano',
}

const getInitials = (name: string) => {
  if (!name) return '?'
  return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
}
</script>

<template>
  <UModal :open="open" :ui="{ content: 'max-w-2xl' }" @update:open="(v) => !v && close()">
    <template #content>
      <div class="flex flex-col max-h-[90vh] bg-default rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-default flex items-start justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="size-12 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
              <span class="text-base font-black text-primary">{{ getInitials(review.name) }}</span>
            </div>
            <div>
              <h2 class="text-lg font-bold">{{ review.name }}</h2>
              <div class="flex items-center gap-2 mt-1">
                <div class="flex items-center gap-0.5">
                  <UIcon
                    v-for="i in 5"
                    :key="i"
                    name="i-lucide-star"
                    :class="['size-4', i <= review.rating ? 'text-yellow-400 fill-yellow-400' : 'text-muted']"
                  />
                </div>
                <span class="text-sm font-bold tabular-nums">{{ review.rating }}/5</span>
              </div>
            </div>
          </div>
          <UButton icon="i-lucide-x" color="neutral" variant="ghost" size="sm" @click="close" />
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto p-6 space-y-5">
          <!-- Estado badges -->
          <div class="flex items-center gap-2 flex-wrap">
            <UBadge
              :color="review.published ? 'success' : 'neutral'"
              variant="subtle"
              size="md"
              :icon="review.published ? 'i-lucide-eye' : 'i-lucide-eye-off'"
            >
              {{ review.published ? 'Publicada' : 'Oculta' }}
            </UBadge>
            <UBadge v-if="review.featured" color="primary" variant="subtle" size="md" icon="i-lucide-sparkles">
              Destacada
            </UBadge>
            <UBadge color="info" variant="subtle" size="md" icon="i-lucide-languages">
              {{ languageLabels[review.language] || review.language?.toUpperCase() }}
            </UBadge>
            <UBadge v-if="review.review_date" color="neutral" variant="subtle" size="md" icon="i-lucide-calendar">
              {{ review.review_date }}
            </UBadge>
          </div>

          <!-- Título y comentario -->
          <div>
            <h3 class="text-[10px] font-black uppercase tracking-widest text-muted mb-2 flex items-center gap-2">
              <UIcon name="i-lucide-message-square-quote" class="size-3.5" />
              Reseña
            </h3>
            <UCard :ui="{ body: 'p-4' }">
              <p v-if="review.title" class="text-base font-bold mb-2">{{ review.title }}</p>
              <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ review.comment }}</p>
            </UCard>
          </div>

          <!-- Opinión original (si existe) -->
          <div v-if="review.opinion">
            <h3 class="text-[10px] font-black uppercase tracking-widest text-muted mb-2 flex items-center gap-2">
              <UIcon name="i-lucide-link" class="size-3.5" />
              Opinión original (referencia)
            </h3>
            <UAlert color="neutral" variant="subtle" :description="review.opinion" />
          </div>

          <!-- Tour asignado -->
          <div>
            <h3 class="text-[10px] font-black uppercase tracking-widest text-muted mb-2 flex items-center gap-2">
              <UIcon name="i-lucide-map-pin" class="size-3.5" />
              Tour asignado
            </h3>
            <UAlert
              v-if="review.tour"
              color="success"
              variant="subtle"
              icon="i-lucide-circle-check"
              :title="`[${review.tour.code}] ${review.tour.title}`"
            />
            <UAlert
              v-else
              color="warning"
              variant="subtle"
              icon="i-lucide-link-2-off"
              title="Sin tour asignado"
              description="Esta reseña no está vinculada a ningún tour específico."
            />
          </div>

          <!-- Metadata -->
          <div class="grid grid-cols-2 gap-4 text-xs">
            <div>
              <p class="text-muted mb-1">ID</p>
              <p class="font-mono font-bold">#{{ review.id }}</p>
            </div>
            <div v-if="review.created_at">
              <p class="text-muted mb-1">Creada</p>
              <p class="font-medium">{{ new Date(review.created_at).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
            </div>
          </div>
        </div>

        <!-- Footer con acciones -->
        <div class="px-6 py-4 bg-elevated/30 border-t border-default flex flex-wrap justify-between gap-2">
          <div class="flex gap-2 flex-wrap">
            <UButton
              :icon="review.published ? 'i-lucide-eye-off' : 'i-lucide-eye'"
              :color="review.published ? 'neutral' : 'success'"
              variant="outline"
              size="sm"
              @click="emit('togglePublished')"
            >
              {{ review.published ? 'Ocultar' : 'Publicar' }}
            </UButton>
            <UButton
              icon="i-lucide-sparkles"
              :color="review.featured ? 'neutral' : 'primary'"
              variant="outline"
              size="sm"
              @click="emit('toggleFeatured')"
            >
              {{ review.featured ? 'Quitar destacada' : 'Destacar' }}
            </UButton>
          </div>
          <div class="flex gap-2">
            <UButton icon="i-lucide-trash-2" color="error" variant="ghost" size="sm" @click="emit('delete')">
              Eliminar
            </UButton>
            <UButton icon="i-lucide-pencil" color="primary" size="sm" @click="emit('edit')">
              Editar
            </UButton>
          </div>
        </div>
      </div>
    </template>
  </UModal>
</template>
