<script setup lang="ts">
/**
 * Hover hint that answers "where does this text end up?" with a live mock of
 * the real surfaces, filled with what the operator is typing.
 *
 * Written after checking the frontend rather than trusting the old label: the
 * short description does NOT appear on the catalogue's grid cards (the default
 * view). It shows in the list view, and — more valuable — it is what Google
 * prints under the tour and what WhatsApp shows when the link is shared. An
 * operator who knows that writes a very different sentence.
 */
const props = defineProps<{
  /** Live value of the field, so the operator sees their own words in place. */
  text?: string
  /** Public title, used as the headline of each mock. */
  title?: string
  citySlug?: string
  slug?: string
}>()

const sample = computed(() =>
  (props.text || '').trim() || 'Aquí aparecerá el resumen que escribas…'
)
const heading = computed(() => (props.title || '').trim() || 'Título del tour')
const url = computed(() =>
  `incalake.com › ${props.citySlug || 'puno'} › ${(props.slug || 'tour').slice(0, 28)}`
)
</script>

<template>
  <UPopover mode="hover" :ui="{ content: 'w-[340px] p-0' }">
    <button
      type="button"
      class="inline-flex items-center gap-1 text-primary hover:underline"
      aria-label="Ver dónde se usa este texto"
    >
      <UIcon name="i-lucide-eye" class="size-3.5" />
      <span class="text-[11px] font-semibold">¿Dónde se ve?</span>
    </button>

    <template #content>
      <div class="p-3 space-y-3">
        <p class="text-[10px] font-black uppercase tracking-widest text-muted">
          Este texto aparece en 3 lugares
        </p>

        <!-- 1. Catalogue, list view -->
        <div class="space-y-1">
          <p class="text-[10px] font-bold text-muted">1 · Catálogo (vista lista)</p>
          <div class="rounded-lg border border-default bg-default p-2 flex gap-2">
            <div class="size-10 rounded bg-elevated shrink-0 flex items-center justify-center">
              <UIcon name="i-lucide-image" class="size-4 text-muted" />
            </div>
            <div class="min-w-0">
              <p class="text-[11px] font-bold truncate">{{ heading }}</p>
              <p class="text-[10px] text-muted line-clamp-2 leading-snug">{{ sample }}</p>
            </div>
          </div>
        </div>

        <!-- 2. Google result -->
        <div class="space-y-1">
          <p class="text-[10px] font-bold text-muted">2 · Resultado en Google</p>
          <div class="rounded-lg border border-default bg-default p-2">
            <p class="text-[9px] text-muted truncate">{{ url }}</p>
            <p class="text-[11px] text-blue-600 dark:text-blue-400 font-medium truncate">{{ heading }}</p>
            <p class="text-[10px] text-muted line-clamp-2 leading-snug">{{ sample }}</p>
          </div>
        </div>

        <!-- 3. Shared link -->
        <div class="space-y-1">
          <p class="text-[10px] font-bold text-muted">3 · Al compartir por WhatsApp</p>
          <div class="rounded-lg border border-default bg-elevated/60 p-2">
            <div class="h-8 rounded bg-elevated mb-1.5 flex items-center justify-center">
              <UIcon name="i-lucide-image" class="size-3.5 text-muted" />
            </div>
            <p class="text-[11px] font-bold truncate">{{ heading }}</p>
            <p class="text-[10px] text-muted line-clamp-2 leading-snug">{{ sample }}</p>
          </div>
        </div>

        <p class="text-[10px] text-muted leading-snug pt-1 border-t border-default">
          En Google y WhatsApp solo se usa si el campo
          <span class="font-semibold">Meta descripción</span> (paso SEO) está vacío.
        </p>
      </div>
    </template>
  </UPopover>
</template>
