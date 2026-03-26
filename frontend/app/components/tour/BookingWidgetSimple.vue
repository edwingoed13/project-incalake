<template>
  <ClientOnly>
    <div class="booking-widget bg-white rounded-2xl shadow-xl p-6 border border-gray-100">
      <h3 class="text-lg font-bold mb-4">Simple Test Widget</h3>

      <!-- Test Button -->
      <button
        @click="testAddToCart"
        type="button"
        class="w-full bg-cyan-500 hover:bg-cyan-600 text-white font-bold py-3 rounded-lg mb-2"
      >
        Test Add to Cart
      </button>

      <div v-if="message" class="mt-4 p-3 bg-green-100 text-green-700 rounded">
        {{ message }}
      </div>
    </div>
  </ClientOnly>
</template>

<script setup>
import { useCartStore } from '~/stores/cart'

const cartStore = useCartStore()
const message = ref('')

function testAddToCart() {
  console.log('testAddToCart called!')

  const testItem = {
    tourId: 999,
    tourTitle: 'Test Tour from Simple Widget',
    tourSlug: 'test-tour',
    tourImage: '',
    selectedDate: '2024-03-20',
    selectedTime: '09:00',
    timezone: 'America/Lima',
    adults: 2,
    children: 0,
    basePrice: 100,
    childPrice: 0,
    total: 200,
    currency: 'USD',
    policies: '',
    cancellationPolicy: '',
    taxPercentage: 0,
    advancePaymentPercentage: 100
  }

  console.log('Adding item:', testItem)
  cartStore.addItem(testItem)

  console.log('Cart items after add:', cartStore.items)
  message.value = `Item added! Cart now has ${cartStore.items.length} items`
}

onMounted(() => {
  console.log('Simple widget mounted')
  console.log('CartStore:', cartStore)
})
</script>