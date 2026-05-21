<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="open" class="fixed inset-0 z-[200] flex items-end sm:items-center justify-center">
        <div class="absolute inset-0 bg-black/50" @click="$emit('close')"></div>

        <div class="relative bg-white dark:bg-slate-900 w-full sm:max-w-md sm:rounded-2xl rounded-t-3xl shadow-2xl">
          <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-800 dark:text-slate-100">Compartir tour</h3>
            <button
              @click="$emit('close')"
              class="p-1.5 -mr-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 active:scale-95 transition"
              aria-label="Cerrar"
            >
              <XMarkIcon class="size-5" />
            </button>
          </div>

          <div class="p-5 space-y-4">
            <p v-if="title" class="text-sm font-semibold text-slate-700 dark:text-slate-200 line-clamp-2">{{ title }}</p>

            <!-- Quick share buttons -->
            <div class="grid grid-cols-4 gap-3">
              <a
                :href="`https://wa.me/?text=${encodedShare}`"
                target="_blank"
                rel="noopener"
                class="flex flex-col items-center gap-1.5 p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-95 transition"
              >
                <div class="size-12 rounded-full bg-[#25D366] flex items-center justify-center text-white">
                  <svg viewBox="0 0 24 24" fill="currentColor" class="size-6"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479s1.065 2.876 1.213 3.074c.149.198 2.096 3.2 5.077 4.487.71.306 1.263.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.42 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c0-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413"/></svg>
                </div>
                <span class="text-[10px] font-semibold text-slate-600 dark:text-slate-300">WhatsApp</span>
              </a>

              <a
                :href="`https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`"
                target="_blank"
                rel="noopener"
                class="flex flex-col items-center gap-1.5 p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-95 transition"
              >
                <div class="size-12 rounded-full bg-[#1877F2] flex items-center justify-center text-white">
                  <svg viewBox="0 0 24 24" fill="currentColor" class="size-6"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </div>
                <span class="text-[10px] font-semibold text-slate-600 dark:text-slate-300">Facebook</span>
              </a>

              <a
                :href="`https://twitter.com/intent/tweet?text=${encodedShare}`"
                target="_blank"
                rel="noopener"
                class="flex flex-col items-center gap-1.5 p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-95 transition"
              >
                <div class="size-12 rounded-full bg-black flex items-center justify-center text-white">
                  <svg viewBox="0 0 24 24" fill="currentColor" class="size-5"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </div>
                <span class="text-[10px] font-semibold text-slate-600 dark:text-slate-300">X</span>
              </a>

              <a
                :href="`mailto:?subject=${encodedTitle}&body=${encodedShare}`"
                class="flex flex-col items-center gap-1.5 p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-95 transition"
              >
                <div class="size-12 rounded-full bg-slate-700 flex items-center justify-center text-white">
                  <EnvelopeIcon class="size-6" />
                </div>
                <span class="text-[10px] font-semibold text-slate-600 dark:text-slate-300">Email</span>
              </a>
            </div>

            <!-- Copy link -->
            <div class="flex items-stretch gap-2 border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
              <input
                :value="url"
                readonly
                class="flex-1 min-w-0 px-3 py-2.5 text-xs text-slate-700 dark:text-slate-300 bg-transparent focus:outline-none truncate"
                @focus="($event.target as HTMLInputElement).select()"
              />
              <button
                @click="copyLink"
                class="shrink-0 px-3 sm:px-4 py-2.5 text-xs font-bold transition-colors flex items-center gap-1.5"
                :class="copied ? 'bg-green-500 text-white' : 'bg-primary text-white hover:brightness-110'"
              >
                <CheckIcon v-if="copied" class="size-4" />
                <ClipboardIcon v-else class="size-4" />
                {{ copied ? 'Copiado' : 'Copiar' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { XMarkIcon, EnvelopeIcon, ClipboardIcon, CheckIcon } from '@heroicons/vue/24/outline'

const props = defineProps<{
  open: boolean
  url: string
  title?: string
}>()

defineEmits<{ close: [] }>()

const copied = ref(false)

const encodedUrl = computed(() => encodeURIComponent(props.url))
const encodedTitle = computed(() => encodeURIComponent(props.title || ''))
const encodedShare = computed(() => encodeURIComponent(`${props.title ? props.title + ' — ' : ''}${props.url}`))

async function copyLink() {
  try {
    await navigator.clipboard.writeText(props.url)
    copied.value = true
    setTimeout(() => { copied.value = false }, 1800)
  } catch {
    // Older browsers / insecure context: fall back to selecting the input
    const input = document.querySelector<HTMLInputElement>('input[readonly][value="' + props.url + '"]')
    input?.select()
  }
}
</script>
