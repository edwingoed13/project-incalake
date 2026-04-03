<template>
  <div class="min-h-screen bg-slate-50 pt-24 pb-12">
    <div class="max-w-3xl mx-auto px-4">

      <!-- Error -->
      <div v-if="error" class="bg-white rounded-2xl shadow-sm p-8 text-center">
        <span class="material-symbols-outlined text-red-400 text-5xl mb-4">error</span>
        <h2 class="text-lg font-bold text-slate-800 mb-2">Booking Not Found</h2>
        <p class="text-sm text-slate-500">{{ errorMessage }}</p>
      </div>

      <!-- Loading -->
      <div v-else-if="pending" class="flex justify-center py-20">
        <div class="size-12 border-4 border-primary/20 border-t-primary rounded-full animate-spin"></div>
      </div>

      <!-- Content -->
      <div v-else-if="booking">
        <!-- Success Header -->
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center size-16 bg-green-100 rounded-full mb-3">
            <span class="material-symbols-outlined text-green-600 text-4xl">check_circle</span>
          </div>
          <h1 class="text-2xl font-black text-slate-800">Booking Confirmed!</h1>
          <p class="text-sm text-slate-500 mt-1">Code: <span class="font-mono font-bold text-primary">{{ booking.booking_code }}</span></p>
        </div>

        <!-- Step Indicator -->
        <div class="flex items-center justify-center gap-2 mb-8">
          <button
            v-for="(s, idx) in steps"
            :key="idx"
            @click="currentStep = idx"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold transition-all"
            :class="idx === currentStep
              ? 'bg-primary text-white shadow-md'
              : idx < currentStep || completedSteps.has(idx)
                ? 'bg-green-100 text-green-700'
                : 'bg-slate-100 text-slate-400'"
          >
            <span v-if="completedSteps.has(idx)" class="material-symbols-outlined text-xs">check</span>
            <span>{{ idx + 1 }}. {{ s.label }}</span>
          </button>
        </div>

        <!-- Step 0: Booking Summary -->
        <div v-if="currentStep === 0" class="space-y-4">
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <h3 class="text-base font-bold text-slate-800 mb-4 flex items-center gap-2">
              <span class="material-symbols-outlined text-primary">info</span>
              Booking Details
            </h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <p class="text-xs text-slate-400 font-semibold uppercase">Tour</p>
                <p class="font-bold text-slate-800">{{ booking.tour?.title }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-400 font-semibold uppercase">Date</p>
                <p class="font-semibold text-slate-700">{{ formatDate(booking.tour_date) }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-400 font-semibold uppercase">Travelers</p>
                <p class="font-semibold text-slate-700">{{ booking.participants?.adults || 0 }} adults</p>
              </div>
              <div>
                <p class="text-xs text-slate-400 font-semibold uppercase">Total Paid</p>
                <p class="font-bold text-primary">${{ (booking.pricing?.total || 0).toFixed(2) }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-400 font-semibold uppercase">Customer</p>
                <p class="font-semibold text-slate-700">{{ booking.customer?.name }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-400 font-semibold uppercase">Email</p>
                <p class="font-semibold text-slate-700">{{ booking.customer?.email }}</p>
              </div>
            </div>
          </div>

          <!-- Quick actions -->
          <div class="flex gap-2">
            <button @click="downloadPDF" class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50">
              <span class="material-symbols-outlined text-base">download</span> Voucher
            </button>
            <button @click="shareBooking" class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50">
              <span class="material-symbols-outlined text-base">share</span> Share
            </button>
            <button @click="sendEmailCopy" :disabled="emailSending" class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50">
              <span class="material-symbols-outlined text-base">mail</span> {{ emailSending ? 'Sending...' : 'Email' }}
            </button>
          </div>

          <button @click="currentStep = 1" class="w-full bg-primary text-white py-3 rounded-xl font-bold text-sm hover:brightness-110 transition-all flex items-center justify-center gap-2">
            Continue: Configure Pickup
            <span class="material-symbols-outlined text-lg">arrow_forward</span>
          </button>
        </div>

        <!-- Step 1: Pickup Configuration -->
        <div v-else-if="currentStep === 1">
          <BookingPickupConfiguration
            :booking-id="booking.id"
            @completed="onPickupCompleted"
            @error="(msg) => console.error(msg)"
          />
          <div class="flex gap-3 mt-4">
            <button @click="currentStep = 0" class="flex-1 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-50">Back</button>
            <button @click="skipStep(1)" class="flex-1 py-2.5 bg-slate-100 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-200">Skip for now</button>
          </div>
        </div>

        <!-- Step 2: Traveler Details -->
        <div v-else-if="currentStep === 2">
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">group</span>
                Traveler Details
                <span class="text-xs font-normal text-slate-400">({{ travelers.length }})</span>
              </h3>
              <button
                @click="addTraveler"
                class="flex items-center gap-1 px-3 py-1.5 bg-primary/10 text-primary text-xs font-bold rounded-lg hover:bg-primary/20 transition-colors"
              >
                <span class="material-symbols-outlined text-sm">add</span>
                Add Traveler
              </button>
            </div>
            <p class="text-xs text-slate-500 mb-4">Provide details for each traveler. You can add companions not included in the original booking.</p>

            <div v-for="(traveler, idx) in travelers" :key="idx" class="mb-3 p-4 bg-slate-50 rounded-xl relative">
              <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-bold uppercase" :class="traveler.is_leader ? 'text-primary' : 'text-slate-400'">
                  {{ traveler.is_leader ? 'Leader' : `Traveler ${idx + 1}` }}
                  <span v-if="traveler.age_group === 'child'" class="ml-1 text-amber-500">(Child)</span>
                  <span v-if="traveler.age_group === 'infant'" class="ml-1 text-amber-500">(Infant)</span>
                </p>
                <button
                  v-if="!traveler.is_leader && travelers.length > 1"
                  @click="removeTraveler(idx)"
                  class="p-1 text-slate-400 hover:text-red-500 transition-colors"
                  title="Remove"
                >
                  <span class="material-symbols-outlined text-sm">close</span>
                </button>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1 block">Full Name *</label>
                  <input v-model="traveler.full_name" type="text" placeholder="Full name" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm" />
                </div>
                <div>
                  <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1 block">Nationality</label>
                  <input v-model="traveler.nationality" type="text" placeholder="e.g. American" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm" />
                </div>
                <div>
                  <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1 block">Document Type</label>
                  <select v-model="traveler.doc_type" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">
                    <option value="passport">Passport</option>
                    <option value="dni">DNI / ID Card</option>
                  </select>
                </div>
                <div>
                  <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1 block">Document Number</label>
                  <input v-model="traveler.doc_number" type="text" placeholder="Document #" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm" />
                </div>
                <div class="col-span-2">
                  <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1 block">Special Needs / Allergies</label>
                  <input v-model="traveler.special_needs" type="text" placeholder="Optional" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm" />
                </div>
              </div>
            </div>
          </div>

          <div v-if="travelerError" class="mt-2 bg-red-50 border border-red-200 rounded-xl p-3">
            <p class="text-red-700 text-sm">{{ travelerError }}</p>
          </div>

          <div class="flex gap-3 mt-4">
            <button @click="currentStep = 1" class="flex-1 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-50">Back</button>
            <button @click="saveTravelers" :disabled="savingTravelers" class="flex-1 py-2.5 bg-primary text-white rounded-xl text-sm font-bold hover:brightness-110 disabled:opacity-50 flex items-center justify-center gap-2">
              <span v-if="savingTravelers" class="material-symbols-outlined animate-spin text-base">progress_activity</span>
              {{ savingTravelers ? 'Saving...' : 'Save & Continue' }}
            </button>
          </div>
        </div>

        <!-- Step 3: Complete -->
        <div v-else-if="currentStep === 3" class="text-center">
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-8">
            <div class="inline-flex items-center justify-center size-16 bg-green-100 rounded-full mb-4">
              <span class="material-symbols-outlined text-green-600 text-4xl">celebration</span>
            </div>
            <h3 class="text-xl font-bold text-slate-800 mb-2">You're all set!</h3>
            <p class="text-sm text-slate-500 mb-6">We'll send you a confirmation email with all the details. See you on {{ formatDate(booking.tour_date) }}!</p>

            <div class="flex flex-col gap-2">
              <a
                :href="`https://wa.me/51999999999?text=Hi! My booking code is ${booking.booking_code}`"
                target="_blank"
                class="flex items-center justify-center gap-2 py-3 bg-green-500 text-white rounded-xl font-bold text-sm hover:bg-green-600 transition-colors"
              >
                <span class="material-symbols-outlined text-lg">chat</span>
                Contact us on WhatsApp
              </a>
              <NuxtLink to="/" class="flex items-center justify-center gap-2 py-3 bg-slate-100 rounded-xl font-semibold text-sm text-slate-600 hover:bg-slate-200 transition-colors">
                <span class="material-symbols-outlined text-lg">home</span>
                Back to Home
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const { api } = useApi()

const bookingCode = route.params.bookingCode as string
const email = route.query.email as string
const token = route.query.token as string

const currentStep = ref(0)
const completedSteps = ref(new Set<number>())
const emailSending = ref(false)

const steps = [
  { label: 'Summary' },
  { label: 'Pickup' },
  { label: 'Travelers' },
  { label: 'Complete' },
]

// Fetch booking
const { data: response, pending, error } = await useAsyncData(
  `booking-${bookingCode}`,
  () => {
    const params = token ? `?token=${token}` : email ? `?email=${encodeURIComponent(email)}` : ''
    return api(`/bookings/${bookingCode}${params}`)
  }
)

const booking = computed(() => (response.value as any)?.booking || null)
const errorMessage = computed(() => (error.value as any)?.data?.message || 'Invalid or expired link')

// Travelers form
const travelers = ref<any[]>([])
const savingTravelers = ref(false)
const travelerError = ref<string | null>(null)

// Load existing data when booking is available
watch(booking, async (b) => {
  if (!b) return

  // Load full details (travelers + pickup)
  try {
    const details = await api(`/bookings/${b.id}/full-details`)
    const data = (details as any)?.data || details

    // Load existing travelers
    if (data?.travelers?.length) {
      travelers.value = data.travelers.map((t: any) => ({
        full_name: t.full_name || '',
        nationality: t.nationality || '',
        doc_type: t.doc_type || 'passport',
        doc_number: t.doc_number || '',
        age_group: t.age_group || 'adult',
        special_needs: t.special_needs || '',
        is_leader: t.is_leader || false,
      }))
      completedSteps.value.add(2)
    } else {
      // Generate empty travelers from booking participants
      const adults = b.participants?.adults || 1
      const children = b.participants?.children || 0
      travelers.value = Array.from({ length: adults + children }, (_, i) => ({
        full_name: i === 0 ? b.customer?.name || '' : '',
        nationality: '',
        doc_type: 'passport',
        doc_number: '',
        age_group: i < adults ? 'adult' : 'child',
        special_needs: '',
        is_leader: i === 0,
      }))
    }

    // Check if pickup was already configured
    if (data?.pickup_detail) {
      completedSteps.value.add(1)
    }

    // If all steps done, go to complete
    if (completedSteps.value.has(1) && completedSteps.value.has(2)) {
      currentStep.value = 3
    }
  } catch (e) {
    // Fallback: generate empty travelers
    const adults = b.participants?.adults || 1
    const children = b.participants?.children || 0
    travelers.value = Array.from({ length: adults + children }, (_, i) => ({
      full_name: i === 0 ? b.customer?.name || '' : '',
      nationality: '',
      doc_type: 'passport',
      doc_number: '',
      age_group: i < adults ? 'adult' : 'child',
      special_needs: '',
      is_leader: i === 0,
    }))
  }
}, { immediate: true })

useHead({
  title: 'Booking Confirmation',
  meta: [{ name: 'robots', content: 'noindex, nofollow' }]
})

function formatDate(dateString: string) {
  if (!dateString) return ''
  const [y, m, d] = dateString.split('-').map(Number)
  return new Date(y, m - 1, d).toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })
}

function onPickupCompleted(data: any) {
  completedSteps.value.add(1)
  currentStep.value = 2
}

function skipStep(step: number) {
  currentStep.value = step + 1
}

function addTraveler() {
  travelers.value.push({
    full_name: '',
    nationality: '',
    doc_type: 'passport',
    doc_number: '',
    age_group: 'adult',
    special_needs: '',
    is_leader: false,
  })
}

function removeTraveler(idx: number) {
  if (travelers.value.length > 1 && !travelers.value[idx].is_leader) {
    travelers.value.splice(idx, 1)
  }
}

async function saveTravelers() {
  // Validate at least leader has a name
  if (!travelers.value[0]?.full_name) {
    travelerError.value = 'Leader name is required'
    return
  }
  // Filter out empty names
  const validTravelers = travelers.value.filter(t => t.full_name?.trim())
  if (validTravelers.length === 0) {
    travelerError.value = 'At least one traveler is required'
    return
  }

  savingTravelers.value = true
  travelerError.value = null
  try {
    await api(`/bookings/${booking.value.id}/travelers`, {
      method: 'POST',
      body: { travelers: validTravelers }
    })
    completedSteps.value.add(2)
    currentStep.value = 3
  } catch (e: any) {
    travelerError.value = 'Error saving travelers. Please try again.'
  } finally {
    savingTravelers.value = false
  }
}

async function downloadPDF() {
  const config = useRuntimeConfig()
  window.open(`${config.public.apiBase}/bookings/${booking.value.booking_code}/pdf`, '_blank')
}

async function shareBooking() {
  try {
    if (navigator.share) {
      await navigator.share({ title: `Booking ${booking.value.booking_code}`, url: window.location.href })
    } else {
      await navigator.clipboard.writeText(window.location.href)
      alert('Link copied!')
    }
  } catch (e) {}
}

async function sendEmailCopy() {
  emailSending.value = true
  try {
    await api(`/bookings/${booking.value.booking_code}/resend-email`, { method: 'POST' })
    alert('Email sent!')
  } catch (e) { alert('Error sending email') }
  finally { emailSending.value = false }
}
</script>

<style>
@media print { nav, footer, button { display: none !important; } }
</style>
