<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { TRAVELER_FIELD_DEFS as FIELD_DEFS } from '~/utils/travelerFields'

// Controlled travelers editor for ONE booking. The parent owns the array
// (load + save); this only renders the fields, capped at maxTravelers.
//
// Which fields show is driven by the tour's admin config (requiredFields):
// full_name is always collected; `nationality` maps to its own column; every
// other configured field lives in `traveler.extra_data[key]`. Extras render on
// the lead traveler always, and on the rest only when applyToAllPax is true.
const props = withDefaults(defineProps<{
  modelValue?: any[]
  maxTravelers: number
  customerName?: string
  customerEmail?: string
  customerPhone?: string
  requiredFields?: string[]
  applyToAllPax?: boolean
  showErrors?: boolean
}>(), {
  maxTravelers: 1,
  modelValue: () => [],
  customerName: '',
  customerEmail: '',
  customerPhone: '',
  requiredFields: () => [],
  applyToAllPax: false,
  showErrors: false,
})

const emit = defineEmits<{ 'update:modelValue': [v: any[]] }>()
const { t, te } = useI18n()

// The renderable fields the admin asked for (unknown keys / name parts dropped).
const fields = computed(() => (props.requiredFields || []).filter(k => FIELD_DEFS[k]))

// Field labels come from i18n (traveler_field_*); the Spanish label in
// FIELD_DEFS stays as fallback for keys without a translation yet.
const fieldLabel = (key: string) =>
  te(`traveler_field_${key}`) ? t(`traveler_field_${key}`) : FIELD_DEFS[key]?.label || key

const canAdd = computed(() => props.modelValue.length < props.maxTravelers)
const showExtras = (traveler: any) => !!traveler?.is_leader || props.applyToAllPax

// Inline validation: required fields are enforced on the lead traveler only.
// `showErrors` is flipped on by the parent when a submit attempt fails.
function fieldEmpty(traveler: any, key: string): boolean {
  const v = key === 'nationality' ? traveler?.nationality : traveler?.extra_data?.[key]
  return !String(v ?? '').trim()
}
const fieldInvalid = (traveler: any, key: string) =>
  props.showErrors && !!traveler?.is_leader && fieldEmpty(traveler, key)
const nameInvalid = (traveler: any) =>
  props.showErrors && !!traveler?.is_leader && !String(traveler?.full_name ?? '').trim()

// Guarantee every traveler has an extra_data object so v-model on its keys is
// reactive, and prefill the lead traveler's email/phone from the purchase.
function ensureShape() {
  for (const tr of props.modelValue) {
    if (!tr.extra_data || typeof tr.extra_data !== 'object') tr.extra_data = {}
    if (tr.is_leader) {
      if (fields.value.includes('email') && !tr.extra_data.email && props.customerEmail) tr.extra_data.email = props.customerEmail
      if (fields.value.includes('phone_whatsapp') && !tr.extra_data.phone_whatsapp && props.customerPhone) tr.extra_data.phone_whatsapp = props.customerPhone
    }
  }
}
onMounted(ensureShape)
watch(() => props.modelValue, ensureShape, { deep: false })

function add() {
  if (!canAdd.value) return
  emit('update:modelValue', [...props.modelValue, {
    full_name: '', nationality: '', doc_type: 'passport', doc_number: '',
    age_group: 'adult', special_needs: '', extra_data: {}, is_leader: false,
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
          {{ traveler.is_leader ? t('leader') : `${t('traveler_word')} ${idx + 1}` }}
          <span v-if="traveler.age_group === 'child'" class="ml-1 text-amber-500">({{ t('child_word') }})</span>
        </p>
        <button
          v-if="traveler.is_leader && customerName"
          type="button"
          @click="traveler.full_name = customerName"
          class="shrink-0 inline-flex items-center gap-1 text-[11px] font-semibold text-primary active:text-primary/70"
        >
          <Icon name="material-symbols:badge-outline" class="text-sm" />
          {{ t('use_my_data') }}
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
        <!-- Full name: always collected; required for the lead traveler -->
        <div class="md:col-span-2">
          <label class="form-label">
            {{ t('full_name') }}<span v-if="traveler.is_leader"> *</span>
          </label>
          <input v-model="traveler.full_name" type="text" autocomplete="name" autocapitalize="words" :placeholder="t('traveler_full_name')"
            class="w-full px-3 py-2.5 rounded-lg border text-base sm:text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
            :class="nameInvalid(traveler) ? 'border-red-400 ring-1 ring-red-200' : 'border-slate-200'" />
          <p v-if="nameInvalid(traveler)" class="text-[10px] text-red-500 mt-1">{{ t('required_label') }}</p>
        </div>

        <!-- Admin-configured fields -->
        <template v-if="showExtras(traveler)">
          <div v-for="key in fields" :key="key" :class="FIELD_DEFS[key].type === 'country' ? 'md:col-span-2' : ''">
            <label class="form-label">
              {{ fieldLabel(key) }}<span v-if="traveler.is_leader"> *</span>
            </label>

            <div v-if="FIELD_DEFS[key].type === 'country'" class="rounded-lg" :class="fieldInvalid(traveler, key) ? 'ring-1 ring-red-300' : ''">
              <AppCountrySelect
                v-model="traveler.nationality"
                :placeholder="t('select_country')"
              />
            </div>

            <select
              v-else-if="FIELD_DEFS[key].type === 'gender'"
              v-model="traveler.extra_data[key]"
              class="w-full px-3 py-2.5 rounded-lg border text-base sm:text-sm bg-white"
              :class="fieldInvalid(traveler, key) ? 'border-red-400 ring-1 ring-red-200' : 'border-slate-200'"
            >
              <option value="">{{ t('select_option') }}</option>
              <option value="male">{{ t('gender_male') }}</option>
              <option value="female">{{ t('gender_female') }}</option>
              <option value="other">{{ t('gender_other') }}</option>
              <option value="undisclosed">{{ t('gender_undisclosed') }}</option>
            </select>

            <input
              v-else
              v-model="traveler.extra_data[key]"
              :type="FIELD_DEFS[key].type === 'number' ? 'number' : FIELD_DEFS[key].type === 'date' ? 'date' : FIELD_DEFS[key].type === 'email' ? 'email' : FIELD_DEFS[key].type === 'tel' ? 'tel' : 'text'"
              :inputmode="FIELD_DEFS[key].type === 'number' ? 'decimal' : undefined"
              :autocomplete="FIELD_DEFS[key].type === 'email' ? 'email' : FIELD_DEFS[key].type === 'tel' ? 'tel' : 'off'"
              :placeholder="fieldLabel(key)"
              class="w-full px-3 py-2.5 rounded-lg border text-base sm:text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
              :class="fieldInvalid(traveler, key) ? 'border-red-400 ring-1 ring-red-200' : 'border-slate-200'"
            />
            <p v-if="fieldInvalid(traveler, key)" class="text-[10px] text-red-500 mt-1">{{ t('required_label') }}</p>
          </div>
        </template>

        <!-- Optional special-requests note. Always shown (not admin-config
             driven): the customer can leave dietary/access/etc. requests so
             the operator can prepare. Saved into extra_data.special_requests. -->
        <div class="md:col-span-2">
          <label class="form-label">
            Pedidos especiales <span class="font-normal normal-case text-slate-400">(opcional)</span>
          </label>
          <textarea
            v-model="traveler.extra_data.special_requests"
            rows="2"
            placeholder="Ej: vegetariano, alérgico a frutos secos, silla extra…"
            class="w-full px-3 py-2.5 rounded-lg border border-slate-200 text-base sm:text-sm resize-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
          ></textarea>
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
