<script setup lang="ts">
interface Props {
  tour: any
}

const props = defineProps<Props>()
const router = useRouter()
const config = useRuntimeConfig()

// State
const selectedDate = ref('')
const selectedTime = ref('')
const adults = ref(2)

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
  if (!selectedDate.value) {
    alert('Por favor selecciona una fecha')
    return
  }

  if (!selectedTime.value) {
    alert('Por favor selecciona un horario')
    return
  }

  // TODO: Navigate to checkout
  console.log('Booking:', { selectedDate: selectedDate.value, selectedTime: selectedTime.value, adults: adults.value })
}

function addToCart() {
  if (!selectedDate.value) {
    alert('Por favor selecciona una fecha')
    return
  }

  if (!selectedTime.value) {
    alert('Por favor selecciona un horario')
    return
  }

  // TODO: Add to cart store
  console.log('Add to cart:', { selectedDate: selectedDate.value, selectedTime: selectedTime.value, adults: adults.value })
}

function handleConsult() {
  console.log('Consult')
}

function handleSave() {
  console.log('Save to wishlist')
}
</script>

<template>
  <div class="booking-widget bg-white rounded-2xl shadow-xl p-4 border border-gray-100">
    <!-- Price -->
    <div class="mb-4">
      <div class="flex items-baseline space-x-2">
        <span class="text-3xl font-bold text-gray-900">${{ basePrice.toFixed(2) }}</span>
        <span class="text-sm text-gray-600">{{ currency }}</span>
        <span class="text-sm text-gray-500">per person</span>
      </div>
    </div>

    <!-- Date Selector -->
    <div class="mb-3">
      <label class="block text-xs font-semibold text-gray-700 mb-1.5">Fecha de inicio</label>
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
      <label class="block text-xs font-semibold text-gray-700 mb-1.5">Horario</label>
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
      <label class="block text-xs font-semibold text-gray-700 mb-1.5">Viajeros</label>
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
        <span class="font-semibold text-sm">{{ adults }} adultos</span>
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
        <span class="text-gray-600">${{ basePrice.toFixed(2) }} x {{ adults }} adultos</span>
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
      Reservar ahora
    </button>

    <button
      @click="addToCart"
      class="w-full bg-white text-cyan-500 border-2 border-cyan-500 font-bold py-3 rounded-lg transition hover:bg-cyan-50 mb-2 flex items-center justify-center"
    >
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
      </svg>
      Agregar al carrito
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

    <!-- Consult & Wishlist -->
    <div class="flex space-x-2 mt-3">
      <button
        @click="handleConsult"
        type="button"
        class="flex-1 border-2 border-gray-300 text-gray-700 font-semibold py-2 text-sm rounded-lg hover:border-cyan-500 hover:text-cyan-500 transition flex items-center justify-center"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
          <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
        </svg>
        Consultar
      </button>
      <button
        @click="handleSave"
        type="button"
        class="flex-1 border-2 border-gray-300 text-gray-700 font-semibold py-2 text-sm rounded-lg hover:border-cyan-500 hover:text-cyan-500 transition flex items-center justify-center"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
        </svg>
        Guardar
      </button>
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
