<template>
  <div class="min-h-screen bg-slate-50 pt-24 pb-12">
    <div class="max-w-5xl mx-auto px-4">

      <!-- Loading -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-20">
        <div class="size-12 border-4 border-primary/20 border-t-primary rounded-full animate-spin mb-4"></div>
        <p class="text-sm text-slate-500 font-medium">{{ t('loading_payment') }}</p>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="max-w-md mx-auto bg-white rounded-2xl shadow-lg p-8 text-center">
        <span class="material-symbols-outlined text-red-400 text-5xl mb-4">error</span>
        <h3 class="text-lg font-bold text-slate-800 mb-2">{{ t('payment_error') }}</h3>
        <p class="text-sm text-slate-500 mb-6">{{ error }}</p>
        <button @click="router.push('/cart')" class="px-6 py-2.5 bg-primary text-white font-bold rounded-xl text-sm">
          {{ t('return_cart') }}
        </button>
      </div>

      <!-- Payment Content -->
      <div v-else-if="allBookings.length > 0" class="grid grid-cols-1 lg:grid-cols-5 gap-4 lg:gap-6">

        <!-- Left: Order Summary (3 cols) -->
        <div class="lg:col-span-3 space-y-4">
          <!-- Header -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 sm:p-6">
            <div class="flex items-center gap-3 mb-1">
              <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center">
                <span class="material-symbols-outlined text-primary">receipt_long</span>
              </div>
              <div>
                <h2 class="text-lg font-bold text-slate-800">{{ t('order_summary') }}</h2>
                <p class="text-xs text-slate-400">{{ allBookings.length }} {{ allBookings.length === 1 ? t('tour_selected') : t('tours_selected') }}</p>
              </div>
            </div>
          </div>

          <!-- Tour Cards -->
          <div
            v-for="(b, idx) in allBookings"
            :key="b.booking_code"
            class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden"
          >
            <div class="p-5">
              <div class="flex gap-4">
                <!-- Tour number -->
                <div class="size-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center text-sm font-bold shrink-0">
                  {{ idx + 1 }}
                </div>
                <div class="flex-1 min-w-0">
                  <!-- Tour title -->
                  <h3 class="text-sm font-bold text-slate-800 mb-1">{{ b.tour?.title || 'Tour' }}</h3>

                  <!-- Date & Participants -->
                  <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 mb-3">
                    <span class="flex items-center gap-1">
                      <span class="material-symbols-outlined text-xs">calendar_today</span>
                      {{ formatDate(b.tour_date) }}
                    </span>
                    <span class="flex items-center gap-1">
                      <span class="material-symbols-outlined text-xs">group</span>
                      {{ b.participants?.adults || 0 }} adults
                      <template v-if="b.participants?.children">, {{ b.participants.children }} children</template>
                    </span>
                  </div>

                  <!-- Booking code is intentionally hidden until payment is
                       confirmed (shown on the confirmation page). -->
                </div>

                <!-- Price -->
                <div class="text-right shrink-0">
                  <p class="text-lg font-black text-slate-800">{{ currencyStore.formatConverted(b.pricing?.total || 0) }}</p>
                  <p class="text-[10px] text-slate-400">{{ b.pricing?.currency || 'USD' }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Customer Info -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">{{ t('customer') }}</h4>
            <div class="flex items-center gap-3">
              <div class="size-10 rounded-full bg-primary/10 flex items-center justify-center text-primary text-sm font-bold">
                {{ getInitials(allBookings[0]?.customer?.name || '') }}
              </div>
              <div>
                <p class="text-sm font-bold text-slate-800">{{ allBookings[0]?.customer?.name }}</p>
                <p class="text-xs text-slate-500">{{ allBookings[0]?.customer?.email }}</p>
              </div>
            </div>
          </div>

          <!-- Total -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <div class="space-y-2 mb-3 pb-3 border-b border-slate-100">
              <div class="flex justify-between text-xs">
                <span class="text-slate-500">{{ t('subtotal') }} ({{ allBookings.length }} {{ allBookings.length === 1 ? t('booking') : t('bookings') }})</span>
                <span class="font-semibold">{{ currencyStore.formatConverted(subtotalAmount) }}</span>
              </div>
              <div v-if="taxAmount > 0" class="flex justify-between text-xs">
                <span class="text-slate-500">{{ t('transaction_fees') }}</span>
                <span class="font-semibold">{{ currencyStore.formatConverted(taxAmount) }}</span>
              </div>
            </div>
            <div class="flex justify-between items-center">
              <p class="font-black">{{ paymentMode === 'advance' && hasAdvanceOption ? 'Pagas ahora' : t('total_to_pay') }}</p>
              <p class="text-2xl font-black text-primary">
                {{ currencyStore.formatConverted(payNowAmount) }}
                <span v-if="!currencyStore.isForeignCurrency" class="text-sm font-semibold text-slate-400">USD</span>
              </p>
            </div>
            <div v-if="paymentMode === 'advance' && hasAdvanceOption" class="flex justify-between items-center mt-1 text-xs text-slate-500">
              <span>Saldo a pagar el día del tour</span>
              <span class="font-semibold">{{ currencyStore.formatConverted(balanceAmount) }}</span>
            </div>
            <div v-if="currencyStore.isForeignCurrency" class="mt-3 flex items-start gap-1.5 p-2 bg-amber-50 border border-amber-200 rounded-lg">
              <span class="material-symbols-outlined text-amber-600 text-sm mt-0.5">info</span>
              <div class="flex-1">
                <p class="text-[11px] text-amber-800 leading-tight font-semibold">{{ t('payment_usd_notice') }}</p>
                <p class="text-[10px] text-amber-700 mt-0.5">≈ ${{ grandTotal.toFixed(2) }} USD</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Payment (2 cols) -->
        <div class="lg:col-span-2">
          <div class="sticky top-24 space-y-4">
            <!-- Payment mode (deposit vs full) — only when the tour offers a deposit -->
            <div v-if="hasAdvanceOption" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 space-y-2">
              <p class="text-xs font-bold uppercase tracking-wider text-slate-400">¿Cuánto deseas pagar ahora?</p>
              <!-- Full payment first = the default/recommended -->
              <label
                class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all"
                :class="paymentMode === 'full' ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-slate-300'"
              >
                <input type="radio" v-model="paymentMode" value="full" class="text-primary focus:ring-primary" />
                <div class="flex-1">
                  <p class="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                    Pagar todo ahora
                    <span class="px-1.5 py-0.5 rounded bg-trust/10 text-trust text-[9px] font-black uppercase tracking-wide">Recomendado</span>
                  </p>
                  <p class="text-[11px] text-slate-500">Sin saldo pendiente</p>
                </div>
                <span class="text-sm font-black text-primary">{{ currencyStore.formatConverted(grandTotal) }}</span>
              </label>
              <label
                class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all"
                :class="paymentMode === 'advance' ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-slate-300'"
              >
                <input type="radio" v-model="paymentMode" value="advance" class="text-primary focus:ring-primary" />
                <div class="flex-1">
                  <p class="text-sm font-bold text-slate-800">Pagar adelanto</p>
                  <p class="text-[11px] text-slate-500">Saldo {{ currencyStore.formatConverted(grandTotal - advanceTotal) }} el día del tour</p>
                </div>
                <span class="text-sm font-black text-primary">{{ currencyStore.formatConverted(advanceTotal) }}</span>
              </label>
            </div>

            <!-- Pay Button -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 sm:p-6">
              <ClientOnly>
                <PaymentCulqiCheckoutFixed
                  :public-key="paymentConfig?.culqi_public_key || 'pk_test_J0V01cM2W5eNlHNz'"
                  :amount="payNowAmount"
                  :currency="allBookings[0]?.pricing?.currency || 'USD'"
                  :description="`Incalake Tours - ${allBookings.length} booking(s)`"
                  :customer-email="customerInfo.email"
                  :customer-first-name="customerInfo.firstName"
                  :customer-last-name="customerInfo.lastName"
                  :customer-phone="customerInfo.phone"
                  :customer-country="customerInfo.country"
                  @success="handlePaymentSuccess"
                  @error="handlePaymentError"
                />
              </ClientOnly>

              <div class="flex items-center justify-center gap-2 text-xs text-slate-400 mt-4">
                <span class="material-symbols-outlined text-sm">shield</span>
                <span>{{ t('secure_powered') }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Processing Modal -->
    <Teleport to="body">
      <div v-if="processingPayment" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-sm w-full text-center">
          <div class="size-16 border-4 border-primary/20 border-t-primary rounded-full animate-spin mx-auto mb-4"></div>
          <h3 class="text-lg font-bold text-slate-800 mb-1">{{ t('processing_payment') }}</h3>
          <p class="text-sm text-slate-500">{{ t('please_wait') }}</p>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const { t } = useI18n()
const currencyStore = useCurrencyStore()

useHead({
  title: 'Payment - Complete Your Booking',
  meta: [{ name: 'robots', content: 'noindex, nofollow' }]
})

const router = useRouter()
const route = useRoute()
const bookingStore = useBookingStore()
const cartStore = useCartStore()

const loading = ref(true)
const error = ref<string | null>(null)
const processingPayment = ref(false)
const allBookings = ref<any[]>([])
const paymentConfig = ref<any>(null)

const subtotalAmount = computed(() =>
  allBookings.value.reduce((sum, b) => sum + (b.pricing?.subtotal || b.pricing?.total || 0), 0)
)

const taxAmount = computed(() =>
  allBookings.value.reduce((sum, b) => {
    const tax = b.pricing?.tax_amount || 0
    return sum + tax
  }, 0)
)

const grandTotal = computed(() => subtotalAmount.value + taxAmount.value)

// Advance (deposit) support: pricing.amount_to_pay = total * advance% (from
// the API). When the deposit is less than the full total, let the customer
// choose to pay the deposit now and the balance on the tour day.
const advanceTotal = computed(() =>
  allBookings.value.reduce((sum, b) => sum + (b.pricing?.amount_to_pay ?? b.pricing?.total ?? 0), 0)
)
const hasAdvanceOption = computed(() => advanceTotal.value > 0 && advanceTotal.value < grandTotal.value - 0.01)
const paymentMode = ref<'full' | 'advance'>('full')
const payNowAmount = computed(() =>
  paymentMode.value === 'advance' && hasAdvanceOption.value ? advanceTotal.value : grandTotal.value
)
const balanceAmount = computed(() => Math.max(0, grandTotal.value - payNowAmount.value))

const customerInfo = computed(() => {
  const c = allBookings.value[0]?.customer || {}
  const fullName = (c.name || '').trim()
  const parts = fullName.split(/\s+/)
  const firstName = parts.length > 2
    ? parts.slice(0, Math.ceil(parts.length / 2)).join(' ')
    : parts[0] || ''
  const lastName = parts.length > 2
    ? parts.slice(Math.ceil(parts.length / 2)).join(' ')
    : parts.slice(1).join(' ') || ''
  return {
    email: c.email || '',
    firstName,
    lastName,
    phone: c.phone || '',
    country: c.country || ''
  }
})

function getInitials(name: string) {
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
}

function formatDate(dateString: string) {
  if (!dateString) return ''
  const clean = dateString.split('T')[0] // Remove time part from ISO datetime
  const [y, m, d] = clean.split('-').map(Number)
  const date = new Date(y, m - 1, d)
  return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' })
}

onMounted(async () => {
  try {
    const bookingParam = route.query.booking as string
    const email = route.query.email as string

    if (!bookingParam) throw new Error('Booking code is required')

    // Support multiple booking codes: BK-001,BK-002
    const codes = bookingParam.split(',').map(c => c.trim()).filter(Boolean)

    for (const code of codes) {
      const data = await bookingStore.getBooking(code, email)
      const booking = data?.booking || data
      if (booking) allBookings.value.push(booking)
    }

    if (allBookings.value.length === 0) throw new Error('No bookings found')

    // Load payment config
    const { api } = useApi()
    const configResponse = await api('/payment/config')
    paymentConfig.value = configResponse.data || configResponse

    // Default to paying the FULL amount; the customer can opt into the deposit.
    paymentMode.value = 'full'

    loading.value = false
  } catch (err: any) {
    error.value = err.message || 'Failed to load payment'
    loading.value = false
  }
})

const handlePaymentSuccess = async (token: string, paymentData: any) => {
  try {
    processingPayment.value = true

    // A Culqi token is single-use, so we make ONE charge for the whole group
    // (grandTotal). The backend marks every booking in the group as paid.
    const groupIds = allBookings.value.map(b => b.id)
    await bookingStore.confirmCulqiPayment(allBookings.value[0].id, token, paymentData, groupIds, paymentMode.value)

    cartStore.clearCart()

    // One code in the URL — the confirmation page resolves the whole
    // multi-tour group from the payment record on the backend.
    const email = allBookings.value[0]?.customer?.email || route.query.email
    router.push(`/booking-confirmation/${allBookings.value[0].booking_code}?email=${encodeURIComponent(email as string)}`)
  } catch (err: any) {
    error.value = err.message || 'Payment confirmation failed'
    processingPayment.value = false
  }
}

const handlePaymentError = (errorMsg: string) => {
  error.value = errorMsg
}
</script>
