<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { NATIONALITIES, countryFlagUrl, findNationality } from '~/utils/countries'

const props = withDefaults(defineProps<{
  modelValue?: string
  placeholder?: string
}>(), {
  modelValue: '',
  placeholder: 'Selecciona país',
})

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const open = ref(false)
const search = ref('')
const root = ref<HTMLElement | null>(null)
const searchInput = ref<HTMLInputElement | null>(null)

const selected = computed(() => findNationality(props.modelValue))

const filtered = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return NATIONALITIES
  return NATIONALITIES.filter(n => n.name.toLowerCase().includes(q) || n.code.toLowerCase().includes(q))
})

async function toggle() {
  open.value = !open.value
  if (open.value) {
    search.value = ''
    await nextTick()
    searchInput.value?.focus()
  }
}

function choose(name: string) {
  emit('update:modelValue', name)
  open.value = false
}

function onClickOutside(e: MouseEvent) {
  if (open.value && root.value && !root.value.contains(e.target as Node)) open.value = false
}
function onKey(e: KeyboardEvent) {
  if (e.key === 'Escape') open.value = false
}

onMounted(() => {
  document.addEventListener('click', onClickOutside)
  document.addEventListener('keydown', onKey)
})
onBeforeUnmount(() => {
  document.removeEventListener('click', onClickOutside)
  document.removeEventListener('keydown', onKey)
})
</script>

<template>
  <div ref="root" class="relative">
    <!-- Trigger -->
    <button
      type="button"
      @click="toggle"
      class="w-full flex items-center gap-2 px-3 py-2.5 rounded-lg border border-slate-200 bg-white text-sm text-left focus:ring-2 focus:ring-primary/20 focus:border-primary"
      :class="open ? 'ring-2 ring-primary/20 border-primary' : ''"
    >
      <img v-if="selected" :src="countryFlagUrl(selected.code, 24)" :alt="selected.name" class="w-5 h-auto rounded-sm shrink-0" />
      <span class="flex-1 truncate" :class="selected ? 'text-slate-800' : 'text-slate-400'">
        {{ selected ? selected.name : (modelValue || placeholder) }}
      </span>
      <Icon name="material-symbols:expand-more" :class="open ? 'rotate-180' : ''" class="text-slate-400 text-lg transition-transform" />
    </button>

    <!-- Dropdown -->
    <div
      v-if="open"
      class="absolute z-30 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden"
    >
      <div class="p-2 border-b border-slate-100">
        <input
          ref="searchInput"
          v-model="search"
          type="text"
          placeholder="Buscar país..."
          class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
        />
      </div>
      <ul class="max-h-56 overflow-y-auto py-1">
        <li v-for="n in filtered" :key="n.code">
          <button
            type="button"
            @click="choose(n.name)"
            class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-left hover:bg-slate-50 active:bg-slate-100"
            :class="selected?.code === n.code ? 'bg-primary/5 font-semibold text-primary' : 'text-slate-700'"
          >
            <img :src="countryFlagUrl(n.code, 24)" :alt="n.name" class="w-5 h-auto rounded-sm shrink-0" loading="lazy" />
            <span class="flex-1 truncate">{{ n.name }}</span>
            <Icon name="material-symbols:check" v-if="selected?.code === n.code" class="text-primary text-base" />
          </button>
        </li>
        <li v-if="filtered.length === 0" class="px-3 py-3 text-sm text-slate-400 text-center">Sin resultados</li>
      </ul>
    </div>
  </div>
</template>
