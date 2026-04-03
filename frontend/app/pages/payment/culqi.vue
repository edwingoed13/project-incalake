<template>
  <div class="min-h-screen bg-slate-50 pt-24 pb-12">
    <div class="max-w-5xl mx-auto px-4">

      <!-- Loading -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-20">
        <div class="size-12 border-4 border-primary/20 border-t-primary rounded-full animate-spin mb-4"></div>
        <p class="text-sm text-slate-500 font-medium">Loading payment...</p>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="max-w-md mx-auto bg-white rounded-2xl shadow-lg p-8 text-center">
        <span class="material-symbols-outlined text-red-400 text-5xl mb-4">error</span>
        <h3 class="text-lg font-bold text-slate-800 mb-2">Payment Error</h3>
        <p class="text-sm text-slate-500 mb-6">{{ error }}</p>
        <button @click="router.push('/cart')" class="px-6 py-2.5 bg-primary text-white font-bold rounded-xl text-sm">
          Return to Cart
        </button>
      </div>

      <!-- Payment Content -->
      <div v-else-if="allBookings.length > 0" class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        <!-- Left: Order Summary (3 cols) -->
        <div class="lg:col-span-3 space-y-4">
          <!-- Header -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <div class="flex items-center gap-3 mb-1">
              <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center">
                <span class="material-symbols-outlined text-primary">receipt_long</span>
              </div>
              <div>
                <h2 class="text-lg font-bold text-slate-800">Order Summary</h2>
                <p class="text-xs text-slate-400">{{ allBookings.length }} {{ allBookings.length === 1 ? 'tour' : 'tours' }} selected</p>
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

                  <!-- Booking code -->
                  <span class="text-[10px] font-mono font-bold text-slate-400 bg-slate-50 px-2 py-0.5 rounded">{{ b.booking_code }}</span>
                </div>

                <!-- Price -->
                <div class="text-right shrink-0">
                  <p class="text-lg font-black text-slate-800">${{ (b.pricing?.total || 0).toFixed(2) }}</p>
                  <p class="text-[10px] text-slate-400">{{ b.pricing?.currency || 'USD' }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Customer Info -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Customer</h4>
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
            <div class="flex justify-between items-center">
              <div>
                <p class="text-xs text-slate-400 font-semibold uppercase">Total to Pay</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ allBookings.length }} {{ allBookings.length === 1 ? 'booking' : 'bookings' }}</p>
              </div>
              <p class="text-3xl font-black text-primary">${{ grandTotal.toFixed(2) }} <span class="text-sm font-semibold text-slate-400">USD</span></p>
            </div>
          </div>
        </div>

        <!-- Right: Payment (2 cols) -->
        <div class="lg:col-span-2">
          <div class="sticky top-24 space-y-4">
            <!-- Pay Button -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
              <ClientOnly>
                <PaymentCulqiCheckoutFixed
                  :public-key="paymentConfig?.culqi_public_key || 'pk_test_J0V01cM2W5eNlHNz'"
                  :amount="grandTotal"
                  :currency="allBookings[0]?.pricing?.currency || 'USD'"
                  :description="`Incalake Tours - ${allBookings.length} booking(s)`"
                  :customer-email="allBookings[0]?.customer?.email || ''"
                  @success="handlePaymentSuccess"
                  @error="handlePaymentError"
                />
              </ClientOnly>

              <div class="flex items-center justify-center gap-2 text-xs text-slate-400 mt-4">
                <span class="material-symbols-outlined text-sm">shield</span>
                <span>Secure payment powered by Culqi</span>
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
          <h3 class="text-lg font-bold text-slate-800 mb-1">Processing Payment</h3>
          <p class="text-sm text-slate-500">Please wait...</p>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'

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

const grandTotal = computed(() =>
  allBookings.value.reduce((sum, b) => sum + (b.pricing?.total || 0), 0)
)

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

    loading.value = false
  } catch (err: any) {
    error.value = err.message || 'Failed to load payment'
    loading.value = false
  }
})

const handlePaymentSuccess = async (token: string, paymentData: any) => {
  try {
    processingPayment.value = true

    // Confirm payment for all bookings
    for (const booking of allBookings.value) {
      await bookingStore.confirmCulqiPayment(booking.id, token, paymentData)
    }

    cartStore.clearCart()

    // Redirect to first booking confirmation
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
