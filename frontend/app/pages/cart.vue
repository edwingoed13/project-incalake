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
const editForm = ref({ date: '', time: '', adults: 1 })

function openEdit(item: CartItem) {
  editForm.value = { date: item.selectedDate, time: item.selectedTime, adults: item.adults }
  editingItem.value = item.id
}

function saveEdit(item: CartItem) {
  const newTotal = item.basePrice * editForm.value.adults
  cartStore.updateItem(item.id, {
    selectedDate: editForm.value.date,
    selectedTime: editForm.value.time,
    adults: editForm.value.adults,
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

const formatDate = (d: string) => {
  if (!d) return ''
  const [y, m, day] = d.split('-').map(Number)
  return new Date(y, m - 1, day).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' })
}

const formatTime = (t: string) => {
  if (!t) return ''
  const [h, m] = t.split(':')
  const hour = parseInt(h)
  return `${hour % 12 || 12}:${m} ${hour >= 12 ? 'PM' : 'AM'}`
}

const guideTypeLabels: Record<string, string> = {
  live_guide: 'Live Tour Guide', audio_guide: 'Audio Guide',
  informative_brochures: 'Brochures', no_guide: 'Self-guided', none: '',
}

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
        <span class="material-symbols-outlined text-slate-300 text-6xl mb-4">shopping_cart</span>
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
          <div
            v-for="item in sortedCartItems"
            :key="item.id"
            class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden"
          >
            <div class="flex gap-4 p-4">
              <!-- Image -->
              <img
                v-if="item.tourImage"
                :src="getImageUrl(item.tourImage)"
                :alt="item.tourTitle"
                class="w-28 h-28 object-cover rounded-xl shrink-0"
              />
              <div v-else class="w-28 h-28 bg-slate-100 rounded-xl flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-slate-300 text-3xl">image</span>
              </div>

              <!-- Details -->
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2 mb-2">
                  <h3 class="text-sm font-bold text-slate-800 line-clamp-2">{{ item.tourTitle }}</h3>
                  <div class="flex items-center gap-0.5 shrink-0">
                    <button @click="openEdit(item)" class="p-1.5 text-slate-400 hover:text-primary hover:bg-primary/5 rounded-lg" title="Edit">
                      <span class="material-symbols-outlined text-base">edit</span>
                    </button>
                    <button @click="removeItem(item.id)" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg" title="Remove">
                      <span class="material-symbols-outlined text-base">delete</span>
                    </button>
                  </div>
                </div>

                <!-- Offer badge -->
                <div v-if="item.hasOffer" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold mb-2" :style="{ backgroundColor: (item.offerColor || '#22c55e') + '20', color: item.offerColor || '#22c55e' }">
                  <span class="material-symbols-outlined text-xs">sell</span>
                  {{ item.offerDiscount }}{{ item.offerDiscountType === 'percentage' ? '%' : ' USD' }} OFF
                </div>

                <!-- Info rows -->
                <div class="space-y-1 text-xs text-slate-500">
                  <div class="flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">calendar_today</span>
                    {{ formatDate(item.selectedDate) }}
                  </div>
                  <div class="flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">schedule</span>
                    {{ formatTime(item.selectedTime) }}{{ item.durationLabel ? ` · ${item.durationLabel}` : '' }}
                  </div>
                  <div class="flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">group</span>
                    {{ item.adults }} adult{{ item.adults !== 1 ? 's' : '' }}{{ item.children > 0 ? `, ${item.children} children` : '' }}
                  </div>
                  <div v-if="item.guideType && item.guideType !== 'none'" class="flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">record_voice_over</span>
                    {{ guideTypeLabels[item.guideType] || item.guideType }}{{ item.guideLanguages?.length ? ` [ ${item.guideLanguages.join(', ')} ]` : '' }}
                  </div>
                  <button
                    @click="openPolicies(item)"
                    class="flex items-center gap-1 text-primary hover:underline font-semibold mt-0.5"
                  >
                    <span class="material-symbols-outlined text-sm">description</span>
                    {{ t('terms_conditions') }}
                  </button>
                </div>

                <!-- Price -->
                <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100">
                  <div v-if="item.hasOffer && item.originalPrice" class="text-xs">
                    <span class="line-through text-slate-400">{{ currencyStore.formatConverted(item.originalPrice * item.adults) }}</span>
                    <span class="text-green-600 font-semibold ml-1">-{{ item.offerDiscount }}{{ item.offerDiscountType === 'percentage' ? '%' : ' USD' }}</span>
                  </div>
                  <div v-else></div>
                  <span class="text-lg font-black text-primary">{{ currencyStore.formatConverted(item.total) }}</span>
                </div>
              </div>
            </div>

            <!-- Edit Panel -->
            <Transition name="slide">
              <div v-if="editingItem === item.id" class="p-4 bg-slate-50 border-t border-slate-100">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">{{ t('edit_booking') }}</p>
                <div class="grid grid-cols-3 gap-3">
                  <div>
                    <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">{{ t('date') }}</label>
                    <input v-model="editForm.date" type="date" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm" />
                  </div>
                  <div>
                    <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">{{ t('time') }}</label>
                    <input v-model="editForm.time" type="time" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm" />
                  </div>
                  <div>
                    <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">{{ t('adults') }}</label>
                    <div class="flex items-center gap-2">
                      <button @click="editForm.adults = Math.max(1, editForm.adults - 1)" class="size-8 rounded-lg border border-slate-200 flex items-center justify-center hover:bg-slate-100 bg-white">
                        <span class="material-symbols-outlined text-sm">remove</span>
                      </button>
                      <span class="text-sm font-bold w-6 text-center">{{ editForm.adults }}</span>
                      <button @click="editForm.adults = Math.min(20, editForm.adults + 1)" class="size-8 rounded-lg border border-slate-200 flex items-center justify-center hover:bg-slate-100 bg-white">
                        <span class="material-symbols-outlined text-sm">add</span>
                      </button>
                    </div>
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
            <span class="material-symbols-outlined text-base">arrow_back</span>
            {{ t('continue_shopping') }}
          </NuxtLink>
        </div>

        <!-- Summary Sidebar -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 sticky top-24">
            <h2 class="text-base font-black mb-4">{{ t('summary') }}</h2>

            <div class="space-y-2 mb-4 pb-4 border-b border-slate-100">
              <div class="flex justify-between text-xs">
                <span class="text-slate-500">{{ t('tours') }} ({{ cartStore.itemCount }})</span>
                <span class="font-semibold">{{ currencyStore.formatConverted(cartStore.subtotal) }}</span>
              </div>
              <div v-if="cartStore.totalTax > 0" class="flex justify-between text-xs">
                <span class="text-slate-500">{{ t('transaction_fees') }}</span>
                <span class="font-semibold">{{ currencyStore.formatConverted(cartStore.totalTax) }}</span>
              </div>
            </div>

            <div class="flex justify-between items-center mb-3">
              <span class="font-black">{{ t('total') }}</span>
              <span class="text-2xl font-black text-primary">{{ currencyStore.formatConverted(cartStore.totalAmount) }}</span>
            </div>

            <div v-if="currencyStore.isForeignCurrency" class="mb-4 flex items-start gap-1.5 p-2 bg-amber-50 border border-amber-200 rounded-lg">
              <span class="material-symbols-outlined text-amber-600 text-sm mt-0.5">info</span>
              <span class="text-[11px] text-amber-800 leading-tight">{{ t('payment_usd_notice') }}</span>
            </div>

            <!-- Terms -->
            <label class="flex items-start gap-2 cursor-pointer mb-4 p-3 bg-slate-50 rounded-xl">
              <input v-model="acceptedTerms" type="checkbox" class="mt-0.5 w-4 h-4 text-primary rounded" />
              <span class="text-[11px] text-slate-600">{{ t('terms_accept') }} <a href="#" class="text-primary font-semibold">{{ t('terms_link') }}</a> {{ t('terms_policies') }}</span>
            </label>

            <button
              @click="proceedToCheckout"
              :disabled="!acceptedTerms"
              class="w-full py-3.5 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2"
              :class="acceptedTerms ? 'bg-primary text-white shadow-lg shadow-primary/20 hover:brightness-110' : 'bg-slate-200 text-slate-400 cursor-not-allowed'"
            >
              <span class="material-symbols-outlined text-lg">lock</span>
              {{ t('proceed_checkout') }}
            </button>

            <!-- Trust -->
            <div class="mt-4 pt-4 border-t border-slate-100 space-y-1.5">
              <div class="flex items-center gap-2 text-[10px] text-slate-400">
                <span class="material-symbols-outlined text-green-500 text-sm">shield</span> {{ t('secure_payment') }}
              </div>
              <div class="flex items-center gap-2 text-[10px] text-slate-400">
                <span class="material-symbols-outlined text-yellow-500 text-sm">bolt</span> {{ t('instant_confirmation') }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Policies Modal -->
    <Teleport to="body">
      <div v-if="policiesItem" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closePolicies">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[80vh] overflow-hidden">
          <div class="flex items-center justify-between p-5 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-800">{{ t('terms_conditions') }}</h3>
            <button @click="closePolicies" class="p-1 hover:bg-slate-100 rounded-lg">
              <span class="material-symbols-outlined text-slate-400">close</span>
            </button>
          </div>
          <div class="p-5 overflow-y-auto max-h-[calc(80vh-120px)] space-y-4">
            <p class="text-xs font-bold text-slate-400 uppercase">{{ policiesItem.tourTitle }}</p>

            <!-- Custom policies from admin -->
            <div v-if="policiesItem.policies">
              <h4 class="text-sm font-bold text-slate-800 flex items-center gap-1.5 mb-2">
                <span class="material-symbols-outlined text-blue-500 text-base">policy</span>
                {{ t('tour_policies') }}
              </h4>
              <div class="text-xs text-slate-600 bg-blue-50 border border-blue-100 rounded-xl p-3 prose prose-sm max-w-none" v-html="policiesItem.policies"></div>
            </div>

            <div v-if="policiesItem.cancellationPolicy">
              <h4 class="text-sm font-bold text-slate-800 flex items-center gap-1.5 mb-2">
                <span class="material-symbols-outlined text-red-500 text-base">cancel</span>
                {{ t('cancellation_policy') }}
              </h4>
              <div class="text-xs text-slate-600 bg-red-50 border border-red-100 rounded-xl p-3 prose prose-sm max-w-none" v-html="policiesItem.cancellationPolicy"></div>
            </div>

            <!-- Standard policy (default when nothing configured) -->
            <div v-if="!policiesItem.policies && !policiesItem.cancellationPolicy && policiesItem.policyType !== 'custom'">
              <h4 class="text-sm font-bold text-slate-800 flex items-center gap-1.5 mb-2">
                <span class="material-symbols-outlined text-green-500 text-base">check_circle</span>
                {{ t('standard_policy') }}
              </h4>
              <div class="text-xs text-slate-600 bg-green-50 border border-green-100 rounded-xl p-3 space-y-2">
                <p><strong>Free cancellation</strong> up to 24 hours before the tour start time for a full refund.</p>
                <p><strong>No changes</strong> are accepted within 24 hours of the tour.</p>
                <p><strong>No-shows</strong> will be charged in full.</p>
                <p>All tours are subject to weather conditions and minimum participant requirements.</p>
              </div>
            </div>

            <!-- Custom type selected but no content yet -->
            <div v-if="!policiesItem.policies && !policiesItem.cancellationPolicy && policiesItem.policyType === 'custom'">
              <h4 class="text-sm font-bold text-slate-800 flex items-center gap-1.5 mb-2">
                <span class="material-symbols-outlined text-amber-500 text-base">info</span>
                {{ t('custom_policy') }}
              </h4>
              <div class="text-xs text-slate-600 bg-amber-50 border border-amber-100 rounded-xl p-3">
                <p>This tour has custom policies. Please contact us at <strong>reservas@incalake.com</strong> for specific terms and conditions.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: all 0.2s ease; }
.slide-enter-from, .slide-leave-to { opacity: 0; max-height: 0; }
.slide-enter-to, .slide-leave-from { max-height: 300px; }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>
