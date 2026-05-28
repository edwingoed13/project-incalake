<script setup lang="ts">
import { computed } from 'vue'

// Controlled travelers editor for ONE booking. The parent owns the array
// (load + save); this only renders the fields, capped at maxTravelers.
const props = withDefaults(defineProps<{
  modelValue?: any[]
  maxTravelers: number
  customerName?: string
}>(), { maxTravelers: 1, modelValue: () => [], customerName: '' })

const emit = defineEmits<{ 'update:modelValue': [v: any[]] }>()
const { t } = useI18n()

const canAdd = computed(() => props.modelValue.length < props.maxTravelers)

function add() {
  if (!canAdd.value) return
  emit('update:modelValue', [...props.modelValue, {
    full_name: '', nationality: '', doc_type: 'passport', doc_number: '',
    age_group: 'adult', special_needs: '', is_leader: false,
  }])
}

function remove(idx: number) {
  if (props.modelValue.length > 1 && !props.modelValue[idx].is_leader) {
    const next = props.modelValue.slice()
    next.splice(idx, 1)
    emit('update:modelValue', next)
  }
}
</script>

<template>
  <div class="space-y-3">
    <div v-for="(traveler, idx) in modelValue" :key="idx" class="p-3 bg-slate-50 rounded-xl">
      <div class="flex items-center justify-between gap-2 mb-2.5">
        <p class="text-[11px] font-bold uppercase" :class="traveler.is_leader ? 'text-primary' : 'text-slate-400'">
          {{ traveler.is_leader ? t('leader') : `Viajero ${idx + 1}` }}
          <span v-if="traveler.age_group === 'child'" class="ml-1 text-amber-500">(Niño)</span>
        </p>
        <button
          v-if="traveler.is_leader && customerName"
          type="button"
          @click="traveler.full_name = customerName"
          class="shrink-0 inline-flex items-center gap-1 text-[11px] font-semibold text-primary active:text-primary/70"
        >
          <Icon name="material-symbols:badge-outline" class="text-sm" />
          Usar mis datos
        </button>
        <button
          v-else-if="!traveler.is_leader && modelValue.length > 1"
          type="button"
          @click="remove(idx)"
          class="p-1 text-slate-400 active:text-red-500"
        >
          <Icon name="material-symbols:close" class="text-sm" />
        </button>
      </div>

      <div class="space-y-2 md:grid md:grid-cols-2 md:gap-3 md:space-y-0">
        <div class="md:col-span-2">
          <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1 block">{{ t('full_name') }} *</label>
          <input v-model="traveler.full_name" type="text" autocomplete="name" autocapitalize="words" placeholder="Nombre completo" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
        </div>
        <div>
          <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1 block">{{ t('nationality') }}</label>
          <AppCountrySelect v-model="traveler.nationality" placeholder="Selecciona país" />
        </div>
        <div>
          <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1 block">{{ t('doc_type') }}</label>
          <select v-model="traveler.doc_type" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 text-sm bg-white">
            <option value="passport">Pasaporte</option>
            <option value="dni">DNI</option>
            <option value="ce">Carné de extranjería</option>
            <option value="cedula">Cédula de ciudadanía</option>
            <option value="run">RUN</option>
            <option value="rut">RUT</option>
            <option value="other">Otro</option>
          </select>
        </div>
        <div>
          <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1 block">{{ t('doc_number') }}</label>
          <input v-model="traveler.doc_number" type="text" autocomplete="off" autocapitalize="characters" placeholder="N° de documento" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 text-sm uppercase placeholder:normal-case" />
        </div>
        <div>
          <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1 block">{{ t('special_needs') }}</label>
          <input v-model="traveler.special_needs" type="text" placeholder="Opcional" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 text-sm" />
        </div>
      </div>
    </div>

    <button
      v-if="canAdd"
      type="button"
      @click="add"
      class="w-full flex items-center justify-center gap-1 py-2 border border-dashed border-primary/40 text-primary text-xs font-bold rounded-lg active:bg-primary/5"
    >
      <Icon name="material-symbols:add" class="text-sm" />
      {{ t('add_traveler') }}
    </button>
  </div>
</template>
