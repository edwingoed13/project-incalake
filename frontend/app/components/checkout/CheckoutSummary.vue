<script setup lang="ts">
const cartStore = useCartStore()

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
      <div v-for="item in cartStore.items" :key="item.id" class="space-y-2">
        <h4 class="font-bold text-slate-900 dark:text-white">{{ item.tourTitle }}</h4>
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
        <div class="flex justify-between items-center pt-2">
          <span class="text-sm font-semibold text-slate-600 dark:text-slate-400">Subtotal:</span>
          <span class="font-bold text-slate-900 dark:text-white">${{ item.total.toFixed(2) }} {{ item.currency }}</span>
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
