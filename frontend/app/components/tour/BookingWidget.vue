<script setup lang="ts">
import { onMounted } from "vue"
import { useCartStore } from '~/stores/cart'
import { getImageUrl } from '~/utils/formatters'

interface Props {
  tour: any
}

const props = defineProps<Props>()
const router = useRouter()
const config = useRuntimeConfig()
const cartStore = useCartStore()

// State
const selectedDate = ref('')
const selectedTime = ref('')
const adults = ref(2)

// Fix for client-side hydration
onMounted(() => {
  console.log('BookingWidget mounted on client')
  console.log('CartStore available:', !!cartStore)
  console.log('Router available:', !!router)
})

// Computed
const basePrice = computed(() => {
  if (props.tour?.price_details && props.tour.price_details.length > 0) {
    const adultPrices = props.tour.price_details.filter((p: any) => {
      const ageStage = p.age_stage || {}
      return ageStage.min_age >= 12 || ageStage.max_age >= 18
    })

    if (adultPrices.length > 0) {
      const matchingPrice = adultPrices.find((p: any) => {
        const min = p.min_quantity || 1
        const max = p.max_quantity || Infinity
        return adults.value >= min && adults.value <= max
      })

      if (matchingPrice) {
        return parseFloat(matchingPrice.price || 0)
      }

      const prices = adultPrices.map((p: any) => parseFloat(p.price || 0))
      return Math.min(...prices)
    }
  }

  return props.tour?.min_price || 0
})

const subtotal = computed(() => basePrice.value * adults.value)
const total = computed(() => subtotal.value)
const currency = computed(() => props.tour?.currency || 'USD')

// Available times
const availableTimes = computed(() => {
  const times = []

  if (props.tour?.departure_time) {
    const [hours, minutes] = props.tour.departure_time.split(':')
    const hour = parseInt(hours)
    const ampm = hour >= 12 ? 'PM' : 'AM'
    const hour12 = hour % 12 || 12
    times.push({
      value: props.tour.departure_time,
      label: `${hour12}:${minutes} ${ampm}`
    })
  }

  return times
})

// Minimum bookable date
const minDate = computed(() => {
  const anticipationHours = props.tour?.booking_anticipation_hours || 24
  const now = new Date()
  const minDateTime = new Date(now.getTime() + (anticipationHours * 60 * 60 * 1000))
  return minDateTime.toISOString().split('T')[0]
})

// Methods
function incrementAdults() {
  if (adults.value < 20) adults.value++
}

function decrementAdults() {
  if (adults.value > 1) adults.value--
}

function handleBooking() {
  console.log('=== handleBooking called ===')
  console.log('Cart store exists:', !!cartStore)
  console.log('Cart items before:', cartStore.items)
  
  if (!selectedDate.value) {
    alert('Please select a date')
    return
  }

  if (!selectedTime.value) {
    alert('Please select a time')
    return
  }

  console.log('Validation passed, preparing to add to cart...')

  // Get tour image
  const tourImage = props.tour.media_gallery && props.tour.media_gallery.length > 0
    ? getImageUrl(props.tour.media_gallery[0].url)
    : ''
    
  console.log('Image URL:', tourImage)

  // Add to cart
  cartStore.addItem({
    tourId: props.tour.id,
    tourTitle: props.tour.title,
    tourSlug: props.tour.slug,
    tourImage,
    selectedDate: selectedDate.value,
    selectedTime: selectedTime.value,
    timezone: props.tour.timezone || 'America/Lima',
    adults: adults.value,
    children: 0,
    basePrice: basePrice.value,
    childPrice: 0,
    total: total.value,
    currency: currency.value,
    policies: props.tour.policies || '',
    cancellationPolicy: props.tour.cancellation_policy || '',
    taxPercentage: props.tour.tax_percentage || 0,
    advancePaymentPercentage: props.tour.advance_payment_percentage || 100
  })

  console.log('Called cartStore.addItem()')
  console.log('Cart items after:', cartStore.items)
  console.log('Cart total items:', cartStore.items.length)

  // Navigate directly to checkout
  console.log('Navigating to checkout...')
  router.push('/checkout')
}

</script>

<template>
  <div class="booking-widget bg-white rounded-2xl shadow-xl p-6 border border-gray-100">
    <!-- Price in single line -->
    <div class="mb-6">
      <p class="text-2xl font-bold text-gray-900">
        ${{ basePrice.toFixed(2) }} {{ currency }} <span class="text-lg font-normal text-gray-600">per person</span>
      </p>
    </div>

    <!-- Date Selector -->
    <div class="mb-3">
      <label class="block text-xs font-semibold text-gray-700 mb-1.5">Tour Date</label>
      <div class="relative">
        <input
          type="date"
          v-model="selectedDate"
          :min="minDate"
          placeholder="Selecciona una fecha"
          class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
        >
        <div class="absolute right-3 top-2.5 pointer-events-none">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-400">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
          </svg>
        </div>
      </div>
    </div>

    <!-- Time Selector -->
    <div class="mb-3">
      <label class="block text-xs font-semibold text-gray-700 mb-1.5">Departure Time</label>
      <div class="relative">
        <select
          v-model="selectedTime"
          class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent appearance-none"
        >
          <option value="">Selecciona horario</option>
          <option v-for="time in availableTimes" :key="time.value" :value="time.value">
            {{ time.label }}
          </option>
        </select>
        <div class="absolute right-3 top-2.5 pointer-events-none">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-400">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
          </svg>
        </div>
      </div>
    </div>

    <!-- Travelers Selector -->
    <div class="mb-4">
      <label class="block text-xs font-semibold text-gray-700 mb-1.5">Travelers</label>
      <div class="flex items-center justify-between border border-gray-300 rounded-lg px-3 py-2">
        <button
          @click="decrementAdults"
          type="button"
          class="w-8 h-8 flex items-center justify-center bg-gray-100 rounded-full hover:bg-gray-200 transition"
        >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-600">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
          </svg>
        </button>
        <span class="font-semibold text-sm">{{ adults }} adults</span>
        <button
          @click="incrementAdults"
          type="button"
          class="w-8 h-8 flex items-center justify-center bg-gray-100 rounded-full hover:bg-gray-200 transition"
        >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-600">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Price Breakdown -->
    <div class="mb-4 p-3 bg-gray-50 rounded-lg space-y-1.5">
      <div class="flex justify-between text-xs">
        <span class="text-gray-600">${{ basePrice.toFixed(2) }} x {{ adults }} adults</span>
        <span class="font-semibold">${{ subtotal.toFixed(2) }}</span>
      </div>
      <div class="border-t pt-1.5 flex justify-between font-bold text-base">
        <span>Total</span>
        <span class="text-cyan-500">${{ total.toFixed(2) }} {{ currency }}</span>
      </div>
    </div>

    <!-- CTA Button -->
    <button
      @click="handleBooking"
      class="w-full bg-cyan-500 hover:bg-cyan-600 text-white font-bold py-3 rounded-lg transition transform hover:scale-105 shadow-lg mb-2 flex items-center justify-center"
    >
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
      </svg>
      Book Now
    </button>


    <!-- Trust Signals -->
    <div class="space-y-2 py-3 border-t border-b">
      <div class="flex items-center space-x-2 text-xs text-gray-600">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-green-500">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
        </svg>
        <span>Secure booking</span>
      </div>
      <div class="flex items-center space-x-2 text-xs text-gray-600">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-yellow-500">
          <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
        </svg>
        <span>Instant confirmation</span>
      </div>
      <div class="flex items-center space-x-2 text-xs text-gray-600">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-blue-500">
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
        </svg>
        <span>24/7 support</span>
      </div>
    </div>

  </div>
</template>

<style scoped>
@media (min-width: 1024px) {
  .booking-widget {
    position: sticky;
    top: 24px;
    height: fit-content;
  }
}
</style>
