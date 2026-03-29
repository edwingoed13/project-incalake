<script setup lang="ts">
const cartStore = useCartStore()
const router = useRouter()

// Redirect if cart becomes empty
watch(() => cartStore.isEmpty, (isEmpty) => {
  if (isEmpty) {
    router.push('/tours')
  }
})

const formattedDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatTime = (timeString: string) => {
  const [hours, minutes] = timeString.split(':')
  const hour = parseInt(hours)
  const ampm = hour >= 12 ? 'PM' : 'AM'
  const hour12 = hour % 12 || 12
  return `${hour12}:${minutes} ${ampm}`
}
</script>

<template>
  <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg p-6 sticky top-24 border border-slate-200 dark:border-slate-800">
    <h3 class="text-xl font-black mb-4">Booking Summary</h3>

    <!-- Tours List -->
    <div class="space-y-4 mb-6 pb-6 border-b border-slate-200 dark:border-slate-800">
      <div v-for="item in cartStore.items" :key="item.id" class="relative space-y-2 group">
        <!-- Delete Button -->
        <button
          @click="cartStore.removeItem(item.id)"
          class="absolute -top-1 -right-1 p-1 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-full transition-all duration-200 hover:scale-110"
          aria-label="Remove tour from cart"
          title="Eliminar del carrito"
        >
          <span class="material-symbols-outlined text-red-500 text-base">delete</span>
        </button>

        <h4 class="font-bold text-slate-900 dark:text-white pr-8">{{ item.tourTitle }}</h4>

        <!-- Offer Badge if applicable -->
        <div v-if="item.hasOffer" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-semibold" :style="{ backgroundColor: (item.offerColor || '#22c55e') + '20', color: item.offerColor || '#22c55e' }">
          <span class="material-symbols-outlined text-sm">sell</span>
          <span>{{ item.offerDiscount }}{{ item.offerDiscountType === 'percentage' ? '%' : ' USD' }} de descuento aplicado</span>
        </div>

        <div class="space-y-1 text-sm text-slate-600 dark:text-slate-400">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-base">calendar_today</span>
            <span class="capitalize">{{ formattedDate(item.selectedDate) }}</span>
          </div>
          <div v-if="item.selectedTime" class="flex items-center gap-2">
            <span class="material-symbols-outlined text-base">schedule</span>
            <span>{{ formatTime(item.selectedTime) }}</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-base">group</span>
            <span>{{ item.adults }} adult{{ item.adults !== 1 ? 's' : '' }}{{ item.children > 0 ? `, ${item.children} child${item.children !== 1 ? 'ren' : ''}` : '' }}</span>
          </div>
        </div>

        <!-- Price breakdown with offer -->
        <div class="space-y-1 pt-2">
          <div v-if="item.hasOffer && item.originalPrice" class="flex justify-between text-sm">
            <span class="text-slate-500">Precio original:</span>
            <span class="line-through text-slate-400">${{ (item.originalPrice * item.adults).toFixed(2) }}</span>
          </div>
          <div v-if="item.hasOffer" class="flex justify-between text-sm">
            <span class="text-green-600">Descuento aplicado:</span>
            <span class="font-semibold text-green-600">-${{ ((item.originalPrice - item.basePrice) * item.adults).toFixed(2) }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-sm font-semibold text-slate-600 dark:text-slate-400">Subtotal:</span>
            <span class="font-bold text-slate-900 dark:text-white">${{ item.total.toFixed(2) }} {{ item.currency }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Participants Breakdown -->
    <div class="mb-6 pb-6 border-b border-slate-200 dark:border-slate-800 space-y-3">
      <div class="flex justify-between text-sm">
        <span class="text-slate-600 dark:text-slate-400">
          Total Participants
        </span>
        <span class="font-semibold text-slate-900 dark:text-white">
          {{ cartStore.totalParticipants }}
        </span>
      </div>

      <div class="flex justify-between text-sm">
        <span class="text-slate-600 dark:text-slate-400">
          Total Tours
        </span>
        <span class="font-semibold text-slate-900 dark:text-white">
          {{ cartStore.itemCount }}
        </span>
      </div>
    </div>

    <!-- Total Amount -->
    <div class="space-y-3">
      <div class="flex justify-between items-center">
        <span class="text-lg font-black">Total</span>
        <div class="text-right">
          <div class="text-2xl font-black text-primary">
            ${{ cartStore.totalAmount.toFixed(2) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Trust Signals -->
    <div class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-800 space-y-2">
      <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
        <span class="material-symbols-outlined text-green-500 text-base">shield</span>
        <span>Secure payment</span>
      </div>
      <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
        <span class="material-symbols-outlined text-yellow-500 text-base">bolt</span>
        <span>Instant confirmation</span>
      </div>
      <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
        <span class="material-symbols-outlined text-blue-500 text-base">verified_user</span>
        <span>24/7 support</span>
      </div>
    </div>
  </div>
</template>
