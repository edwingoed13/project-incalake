<script setup lang="ts">
import { countries } from '~/utils/countries'

interface Props {
  pickupAvailable?: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  submit: [data: {
    customer_name: string
    customer_email: string
    customer_phone: string
    customer_country: string
    customer_notes: string
    pickup_location?: string
    payment_method: string
  }]
}>()

const cartStore = useCartStore()

// Form data
const customerName = ref('')
const customerEmail = ref('')
const customerPhone = ref('')
const customerCountry = ref('PE')
const customerNotes = ref('')
const pickupLocation = ref('')
const paymentMethod = ref<'culqi' | 'paypal'>('culqi')
const acceptedTerms = ref(false)

// Modal state
const showPoliciesModal = ref(false)
const toursPolicies = ref<Array<{ title: string, policies: string, cancellationPolicy: string }>>([])

// Form validation
const errors = ref<Record<string, string>>({})

const validateForm = () => {
  errors.value = {}

  if (!customerName.value.trim()) {
    errors.value.customer_name = 'Full name is required'
  }

  if (!customerEmail.value.trim()) {
    errors.value.customer_email = 'Email is required'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(customerEmail.value)) {
    errors.value.customer_email = 'Invalid email address'
  }

  if (!customerPhone.value.trim()) {
    errors.value.customer_phone = 'Phone number is required'
  }

  if (!customerCountry.value) {
    errors.value.customer_country = 'Country is required'
  }

  if (props.pickupAvailable && !pickupLocation.value.trim()) {
    errors.value.pickup_location = 'Pickup location is required'
  }

  if (!acceptedTerms.value) {
    errors.value.accepted_terms = 'You must accept the terms and conditions to continue'
  }

  return Object.keys(errors.value).length === 0
}

const handleSubmit = (e: Event) => {
  e.preventDefault()

  if (validateForm()) {
    emit('submit', {
      customer_name: customerName.value,
      customer_email: customerEmail.value,
      customer_phone: customerPhone.value,
      customer_country: customerCountry.value,
      customer_notes: customerNotes.value,
      pickup_location: pickupLocation.value,
      payment_method: paymentMethod.value
    })
  }
}

// Show policies modal
const viewPolicies = () => {
  const uniqueTours = new Map()

  // Group items by tour to get unique policies
  cartStore.items.forEach(item => {
    if (!uniqueTours.has(item.tourId)) {
      uniqueTours.set(item.tourId, {
        title: item.tourTitle,
        policies: item.policies || 'No general policies available.',
        cancellationPolicy: item.cancellationPolicy || 'No cancellation policy available.'
      })
    }
  })

  toursPolicies.value = Array.from(uniqueTours.values())
  showPoliciesModal.value = true
  document.body.style.overflow = 'hidden'
}

const closePoliciesModal = () => {
  showPoliciesModal.value = false
  toursPolicies.value = []
  document.body.style.overflow = ''
}

const modalTitle = computed(() => {
  if (toursPolicies.value.length === 0) return 'Policies'
  if (toursPolicies.value.length === 1) return `Policies - ${toursPolicies.value[0].title}`
  return `Policies - ${toursPolicies.value.length} Tours`
})
</script>

<template>
  <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg p-6 border border-slate-200 dark:border-slate-800">
    <h2 class="text-xl font-black mb-6">Customer Information</h2>

    <form @submit="handleSubmit" class="space-y-4">
      <!-- Customer Name -->
      <div>
        <label for="customer_name" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
          Full Name *
        </label>
        <input
          id="customer_name"
          v-model="customerName"
          type="text"
          class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
          :class="errors.customer_name ? 'border-red-500' : 'border-slate-300 dark:border-slate-700'"
          placeholder="John Doe"
        />
        <p v-if="errors.customer_name" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ errors.customer_name }}
        </p>
      </div>

      <!-- Customer Email -->
      <div>
        <label for="customer_email" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
          Email Address *
        </label>
        <input
          id="customer_email"
          v-model="customerEmail"
          type="email"
          class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
          :class="errors.customer_email ? 'border-red-500' : 'border-slate-300 dark:border-slate-700'"
          placeholder="john@example.com"
        />
        <p v-if="errors.customer_email" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ errors.customer_email }}
        </p>
      </div>

      <!-- Customer Phone -->
      <div>
        <label for="customer_phone" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
          Phone Number *
        </label>
        <input
          id="customer_phone"
          v-model="customerPhone"
          type="tel"
          class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
          :class="errors.customer_phone ? 'border-red-500' : 'border-slate-300 dark:border-slate-700'"
          placeholder="+51 999 999 999"
        />
        <p v-if="errors.customer_phone" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ errors.customer_phone }}
        </p>
      </div>

      <!-- Customer Country -->
      <div>
        <label for="customer_country" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
          Country *
        </label>
        <select
          id="customer_country"
          v-model="customerCountry"
          class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors appearance-none"
          :class="errors.customer_country ? 'border-red-500' : 'border-slate-300 dark:border-slate-700'"
        >
          <option v-for="country in countries" :key="country.code" :value="country.code">
            {{ country.name }}
          </option>
        </select>
        <p v-if="errors.customer_country" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ errors.customer_country }}
        </p>
      </div>

      <!-- Pickup Location (if available) -->
      <div v-if="pickupAvailable">
        <label for="pickup_location" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
          Pickup Location *
        </label>
        <input
          id="pickup_location"
          v-model="pickupLocation"
          type="text"
          class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
          :class="errors.pickup_location ? 'border-red-500' : 'border-slate-300 dark:border-slate-700'"
          placeholder="Hotel address or meeting point"
        />
        <p v-if="errors.pickup_location" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ errors.pickup_location }}
        </p>
      </div>

      <!-- Customer Notes -->
      <div>
        <label for="customer_notes" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
          Additional Notes (optional)
        </label>
        <textarea
          id="customer_notes"
          v-model="customerNotes"
          rows="3"
          class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors resize-none"
          placeholder="Special requests, dietary restrictions, etc."
        ></textarea>
      </div>

      <!-- Payment Method -->
      <div class="border-t border-slate-200 dark:border-slate-800 pt-4">
        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-3">
          Método de pago
        </label>
        <div class="grid grid-cols-2 gap-3">
          <label
            class="flex flex-col items-center gap-2 p-4 border-2 rounded-xl cursor-pointer transition-all"
            :class="paymentMethod === 'culqi' ? 'border-primary bg-primary/5' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300'"
          >
            <input type="radio" v-model="paymentMethod" value="culqi" class="sr-only" />
            <span class="material-symbols-outlined text-2xl" :class="paymentMethod === 'culqi' ? 'text-primary' : 'text-slate-400'">credit_card</span>
            <span class="text-sm font-bold" :class="paymentMethod === 'culqi' ? 'text-primary' : 'text-slate-700 dark:text-slate-300'">Tarjeta</span>
            <span class="text-[10px] text-slate-500">Visa, Mastercard, Amex</span>
          </label>
          <label
            class="flex flex-col items-center gap-2 p-4 border-2 rounded-xl cursor-pointer transition-all"
            :class="paymentMethod === 'paypal' ? 'border-[#0070ba] bg-[#0070ba]/5' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300'"
          >
            <input type="radio" v-model="paymentMethod" value="paypal" class="sr-only" />
            <svg class="h-7" viewBox="0 0 124 33" xmlns="http://www.w3.org/2000/svg">
              <path d="M46.211 6.749h-6.839a.95.95 0 0 0-.939.802l-2.766 17.537a.57.57 0 0 0 .564.658h3.265a.95.95 0 0 0 .939-.803l.746-4.73a.95.95 0 0 1 .938-.803h2.165c4.505 0 7.105-2.18 7.784-6.5.306-1.89.013-3.375-.872-4.415-.972-1.142-2.696-1.746-4.985-1.746zM47 13.154c-.374 2.454-2.249 2.454-4.062 2.454h-1.032l.724-4.583a.57.57 0 0 1 .563-.481h.473c1.235 0 2.4 0 3.002.704.359.42.469 1.044.332 1.906zM66.654 13.075h-3.275a.57.57 0 0 0-.563.481l-.145.916-.229-.332c-.709-1.029-2.29-1.373-3.868-1.373-3.619 0-6.71 2.741-7.312 6.586-.313 1.918.132 3.752 1.22 5.031.998 1.176 2.426 1.666 4.125 1.666 2.916 0 4.533-1.875 4.533-1.875l-.146.91a.57.57 0 0 0 .562.66h2.95a.95.95 0 0 0 .939-.803l1.77-11.209a.568.568 0 0 0-.561-.658zm-4.565 6.374c-.316 1.871-1.801 3.127-3.695 3.127-.951 0-1.711-.305-2.199-.883-.484-.574-.668-1.391-.514-2.301.295-1.855 1.805-3.152 3.67-3.152.93 0 1.686.309 2.184.892.499.589.697 1.411.554 2.317z" fill="#253B80"/>
              <path d="M84.096 13.075h-3.291a.95.95 0 0 0-.787.417l-4.539 6.686-1.924-6.425a.95.95 0 0 0-.912-.678h-3.234a.57.57 0 0 0-.541.754l3.625 10.638-3.408 4.811a.57.57 0 0 0 .465.9h3.287a.95.95 0 0 0 .781-.408l10.946-15.8a.57.57 0 0 0-.468-.895z" fill="#253B80"/>
              <path d="M94.992 6.749h-6.84a.95.95 0 0 0-.938.802l-2.766 17.537a.569.569 0 0 0 .562.658h3.51a.665.665 0 0 0 .656-.562l.785-4.971a.95.95 0 0 1 .938-.803h2.164c4.506 0 7.105-2.18 7.785-6.5.307-1.89.012-3.375-.873-4.415-.971-1.142-2.694-1.746-4.983-1.746zm.789 6.405c-.373 2.454-2.248 2.454-4.062 2.454h-1.031l.725-4.583a.568.568 0 0 1 .562-.481h.473c1.234 0 2.4 0 3.002.704.359.42.468 1.044.331 1.906zM115.434 13.075h-3.273a.567.567 0 0 0-.562.481l-.145.916-.23-.332c-.709-1.029-2.289-1.373-3.867-1.373-3.619 0-6.709 2.741-7.311 6.586-.312 1.918.131 3.752 1.219 5.031 1 1.176 2.426 1.666 4.125 1.666 2.916 0 4.533-1.875 4.533-1.875l-.146.91a.57.57 0 0 0 .564.66h2.949a.95.95 0 0 0 .938-.803l1.771-11.209a.571.571 0 0 0-.565-.658zm-4.565 6.374c-.314 1.871-1.801 3.127-3.695 3.127-.949 0-1.711-.305-2.199-.883-.484-.574-.666-1.391-.514-2.301.297-1.855 1.805-3.152 3.67-3.152.93 0 1.686.309 2.184.892.501.589.699 1.411.554 2.317zM119.295 7.23l-2.807 17.858a.569.569 0 0 0 .562.658h2.822c.469 0 .867-.34.939-.803l2.768-17.536a.57.57 0 0 0-.562-.659h-3.16a.571.571 0 0 0-.562.482z" fill="#179BD7"/>
              <path d="M7.266 29.154l.523-3.322-1.165-.027H1.061L4.927 1.292a.316.316 0 0 1 .314-.268h9.38c3.114 0 5.263.648 6.385 1.927.526.6.861 1.227 1.023 1.917.17.724.173 1.589.007 2.644l-.012.077v.676l.526.298a3.69 3.69 0 0 1 1.065.812c.45.513.741 1.165.864 1.938.127.795.085 1.741-.123 2.812-.24 1.232-.628 2.305-1.152 3.183a6.547 6.547 0 0 1-1.825 2c-.696.494-1.523.869-2.458 1.109-.906.236-1.939.355-3.072.355h-.73c-.522 0-1.029.188-1.427.525a2.21 2.21 0 0 0-.744 1.328l-.055.299-.924 5.855-.042.215c-.011.068-.03.102-.058.125a.155.155 0 0 1-.096.035H7.266z" fill="#253B80"/>
              <path d="M23.048 7.667c-.028.179-.06.362-.096.55-1.237 6.351-5.469 8.545-10.874 8.545H9.326c-.661 0-1.218.48-1.321 1.132l-1.409 8.936-.399 2.533a.704.704 0 0 0 .695.814h4.881c.578 0 1.069-.42 1.16-.99l.048-.248.919-5.832.059-.32c.09-.572.582-.992 1.16-.992h.73c4.729 0 8.431-1.92 9.513-7.476.452-2.321.218-4.259-.978-5.622a4.667 4.667 0 0 0-1.336-1.03z" fill="#179BD7"/>
              <path d="M21.754 7.151a9.757 9.757 0 0 0-1.203-.267 15.284 15.284 0 0 0-2.426-.177h-7.352a1.172 1.172 0 0 0-1.159.992L8.05 17.605l-.045.289a1.336 1.336 0 0 1 1.321-1.132h2.752c5.405 0 9.637-2.195 10.874-8.545.037-.188.068-.371.096-.55a6.594 6.594 0 0 0-1.017-.429 9.045 9.045 0 0 0-.277-.087z" fill="#222D65"/>
              <path d="M9.614 7.699a1.169 1.169 0 0 1 1.159-.991h7.352c.871 0 1.684.057 2.426.177a9.757 9.757 0 0 1 1.481.353c.365.121.704.264 1.017.429.368-2.347-.003-3.945-1.272-5.392C20.378.682 17.853 0 14.622 0h-9.38c-.66 0-1.223.48-1.325 1.133L.01 25.898a.806.806 0 0 0 .795.932h5.791l1.454-9.225 1.564-9.906z" fill="#253B80"/>
            </svg>
            <span class="text-[10px] text-slate-500">PayPal o tarjeta</span>
          </label>
        </div>
      </div>

      <!-- Terms and Conditions Checkbox -->
      <div class="border-t border-slate-200 dark:border-slate-800 pt-4">
        <label class="flex items-start cursor-pointer">
          <input
            v-model="acceptedTerms"
            type="checkbox"
            class="mt-1 mr-3 w-5 h-5 text-primary border-slate-300 dark:border-slate-600 rounded focus:ring-primary"
          />
          <span class="text-sm text-slate-700 dark:text-slate-300">
            I accept the
            <a
              href="#"
              @click.prevent="viewPolicies"
              class="text-primary hover:text-primary/80 font-semibold underline"
            >
              terms and conditions
            </a>
            and cancellation policies of each tour
          </span>
        </label>
        <p v-if="errors.accepted_terms" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ errors.accepted_terms }}
        </p>
      </div>

      <!-- Submit Button -->
      <button
        type="submit"
        class="w-full font-black py-4 px-6 rounded-xl transition-all shadow-lg flex items-center justify-center gap-2"
        :class="acceptedTerms ? 'bg-primary hover:bg-primary/90 text-white shadow-primary/20' : 'bg-slate-300 dark:bg-slate-700 text-slate-500 dark:text-slate-400 cursor-not-allowed'"
      >
        <span class="material-symbols-outlined">payment</span>
        <span>Continue to Payment</span>
      </button>
    </form>

    <!-- Policies Modal -->
    <Teleport to="body">
      <div
        v-if="showPoliciesModal && toursPolicies.length > 0"
        class="fixed inset-0 bg-black/50 z-[100] flex items-center justify-center p-4"
        @click.self="closePoliciesModal"
      >
        <div class="bg-white dark:bg-slate-900 rounded-xl max-w-3xl w-full max-h-[85vh] overflow-hidden shadow-2xl">
          <div class="p-6 border-b border-slate-200 dark:border-slate-800">
            <div class="flex items-center justify-between">
              <h3 class="text-xl font-black">{{ modalTitle }}</h3>
              <button
                @click="closePoliciesModal"
                class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg"
              >
                <span class="material-symbols-outlined text-2xl">close</span>
              </button>
            </div>
          </div>

          <div class="p-6 overflow-y-auto max-h-[65vh]">
            <div v-for="(tour, index) in toursPolicies" :key="index" class="mb-6 last:mb-0">
              <!-- Show tour title if multiple tours -->
              <div v-if="toursPolicies.length > 1" class="mb-4 pb-2 border-b border-slate-200 dark:border-slate-800">
                <h4 class="text-lg font-bold text-primary">{{ tour.title }}</h4>
              </div>

              <div class="space-y-4">
                <div>
                  <h5 class="font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                    <span class="material-symbols-outlined text-blue-500">description</span>
                    General Policies
                  </h5>
                  <div class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                    {{ tour.policies }}
                  </div>
                </div>

                <div>
                  <h5 class="font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                    <span class="material-symbols-outlined text-red-500">report</span>
                    Cancellation Policy
                  </h5>
                  <div class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800">
                    {{ tour.cancellationPolicy }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="p-6 border-t border-slate-200 dark:border-slate-800">
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
