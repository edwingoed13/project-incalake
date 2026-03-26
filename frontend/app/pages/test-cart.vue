<template>
  <div class="container mx-auto p-8">
    <h1 class="text-2xl font-bold mb-6">Cart Test Page</h1>

    <div class="bg-white p-6 rounded-lg shadow mb-6">
      <h2 class="text-lg font-bold mb-4">Test Cart Operations</h2>

      <div class="space-y-4">
        <button
          @click="testAddToCart"
          class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
        >
          Test Add to Cart
        </button>

        <button
          @click="testLoadFromLocalStorage"
          class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600"
        >
          Load from LocalStorage
        </button>

        <button
          @click="checkLocalStorage"
          class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600"
        >
          Check LocalStorage Directly
        </button>

        <button
          @click="clearCart"
          class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600"
        >
          Clear Cart
        </button>
      </div>

      <div class="mt-6 p-4 bg-gray-100 rounded">
        <h3 class="font-bold mb-2">Cart Store Status:</h3>
        <pre class="text-sm">{{ cartStoreStatus }}</pre>
      </div>

      <div class="mt-4 p-4 bg-gray-100 rounded">
        <h3 class="font-bold mb-2">LocalStorage Status:</h3>
        <pre class="text-sm">{{ localStorageStatus }}</pre>
      </div>

      <div class="mt-4 p-4 bg-gray-100 rounded">
        <h3 class="font-bold mb-2">Cart Items:</h3>
        <pre class="text-sm">{{ JSON.stringify(cartItems, null, 2) }}</pre>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useCartStore } from '~/stores/cart'

const cartStore = useCartStore()
const cartStoreStatus = ref('Not checked')
const localStorageStatus = ref('Not checked')
const cartItems = computed(() => cartStore.items)

onMounted(() => {
  // Load cart on mount
  cartStore.loadFromLocalStorage()
  updateStatus()
})

function testAddToCart() {
  const testItem = {
    tourId: 123,
    tourTitle: 'Test Tour',
    tourSlug: 'test-tour',
    tourImage: 'http://example.com/image.jpg',
    selectedDate: '2024-03-15',
    selectedTime: '09:00',
    timezone: 'America/Lima',
    adults: 2,
    children: 0,
    basePrice: 50,
    childPrice: 0,
    total: 100,
    currency: 'USD',
    policies: '',
    cancellationPolicy: '',
    taxPercentage: 0,
    advancePaymentPercentage: 100
  }

  console.log('Adding item to cart:', testItem)
  cartStore.addItem(testItem)
  updateStatus()
}

function testLoadFromLocalStorage() {
  console.log('Loading from localStorage...')
  cartStore.loadFromLocalStorage()
  updateStatus()
}

function checkLocalStorage() {
  if (typeof window !== 'undefined' && window.localStorage) {
    const stored = localStorage.getItem('voyager_cart')
    localStorageStatus.value = stored ? `Found: ${stored}` : 'Empty or not found'
    console.log('LocalStorage content:', stored)
  }
}

function clearCart() {
  console.log('Clearing cart...')
  cartStore.clearCart()
  updateStatus()
}

function updateStatus() {
  const total = cartStore.items.reduce((sum, item) => sum + (item.total || 0), 0)
  cartStoreStatus.value = `Items count: ${cartStore.items.length}, Total: $${total.toFixed(2)}, isEmpty: ${cartStore.isEmpty}`
  checkLocalStorage()
}
</script>