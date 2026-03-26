<script setup lang="ts">
import type { CartItem } from '~/stores/cart'

const cartStore = useCartStore()
const router = useRouter()
const config = useRuntimeConfig()

// Load cart from localStorage on mount
onMounted(() => {
  cartStore.loadFromLocalStorage()
})

const showPoliciesModal = ref(false)
const selectedTourPolicies = ref<CartItem | null>(null)
const acceptedTerms = ref(false)

const formatDate = (dateString: string) => {
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

function removeItem(itemId: string) {
  if (confirm('Are you sure you want to remove this tour from your cart?')) {
    cartStore.removeItem(itemId)
  }
}

function continueShopping() {
  router.push('/tours')
}

function proceedToCheckout() {
  if (!acceptedTerms.value) {
    alert('Please accept the terms and conditions to continue')
    return
  }
  router.push('/checkout')
}

function openPoliciesModal(item: CartItem) {
  selectedTourPolicies.value = item
  showPoliciesModal.value = true
  document.body.style.overflow = 'hidden'
}

function closePoliciesModal() {
  showPoliciesModal.value = false
  selectedTourPolicies.value = null
  document.body.style.overflow = ''
}

function getImageUrl(path: string) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${config.public.storageBase}/${path}`
}
</script>

<template>
  <div class="min-h-screen bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-black mb-2">Shopping Cart</h1>
        <p class="text-slate-600 dark:text-slate-400">{{ cartStore.itemCount }} {{ cartStore.itemCount === 1 ? 'tour' : 'tours' }} in your cart</p>
      </div>

      <!-- Empty State -->
      <div v-if="cartStore.isEmpty" class="bg-white dark:bg-slate-900 rounded-xl p-12 text-center shadow-lg">
        <span class="material-symbols-outlined text-slate-300 dark:text-slate-700 mb-4 block" style="font-size: 96px;">shopping_cart</span>
        <h2 class="text-2xl font-black mb-2">Your cart is empty</h2>
        <p class="text-slate-600 dark:text-slate-400 mb-6">Explore our tours and add your favorites to the cart</p>
        <button @click="continueShopping" class="bg-primary hover:bg-primary/90 text-white font-bold px-6 py-3 rounded-lg transition-colors">
          Explore Tours
        </button>
      </div>

      <!-- Cart Items -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Items List -->
        <div class="lg:col-span-2 space-y-4">
          <div
            v-for="item in cartStore.items"
            :key="item.id"
            class="bg-white dark:bg-slate-900 rounded-xl p-4 shadow-lg hover:shadow-xl transition-shadow border border-slate-200 dark:border-slate-800"
          >
            <div class="flex gap-4">
              <!-- Tour Image -->
              <div class="flex-shrink-0">
                <img
                  v-if="item.tourImage"
                  :src="getImageUrl(item.tourImage)"
                  :alt="item.tourTitle"
                  class="w-24 h-24 object-cover rounded-lg"
                />
                <div v-else class="w-24 h-24 bg-slate-200 dark:bg-slate-800 rounded-lg flex items-center justify-center">
                  <span class="material-symbols-outlined text-slate-400 text-4xl">image</span>
                </div>
              </div>

              <!-- Tour Details -->
              <div class="flex-grow">
                <div class="flex justify-between items-start mb-2">
                  <div class="flex-1">
                    <h3 class="font-bold text-lg mb-2">{{ item.tourTitle }}</h3>
                    <div class="space-y-1 text-sm text-slate-600 dark:text-slate-400">
                      <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-base">calendar_today</span>
                        <span>{{ formatDate(item.selectedDate) }}</span>
                      </div>
                      <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-base">schedule</span>
                        <span>{{ formatTime(item.selectedTime) }}</span>
                      </div>
                      <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-base">group</span>
                        <span>{{ item.adults }} adult{{ item.adults !== 1 ? 's' : '' }}{{ item.children > 0 ? `, ${item.children} child${item.children !== 1 ? 'ren' : ''}` : '' }}</span>
                      </div>

                      <!-- Policies Button -->
                      <button
                        v-if="item.policies || item.cancellationPolicy"
                        @click="openPoliciesModal(item)"
                        class="flex items-center gap-1.5 text-primary hover:text-primary/80 text-sm font-semibold mt-2 hover:underline"
                      >
                        <span class="material-symbols-outlined text-base">description</span>
                        <span>View policies</span>
                      </button>
                    </div>
                  </div>

                  <!-- Remove Button -->
                  <button
                    @click="removeItem(item.id)"
                    class="text-red-500 hover:text-red-700 p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                    title="Remove from cart"
                  >
                    <span class="material-symbols-outlined">close</span>
                  </button>
                </div>

                <!-- Price -->
                <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                  <div class="flex justify-between items-center">
                    <span class="text-sm text-slate-600 dark:text-slate-400">Subtotal:</span>
                    <span class="text-lg font-black text-primary">
                      ${{ item.total.toFixed(2) }} {{ item.currency }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Summary Sidebar -->
        <div class="lg:col-span-1">
          <div class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-lg sticky top-24 border border-slate-200 dark:border-slate-800">
            <h2 class="text-xl font-black mb-4">Summary</h2>

            <div class="space-y-3 mb-6">
              <div class="flex justify-between text-sm">
                <span class="text-slate-600 dark:text-slate-400">Tours ({{ cartStore.itemCount }})</span>
                <span class="font-semibold">${{ cartStore.totalAmount.toFixed(2) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-slate-600 dark:text-slate-400">Participants</span>
                <span class="font-semibold">{{ cartStore.totalParticipants }}</span>
              </div>
              <div class="border-t border-slate-200 dark:border-slate-800 pt-3 flex justify-between">
                <span class="font-black">Total</span>
                <div class="text-right">
                  <div class="text-xl font-black text-primary">
                    ${{ cartStore.totalAmount.toFixed(2) }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Terms and Conditions -->
            <div class="mb-4 p-4 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
              <label class="flex items-start gap-3 cursor-pointer">
                <input
                  type="checkbox"
                  v-model="acceptedTerms"
                  class="mt-1 w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary"
                />
                <span class="text-sm text-slate-700 dark:text-slate-300">
                  I accept the
                  <a href="#" class="text-primary hover:text-primary/80 font-semibold underline">terms and conditions</a>
                  and cancellation policies of each tour
                </span>
              </label>
            </div>

            <div class="space-y-3">
              <button
                @click="proceedToCheckout"
                :disabled="!acceptedTerms"
                :class="[
                  'w-full font-black py-4 rounded-xl transition-all shadow-lg',
                  acceptedTerms
                    ? 'bg-primary hover:bg-primary/90 text-white cursor-pointer shadow-primary/20'
                    : 'bg-slate-300 dark:bg-slate-700 text-slate-500 dark:text-slate-400 cursor-not-allowed'
                ]"
              >
                <span class="flex items-center justify-center gap-2">
                  <span class="material-symbols-outlined">check_circle</span>
                  <span>Proceed to Payment</span>
                </span>
              </button>
              <button
                @click="continueShopping"
                class="w-full border-2 border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-semibold py-4 rounded-xl hover:border-primary hover:text-primary transition-colors"
              >
                Continue Shopping
              </button>
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
                <span>Flexible cancellation</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Policies Modal -->
    <Teleport to="body">
      <div
        v-if="showPoliciesModal && selectedTourPolicies"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
        @click.self="closePoliciesModal"
      >
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
          <!-- Modal Header -->
          <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-800 sticky top-0 bg-white dark:bg-slate-900 z-10">
            <h2 class="text-2xl font-black">Tour Policies</h2>
            <button
              @click="closePoliciesModal"
              class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg"
            >
              <span class="material-symbols-outlined text-2xl">close</span>
            </button>
          </div>

          <!-- Modal Content -->
          <div class="p-6 overflow-y-auto max-h-[calc(80vh-140px)]">
            <!-- Tour Title -->
            <div class="mb-6 pb-4 border-b border-slate-200 dark:border-slate-800">
              <h3 class="text-lg font-bold">{{ selectedTourPolicies.tourTitle }}</h3>
            </div>

            <!-- Cancellation Policy -->
            <div v-if="selectedTourPolicies.cancellationPolicy" class="mb-6">
              <h4 class="text-lg font-bold mb-3 flex items-center gap-2">
                <span class="material-symbols-outlined text-red-500">report</span>
                Cancellation Policy
              </h4>
              <div
                class="prose dark:prose-invert max-w-none text-sm text-slate-700 dark:text-slate-300 bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800"
                v-html="selectedTourPolicies.cancellationPolicy"
              ></div>
            </div>

            <!-- General Policies -->
            <div v-if="selectedTourPolicies.policies" class="mb-6">
              <h4 class="text-lg font-bold mb-3 flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-500">description</span>
                General Policies
              </h4>
              <div
                class="prose dark:prose-invert max-w-none text-sm text-slate-700 dark:text-slate-300 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800"
                v-html="selectedTourPolicies.policies"
              ></div>
            </div>

            <!-- No policies message -->
            <div v-if="!selectedTourPolicies.cancellationPolicy && !selectedTourPolicies.policies" class="text-center py-8">
              <span class="material-symbols-outlined text-slate-300 dark:text-slate-700 mb-3 block" style="font-size: 64px;">description</span>
              <p class="text-slate-500 dark:text-slate-400">No policies available for this tour.</p>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="p-6 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800">
            <button
              @click="closePoliciesModal"
              class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3 rounded-lg transition-colors"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
