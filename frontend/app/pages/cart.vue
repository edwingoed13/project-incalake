<script setup lang="ts">
import type { CartItem } from '~/stores/cart'

const { api } = useApi()
const { t, locale } = useI18n()
const cartStore = useCartStore()
const currencyStore = useCurrencyStore()
const router = useRouter()
const config = useRuntimeConfig()
const localePath = useLocalePath()

// Sort cart items by tour date (earliest first)
const sortedCartItems = computed(() =>
  [...cartStore.items].sort((a, b) => a.selectedDate.localeCompare(b.selectedDate))
)

// Single tax rate to show inline next to "Tasas de transacción" — only when all
// items share the same percentage (otherwise the per-item popover covers it).
const uniformTaxPercent = computed<number | null>(() => {
  const pcts = [...new Set(cartStore.items.map((i: any) => Number(i.taxPercentage || 0)).filter((p: number) => p > 0))]
  return pcts.length === 1 ? pcts[0] : null
})

// Tour details cache (for policies, guide info not in cart)
const tourDetails = ref<Record<number, any>>({})

onMounted(async () => {
  cartStore.loadFromLocalStorage()
  // Fetch full tour details for each cart item (for policies)
  for (const item of cartStore.items) {
    if (!tourDetails.value[item.tourId]) {
      try {
        const res = await api(`/tours/${item.tourId}?language=${locale.value.toUpperCase()}`)
        tourDetails.value[item.tourId] = (res as any)?.data || null
      } catch (e) {}
    }
  }
})

const editingItem = ref<string | null>(null)
const editForm = ref({ date: '', time: '', adults: 1, children: 0 })

function openEdit(item: CartItem) {
  editForm.value = {
    date: item.selectedDate,
    time: item.selectedTime,
    adults: item.adults,
    children: item.children || 0,
  }
  editingItem.value = item.id
}

function saveEdit(item: CartItem) {
  // Keep child cost in the total when only adults are edited.
  const newTotal =
    item.basePrice * editForm.value.adults +
    (item.childPrice || 0) * editForm.value.children
  cartStore.updateItem(item.id, {
    selectedDate: editForm.value.date,
    selectedTime: editForm.value.time,
    adults: editForm.value.adults,
    children: editForm.value.children,
    total: newTotal,
  })
  editingItem.value = null
}

function removeItem(itemId: string) {
  cartStore.removeItem(itemId)
}

function getItemPolicies(item: any) {
  const detail = tourDetails.value[item.tourId]
  const bt = detail?.booking_texts || {}
  const policyType = detail?.policy_type || 'standard'

  // Get policy content: custom from booking_texts, or standard from tour fields
  let policyContent = ''
  if (policyType === 'custom') {
    policyContent = bt.policyDescriptionCustom || bt.policyDescription || detail?.policy_description_custom || ''
  } else {
    policyContent = bt.policyDescription || detail?.policy_description || ''
  }

  return {
    policies: item.policies || detail?.policies || policyContent || '',
    cancellationPolicy: item.cancellationPolicy || detail?.cancellation_policy || '',
    policyType,
    tourTitle: item.tourTitle,
  }
}

const acceptedTerms = ref(false)
const policiesItem = ref<any>(null)

function openPolicies(item: any) {
  policiesItem.value = getItemPolicies(item)
}

function closePolicies() {
  policiesItem.value = null
}

function proceedToCheckout() {
  if (!acceptedTerms.value) return
  router.push(localePath('/checkout'))
}

// --- Bulk select + delete --------------------------------------------------
const selectedIds = ref<string[]>([])
const isSelected = (id: string) => selectedIds.value.includes(id)
function toggleSelect(id: string) {
  const i = selectedIds.value.indexOf(id)
  if (i >= 0) selectedIds.value.splice(i, 1)
  else selectedIds.value.push(id)
}
const allSelected = computed(() =>
  cartStore.items.length > 0 && selectedIds.value.length === cartStore.items.length
)
function toggleSelectAll() {
  selectedIds.value = allSelected.value ? [] : cartStore.items.map(i => i.id)
}
function bulkDelete() {
  if (!selectedIds.value.length) return
  if (!confirm(`¿Eliminar ${selectedIds.value.length} ${selectedIds.value.length === 1 ? 'tour' : 'tours'} del carrito?`)) return
  for (const id of [...selectedIds.value]) cartStore.removeItem(id)
  selectedIds.value = []
}
// Drop selected ids if their items are removed individually.
watch(() => cartStore.items.map(i => i.id), (ids) => {
  selectedIds.value = selectedIds.value.filter(id => ids.includes(id))
})

// --- All-tours policies modal (triggered from the resumen "términos" link) ---
const showAllPolicies = ref(false)
const allTourPolicies = computed(() => sortedCartItems.value.map(item => getItemPolicies(item)))

const localeMap: Record<string, string> = {
  es: 'es-PE', en: 'en-US', pt: 'pt-BR', fr: 'fr-FR', de: 'de-DE', it: 'it-IT'
}

const formatDate = (d: string) => {
  if (!d) return ''
  const [y, m, day] = d.split('-').map(Number)
  return new Date(y, m - 1, day).toLocaleDateString(localeMap[locale.value] || 'en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' })
}

const formatTime = (ti: string) => {
  if (!ti) return ''
  const [h, m] = ti.split(':')
  const hour = parseInt(h)
  return `${hour % 12 || 12}:${m} ${hour >= 12 ? 'PM' : 'AM'}`
}

const guideTypeLabels = computed<Record<string, string>>(() => ({
  live_guide: t('guide_live'), audio_guide: t('guide_audio'),
  informative_brochures: t('guide_brochures'), no_guide: t('guide_self'), none: '',
}))

function getImageUrl(path: string) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${config.public.storageBase}/${path}`
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 pt-24 pb-12">
    <div class="max-w-5xl mx-auto px-4">
      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-2xl font-black text-slate-800">{{ t('shopping_cart') }}</h1>
        <p class="text-sm text-slate-500">{{ cartStore.itemCount }} {{ cartStore.itemCount === 1 ? t('tour') : t('tours') }}</p>
      </div>

      <!-- Empty -->
      <div v-if="cartStore.isEmpty" class="bg-white rounded-2xl p-12 text-center shadow-sm">
        <Icon name="material-symbols:shopping-cart-outline" class="text-slate-300 text-6xl mb-4" />
        <h2 class="text-xl font-bold text-slate-800 mb-2">{{ t('your_cart_empty') }}</h2>
        <p class="text-sm text-slate-500 mb-6">{{ t('explore_tours_hint') }}</p>
        <NuxtLink :to="localePath('/tours')" class="bg-primary text-white font-bold px-6 py-3 rounded-xl text-sm">
          {{ t('explore_tours') }}
        </NuxtLink>
      </div>

      <!-- Cart Content -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Items (2 cols) -->
        <div class="lg:col-span-2 space-y-4">
          <!-- Bulk select toolbar (only with 2+ items) -->
          <div v-if="cartStore.itemCount > 1" class="flex items-center justify-between gap-3 bg-white rounded-xl border border-slate-100 px-3 py-2">
            <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-600">
              <input type="checkbox" :checked="allSelected" @change="toggleSelectAll" class="w-4 h-4 accent-primary rounded" />
              {{ allSelected ? 'Deseleccionar todos' : 'Seleccionar todos' }}
            </label>
            <div v-if="selectedIds.length > 0" class="flex items-center gap-2">
              <span class="text-xs font-semibold text-slate-500">{{ selectedIds.length }} {{ selectedIds.length === 1 ? 'seleccionado' : 'seleccionados' }}</span>
              <button @click="bulkDelete" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                <Icon name="material-symbols:delete-outline" class="text-base" /> Eliminar
              </button>
            </div>
          </div>

          <div
            v-for="item in sortedCartItems"
            :key="item.id"
            class="relative bg-white rounded-2xl shadow-sm border overflow-hidden transition-colors"
            :class="isSelected(item.id) ? 'border-primary/40 ring-1 ring-primary/20' : 'border-slate-100'"
          >
            <!-- Per-item selection checkbox -->
            <label v-if="cartStore.itemCount > 1" class="absolute top-2.5 left-2.5 z-10 flex items-center justify-center w-6 h-6 bg-white/90 backdrop-blur rounded-md cursor-pointer">
              <input type="checkbox" :checked="isSelected(item.id)" @change="toggleSelect(item.id)" class="w-4 h-4 accent-primary rounded" :aria-label="`Seleccionar ${item.tourTitle}`" />
            </label>
            <div class="flex gap-4 p-4">
              <!-- Image -->
              <img
                v-if="item.tourImage"
                :src="getImageUrl(item.tourImage)"
                :alt="item.tourTitle"
                class="w-20 h-20 sm:w-28 sm:h-28 object-cover rounded-xl shrink-0"
              />
              <div v-else class="w-20 h-20 sm:w-28 sm:h-28 bg-slate-100 rounded-xl flex items-center justify-center shrink-0">
                <Icon name="material-symbols:image-outline" class="text-slate-300 text-3xl" />
              </div>

              <!-- Details -->
              <div class="flex-1 min-w-0">
                <h3 class="text-sm sm:text-base font-bold text-slate-800 leading-snug mb-2">{{ item.tourTitle }}</h3>

                <!-- Offer badge -->
                <div v-if="item.hasOffer" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold mb-2" :style="{ backgroundColor: (item.offerColor || '#22c55e') + '20', color: item.offerColor || '#22c55e' }">
                  <Icon name="material-symbols:sell-outline" class="text-xs" />
                  {{ item.offerDiscount }}{{ item.offerDiscountType === 'percentage' ? '%' : ' USD' }} OFF
                </div>

                <!-- Info rows -->
                <div class="space-y-1 text-xs text-slate-500">
                  <div class="flex items-center gap-1.5">
                    <Icon name="material-symbols:calendar-today-outline" class="text-sm" />
                    {{ formatDate(item.selectedDate) }}
                  </div>
                  <div class="flex items-center gap-1.5">
                    <Icon name="material-symbols:schedule-outline" class="text-sm" />
                    {{ formatTime(item.selectedTime) }}{{ item.durationLabel ? ` · ${item.durationLabel}` : '' }}
                  </div>
                  <div class="flex items-center gap-1.5">
                    <Icon name="material-symbols:group-outline" class="text-sm" />
                    {{ item.adults }} {{ item.adults === 1 ? t('adult') : t('adults') }}{{ item.children > 0 ? `, ${item.children} ${t('children_label')}` : '' }}
                  </div>
                  <div v-if="item.guideType && item.guideType !== 'none'" class="flex items-center gap-1.5">
                    <Icon name="material-symbols:record-voice-over-outline" class="text-sm" />
                    {{ guideTypeLabels[item.guideType] || item.guideType }}{{ item.guideLanguages?.length ? ` [ ${item.guideLanguages.join(', ')} ]` : '' }}
                  </div>
                  <button
                    @click="openPolicies(item)"
                    class="flex items-center gap-1 text-primary hover:underline font-semibold mt-0.5"
                  >
                    <Icon name="material-symbols:description-outline" class="text-sm" />
                    {{ t('terms_conditions') }}
                  </button>
                </div>

                <!-- Actions + Price -->
                <div class="flex items-end justify-between mt-3 pt-3 border-t border-slate-100">
                  <div class="flex items-center gap-0.5 -ml-1.5">
                    <button @click="openEdit(item)" class="p-1.5 text-slate-400 hover:text-primary hover:bg-primary/5 rounded-lg" :title="t('edit')">
                      <Icon name="material-symbols:edit-outline" class="text-base" />
                    </button>
                    <button @click="removeItem(item.id)" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg" title="Eliminar">
                      <Icon name="material-symbols:delete-outline" class="text-base" />
                    </button>
                  </div>
                  <div class="text-right">
                    <div v-if="item.hasOffer && item.originalPrice" class="text-[11px] leading-none mb-0.5">
                      <span class="line-through text-slate-400">{{ currencyStore.formatConverted(item.originalPrice * item.adults) }}</span>
                      <span class="text-green-600 font-semibold ml-1">-{{ item.offerDiscount }}{{ item.offerDiscountType === 'percentage' ? '%' : ' USD' }}</span>
                    </div>
                    <span class="text-lg font-black text-primary leading-none">{{ currencyStore.formatConverted(item.total) }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Edit Panel -->
            <Transition name="slide">
              <div v-if="editingItem === item.id" class="p-4 bg-slate-50 border-t border-slate-100">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">{{ t('edit_booking') }}</p>
                <div class="space-y-4">
                  <!-- Step 1: number of travelers -->
                  <div>
                    <label class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wide text-slate-600 mb-2">
                      <span class="size-5 rounded-full bg-primary text-white text-[10px] font-black flex items-center justify-center shrink-0">1</span>
                      {{ t('cart_travelers_label') }}
                    </label>
                    <div class="flex items-end gap-5 pl-7">
                      <div>
                        <span class="block text-[10px] font-semibold text-slate-400 mb-1">{{ t('adults') }}</span>
                        <div class="flex items-center gap-2">
                          <button @click="editForm.adults = Math.max(1, editForm.adults - 1)" class="size-8 rounded-lg border border-slate-200 flex items-center justify-center hover:bg-slate-100 bg-white">
                            <Icon name="material-symbols:remove" class="text-sm" />
                          </button>
                          <span class="text-sm font-bold w-6 text-center">{{ editForm.adults }}</span>
                          <button @click="editForm.adults = Math.min(99, editForm.adults + 1)" class="size-8 rounded-lg border border-slate-200 flex items-center justify-center hover:bg-slate-100 bg-white">
                            <Icon name="material-symbols:add" class="text-sm" />
                          </button>
                        </div>
                      </div>
                      <div v-if="item.childPrice > 0 || item.children > 0">
                        <span class="block text-[10px] font-semibold text-slate-400 mb-1">{{ t('children_label') }}</span>
                        <div class="flex items-center gap-2">
                          <button @click="editForm.children = Math.max(0, editForm.children - 1)" class="size-8 rounded-lg border border-slate-200 flex items-center justify-center hover:bg-slate-100 bg-white">
                            <Icon name="material-symbols:remove" class="text-sm" />
                          </button>
                          <span class="text-sm font-bold w-6 text-center">{{ editForm.children }}</span>
                          <button @click="editForm.children = Math.min(99, editForm.children + 1)" class="size-8 rounded-lg border border-slate-200 flex items-center justify-center hover:bg-slate-100 bg-white">
                            <Icon name="material-symbols:add" class="text-sm" />
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Step 2: travel date -->
                  <div>
                    <label class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wide text-slate-600 mb-2">
                      <span class="size-5 rounded-full bg-primary text-white text-[10px] font-black flex items-center justify-center shrink-0">2</span>
                      {{ t('cart_date_label') }}
                    </label>
                    <input v-model="editForm.date" type="date" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30" />
                  </div>

                  <!-- Step 3: time -->
                  <div>
                    <label class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wide text-slate-600 mb-2">
                      <span class="size-5 rounded-full bg-primary text-white text-[10px] font-black flex items-center justify-center shrink-0">3</span>
                      {{ t('cart_time_label') }}
                    </label>
                    <input v-model="editForm.time" type="time" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30" />
                  </div>
                </div>
                <div class="flex gap-2 mt-3">
                  <button @click="editingItem = null" class="flex-1 py-2 text-xs font-semibold text-slate-500 bg-white border border-slate-200 rounded-lg">{{ t('cancel') }}</button>
                  <button @click="saveEdit(item)" class="flex-1 py-2 text-xs font-bold text-white bg-primary rounded-lg">{{ t('save_changes') }}</button>
                </div>
              </div>
            </Transition>
          </div>

          <!-- Continue Shopping -->
          <NuxtLink :to="localePath('/tours')" class="flex items-center gap-2 text-sm font-semibold text-primary hover:underline">
            <Icon name="material-symbols:arrow-back" class="text-base" />
            {{ t('continue_shopping') }}
          </NuxtLink>
        </div>

        <!-- Summary Sidebar -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 sticky top-24">
            <h2 class="text-base font-black mb-4">{{ t('summary') }}</h2>

            <!-- Per-tour mini list with offer badge -->
            <div class="space-y-2.5 mb-4 pb-4 border-b border-slate-100">
              <div v-for="item in sortedCartItems" :key="'r-'+item.id" class="flex items-start gap-2">
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-bold text-slate-700 truncate leading-snug">{{ item.tourTitle }}</p>
                  <span v-if="item.hasOffer" class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold mt-1" :style="{ backgroundColor: (item.offerColor || '#22c55e') + '20', color: item.offerColor || '#22c55e' }">
                    <Icon name="material-symbols:sell-outline" class="text-[10px]" />
                    {{ item.offerDiscount }}{{ item.offerDiscountType === 'percentage' ? '%' : ' USD' }} OFF
                  </span>
                </div>
                <span class="text-xs font-semibold text-slate-700 shrink-0 tabular-nums">{{ currencyStore.formatConverted(item.total) }}</span>
              </div>
            </div>

            <div class="space-y-2 mb-4 pb-4 border-b border-slate-100">
              <div class="flex justify-between text-xs">
                <span class="text-slate-500">{{ t('tours') }} ({{ cartStore.itemCount }})</span>
                <span class="font-semibold">{{ currencyStore.formatConverted(cartStore.subtotal) }}</span>
              </div>
              <div v-if="cartStore.totalTax > 0" class="flex justify-between text-xs">
                <span class="text-slate-500 flex items-center gap-1 flex-wrap">
                  {{ t('transaction_fees') }}
                  <AppPopover :label="t('transaction_fees')" width="w-72">
                    <p class="leading-snug mb-1.5">{{ t('transaction_fees_info') }}</p>
                    <div class="pt-1.5 mt-1.5 border-t border-white/15">
                      <p class="text-[9px] font-bold uppercase tracking-wider text-white/60 mb-1">{{ t('transaction_fees') }}</p>
                      <div v-for="ti in sortedCartItems" :key="'tax-'+ti.id" class="flex justify-between py-0.5 gap-2">
                        <span class="flex-1 break-words">{{ ti.tourTitle }}</span>
                        <span class="shrink-0 font-semibold">{{ ti.taxPercentage || 0 }}%</span>
                      </div>
                    </div>
                  </AppPopover>
                  <!-- Percentage shown prominently inline (uniform rate across items). -->
                  <span v-if="uniformTaxPercent !== null" class="font-bold text-slate-700 tabular-nums">({{ uniformTaxPercent.toFixed(2) }}%)</span>
                </span>
                <span class="font-semibold">{{ currencyStore.formatConverted(cartStore.totalTax) }}</span>
              </div>
            </div>

            <div class="flex justify-between items-center mb-3">
              <span class="font-black">{{ t('total') }}</span>
              <span class="text-2xl font-black text-primary">{{ currencyStore.formatConverted(cartStore.totalAmount) }}</span>
            </div>

            <div v-if="currencyStore.isForeignCurrency" class="mb-4 flex items-start gap-1.5 p-2 bg-amber-50 border border-amber-200 rounded-lg">
              <Icon name="material-symbols:info-outline" class="text-amber-600 text-sm mt-0.5" />
              <span class="text-[11px] text-amber-800 leading-tight">{{ t('payment_usd_notice') }}</span>
            </div>

            <!-- Terms -->
            <label class="flex items-start gap-2 cursor-pointer mb-4 p-3 bg-slate-50 rounded-xl">
              <input v-model="acceptedTerms" type="checkbox" class="mt-0.5 w-4 h-4 accent-primary rounded" />
              <span class="text-[11px] text-slate-600">{{ t('terms_accept') }}
                <button type="button" @click.stop.prevent="showAllPolicies = true" class="text-primary font-semibold hover:underline">{{ t('terms_link') }}</button>
                {{ t('terms_policies') }}
              </span>
            </label>

            <button
              @click="proceedToCheckout"
              :disabled="!acceptedTerms"
              class="w-full py-3.5 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2"
              :class="acceptedTerms ? 'bg-primary text-white shadow-lg shadow-primary/20 hover:brightness-110' : 'bg-slate-200 text-slate-400 cursor-not-allowed'"
            >
              <Icon name="material-symbols:lock-outline" class="text-lg" />
              {{ t('proceed_checkout') }}
            </button>

            <!-- Trust -->
            <div class="mt-4 pt-4 border-t border-slate-100 space-y-1.5">
              <div class="flex items-center gap-2 text-[10px] text-slate-400">
                <Icon name="material-symbols:shield-outline" class="text-green-500 text-sm" /> {{ t('secure_payment') }}
              </div>
              <div class="flex items-center gap-2 text-[10px] text-slate-400">
                <Icon name="material-symbols:bolt-outline" class="text-yellow-500 text-sm" /> {{ t('instant_confirmation') }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Policies Modal -->
    <AppModal
      :model-value="!!policiesItem"
      @update:model-value="(v) => { if (!v) closePolicies() }"
      :title="t('terms_conditions')"
    >
      <div v-if="policiesItem" class="space-y-4">
        <p class="text-xs font-bold text-slate-400 uppercase">{{ policiesItem.tourTitle }}</p>

        <!-- Custom policies from admin -->
        <div v-if="policiesItem.policies">
          <h4 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-1.5 mb-2">
            <Icon name="material-symbols:policy-outline" class="text-blue-500 text-base" />
            {{ t('tour_policies') }}
          </h4>
          <div class="text-xs text-slate-600 dark:text-slate-300 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-900/40 rounded-xl p-3 prose prose-sm max-w-none" v-html="policiesItem.policies"></div>
        </div>

        <div v-if="policiesItem.cancellationPolicy">
          <h4 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-1.5 mb-2">
            <Icon name="material-symbols:cancel-outline" class="text-red-500 text-base" />
            {{ t('cancellation_policy') }}
          </h4>
          <div class="text-xs text-slate-600 dark:text-slate-300 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/40 rounded-xl p-3 prose prose-sm max-w-none" v-html="policiesItem.cancellationPolicy"></div>
        </div>

        <!-- Standard policy (default when nothing configured) -->
        <div v-if="!policiesItem.policies && !policiesItem.cancellationPolicy && policiesItem.policyType !== 'custom'">
          <h4 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-1.5 mb-2">
            <Icon name="material-symbols:check-circle-outline" class="text-green-500 text-base" />
            {{ t('standard_policy') }}
          </h4>
          <div class="text-xs text-slate-600 dark:text-slate-300 bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-900/40 rounded-xl p-3 space-y-2">
            <p><strong>Free cancellation</strong> up to 24 hours before the tour start time for a full refund.</p>
            <p><strong>No changes</strong> are accepted within 24 hours of the tour.</p>
            <p><strong>No-shows</strong> will be charged in full.</p>
            <p>All tours are subject to weather conditions and minimum participant requirements.</p>
          </div>
        </div>

        <!-- Custom type selected but no content yet -->
        <div v-if="!policiesItem.policies && !policiesItem.cancellationPolicy && policiesItem.policyType === 'custom'">
          <h4 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-1.5 mb-2">
            <Icon name="material-symbols:info-outline" class="text-amber-500 text-base" />
            {{ t('custom_policy') }}
          </h4>
          <div class="text-xs text-slate-600 dark:text-slate-300 bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-900/40 rounded-xl p-3">
            <p>This tour has custom policies. Please contact us at <strong>reservas@incalake.com</strong> for specific terms and conditions.</p>
          </div>
        </div>
      </div>
    </AppModal>

    <!-- All-tours policies modal (triggered from the Resumen "términos" link) -->
    <AppModal v-model="showAllPolicies" :title="t('terms_conditions')" max-width="max-w-2xl">
      <div class="space-y-6">
        <div v-for="(p, idx) in allTourPolicies" :key="idx" class="space-y-3 pb-4" :class="idx < allTourPolicies.length - 1 ? 'border-b border-slate-100' : ''">
          <p class="text-xs font-bold text-primary uppercase tracking-wider">{{ p.tourTitle }}</p>

          <div v-if="p.policies">
            <h4 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-1.5 mb-2">
              <Icon name="material-symbols:policy-outline" class="text-blue-500 text-base" />
              {{ t('tour_policies') }}
            </h4>
            <div class="text-xs text-slate-600 dark:text-slate-300 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-900/40 rounded-xl p-3 prose prose-sm max-w-none" v-html="p.policies"></div>
          </div>

          <div v-if="p.cancellationPolicy">
            <h4 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-1.5 mb-2">
              <Icon name="material-symbols:cancel-outline" class="text-red-500 text-base" />
              {{ t('cancellation_policy') }}
            </h4>
            <div class="text-xs text-slate-600 dark:text-slate-300 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/40 rounded-xl p-3 prose prose-sm max-w-none" v-html="p.cancellationPolicy"></div>
          </div>

          <div v-if="!p.policies && !p.cancellationPolicy && p.policyType !== 'custom'">
            <h4 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-1.5 mb-2">
              <Icon name="material-symbols:check-circle-outline" class="text-green-500 text-base" />
              {{ t('standard_policy') }}
            </h4>
            <div class="text-xs text-slate-600 dark:text-slate-300 bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-900/40 rounded-xl p-3 space-y-2">
              <p><strong>Cancelación gratuita</strong> hasta 24 horas antes del inicio del tour, con reembolso completo.</p>
              <p><strong>No se aceptan cambios</strong> dentro de las 24 horas previas al tour.</p>
              <p><strong>No-shows</strong> se cobran completos.</p>
              <p>Todos los tours están sujetos a condiciones climáticas y mínimo de participantes.</p>
            </div>
          </div>
        </div>
      </div>
    </AppModal>
  </div>
</template>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: all 0.2s ease; }
.slide-enter-from, .slide-leave-to { opacity: 0; max-height: 0; }
.slide-enter-to, .slide-leave-from { max-height: 300px; }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>
