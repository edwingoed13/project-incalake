<template>
  <div class="min-h-screen bg-slate-50 pt-20 md:pt-24 pb-8">
    <div class="max-w-3xl mx-auto px-3 sm:px-4">

      <!-- Error -->
      <div v-if="error" class="bg-white rounded-2xl shadow-sm p-6 text-center mt-4">
        <span class="material-symbols-outlined text-red-400 text-4xl mb-3 block">error</span>
        <h2 class="text-base font-bold text-slate-800 mb-1">{{ t('booking_not_found') }}</h2>
        <p class="text-sm text-slate-500">{{ errorMessage }}</p>
      </div>

      <!-- Loading -->
      <div v-else-if="pending" class="flex justify-center py-20">
        <div class="size-10 border-4 border-primary/20 border-t-primary rounded-full animate-spin"></div>
      </div>

      <!-- Content -->
      <div v-else-if="booking">
        <!-- Success Header -->
        <div class="text-center mb-5 md:mb-8">
          <div class="inline-flex items-center justify-center size-12 md:size-16 bg-green-100 rounded-full mb-2">
            <span class="material-symbols-outlined text-green-600 text-3xl md:text-4xl">check_circle</span>
          </div>
          <h1 class="text-lg md:text-2xl font-black text-slate-800">{{ t('booking_confirmed') }}</h1>
          <p class="text-xs text-slate-500 mt-0.5">{{ t('code') }}: <span class="font-mono font-bold text-primary">{{ booking.booking_code }}</span></p>
        </div>

        <!-- Step Indicator -->
        <div class="flex items-center justify-between mb-5 md:mb-8 px-2">
          <template v-for="(s, idx) in steps" :key="idx">
            <button
              @click="currentStep = idx"
              class="flex flex-col items-center gap-1 group"
            >
              <div
                class="size-8 md:size-9 rounded-full flex items-center justify-center text-xs font-bold transition-all"
                :class="idx === currentStep
                  ? 'bg-primary text-white shadow-md'
                  : completedSteps.has(idx)
                    ? 'bg-green-100 text-green-700'
                    : 'bg-slate-100 text-slate-400'"
              >
                <span v-if="completedSteps.has(idx)" class="material-symbols-outlined text-sm">check</span>
                <span v-else>{{ idx + 1 }}</span>
              </div>
              <span
                class="text-[10px] font-semibold leading-tight text-center"
                :class="idx === currentStep ? 'text-primary' : completedSteps.has(idx) ? 'text-green-600' : 'text-slate-400'"
              >{{ s.label }}</span>
            </button>
            <!-- Connector line -->
            <div
              v-if="idx < steps.length - 1"
              class="flex-1 h-px mb-4"
              :class="completedSteps.has(idx) ? 'bg-green-300' : 'bg-slate-200'"
            ></div>
          </template>
        </div>

        <!-- Step 0: Booking Summary -->
        <div v-if="currentStep === 0" class="space-y-3">
          <!-- Tour card preview -->
          <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 p-3 md:p-4 border-b border-slate-50">
              <div class="size-14 md:size-16 rounded-lg overflow-hidden flex-shrink-0 bg-slate-100">
                <img
                  v-if="booking.tour?.featured_image"
                  :src="getImageUrl(booking.tour.featured_image)"
                  :alt="booking.tour?.title"
                  class="w-full h-full object-cover"
                />
              </div>
              <div class="flex-1 min-w-0">
                <h3 class="text-sm font-bold text-slate-800 leading-tight line-clamp-2">{{ booking.tour?.title }}</h3>
                <p class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-1">
                  <span class="material-symbols-outlined text-xs">calendar_today</span>
                  {{ formatDate(booking.tour_date) }}
                </p>
              </div>
            </div>

            <!-- Details grid -->
            <div class="grid grid-cols-3 divide-x divide-slate-100">
              <div class="p-3 text-center">
                <p class="text-[10px] text-slate-400 font-semibold uppercase">{{ t('travelers') }}</p>
                <p class="text-sm font-bold text-slate-800 mt-0.5">{{ booking.participants?.adults || 0 }}</p>
              </div>
              <div class="p-3 text-center">
                <p class="text-[10px] text-slate-400 font-semibold uppercase">{{ t('total') }}</p>
                <p class="text-sm font-bold text-primary mt-0.5">${{ (booking.pricing?.total || 0).toFixed(2) }}</p>
              </div>
              <div class="p-3 text-center">
                <p class="text-[10px] text-slate-400 font-semibold uppercase">Status</p>
                <p class="text-sm font-bold text-green-600 mt-0.5">{{ t('status_paid') }}</p>
              </div>
            </div>
          </div>

          <!-- Customer info (collapsible on mobile) -->
          <details class="bg-white rounded-xl border border-slate-100 shadow-sm group">
            <summary class="flex items-center justify-between p-3 md:p-4 cursor-pointer list-none">
              <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-lg">person</span>
                <span class="text-sm font-bold text-slate-800">{{ t('customer_info') }}</span>
              </div>
              <span class="material-symbols-outlined text-slate-400 text-lg transition-transform group-open:rotate-180">expand_more</span>
            </summary>
            <div class="px-3 md:px-4 pb-3 md:pb-4 space-y-2 text-sm border-t border-slate-50 pt-3">
              <div class="flex justify-between">
                <span class="text-slate-500">{{ t('name') }}</span>
                <span class="font-semibold text-slate-800">{{ booking.customer?.name }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">{{ t('email') }}</span>
                <span class="font-semibold text-slate-800 text-xs break-all">{{ booking.customer?.email }}</span>
              </div>
              <div v-if="booking.customer?.phone" class="flex justify-between">
                <span class="text-slate-500">{{ t('phone') }}</span>
                <span class="font-semibold text-slate-800">{{ booking.customer?.phone }}</span>
              </div>
            </div>
          </details>

          <!-- Quick actions - icon buttons on mobile -->
          <div class="grid grid-cols-3 gap-2">
            <button @click="downloadPDF" class="flex flex-col items-center gap-1 p-2.5 bg-white border border-slate-200 rounded-xl text-slate-600 active:bg-slate-50 transition-colors">
              <span class="material-symbols-outlined text-lg">download</span>
              <span class="text-[10px] font-semibold">{{ t('voucher') }}</span>
            </button>
            <button @click="shareBooking" class="flex flex-col items-center gap-1 p-2.5 bg-white border border-slate-200 rounded-xl text-slate-600 active:bg-slate-50 transition-colors">
              <span class="material-symbols-outlined text-lg">share</span>
              <span class="text-[10px] font-semibold">{{ t('share') }}</span>
            </button>
            <button @click="sendEmailCopy" :disabled="emailSending" class="flex flex-col items-center gap-1 p-2.5 bg-white border border-slate-200 rounded-xl text-slate-600 active:bg-slate-50 transition-colors disabled:opacity-50">
              <span class="material-symbols-outlined text-lg">{{ emailSending ? 'progress_activity' : 'mail' }}</span>
              <span class="text-[10px] font-semibold">{{ emailSending ? t('sending') : t('send_email') }}</span>
            </button>
          </div>

          <button @click="currentStep = 1" class="w-full bg-primary active:bg-primary/80 text-white py-3 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2 active:scale-[0.98]">
            {{ t('continue_pickup') }}
            <span class="material-symbols-outlined text-lg">arrow_forward</span>
          </button>
        </div>

        <!-- Step 1: Pickup Configuration -->
        <div v-else-if="currentStep === 1">
          <BookingPickupConfiguration
            :booking-id="booking.id"
            @completed="onPickupCompleted"
            @error="(msg: string) => console.error(msg)"
          />
          <div class="flex gap-2 mt-3">
            <button @click="currentStep = 0" class="flex-1 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-600 active:bg-slate-50">{{ t('back') }}</button>
            <button @click="skipStep(1)" class="flex-1 py-2.5 bg-slate-100 rounded-xl text-sm font-semibold text-slate-500 active:bg-slate-200">{{ t('skip_for_now') }}</button>
          </div>
        </div>

        <!-- Step 2: Traveler Details -->
        <div v-else-if="currentStep === 2">
          <div class="bg-white rounded-xl border border-slate-100 shadow-sm">
            <div class="flex items-center justify-between p-3 md:p-4 border-b border-slate-50">
              <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-lg">group</span>
                {{ t('step_travelers') }}
                <span class="text-xs font-normal text-slate-400">({{ travelers.length }})</span>
              </h3>
              <button
                @click="addTraveler"
                class="flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary text-[11px] font-bold rounded-lg active:bg-primary/20"
              >
                <span class="material-symbols-outlined text-sm">add</span>
                {{ t('add_traveler') }}
              </button>
            </div>

            <div class="p-3 md:p-4 space-y-3">
              <div v-for="(traveler, idx) in travelers" :key="idx" class="p-3 bg-slate-50 rounded-xl">
                <div class="flex items-center justify-between mb-2.5">
                  <p class="text-[11px] font-bold uppercase" :class="traveler.is_leader ? 'text-primary' : 'text-slate-400'">
                    {{ traveler.is_leader ? t('leader') : `Traveler ${idx + 1}` }}
                    <span v-if="traveler.age_group === 'child'" class="ml-1 text-amber-500">(Child)</span>
                  </p>
                  <button
                    v-if="!traveler.is_leader && travelers.length > 1"
                    @click="removeTraveler(idx)"
                    class="p-1 text-slate-400 active:text-red-500"
                  >
                    <span class="material-symbols-outlined text-sm">close</span>
                  </button>
                </div>

                <!-- Mobile: stack fields, Desktop: 2 cols -->
                <div class="space-y-2 md:grid md:grid-cols-2 md:gap-3 md:space-y-0">
                  <div class="md:col-span-2">
                    <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1 block">{{ t('full_name') }} *</label>
                    <input v-model="traveler.full_name" type="text" placeholder="Full name" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                  </div>
                  <div>
                    <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1 block">{{ t('nationality') }}</label>
                    <input v-model="traveler.nationality" type="text" placeholder="e.g. American" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 text-sm" />
                  </div>
                  <div>
                    <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1 block">{{ t('doc_type') }}</label>
                    <select v-model="traveler.doc_type" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 text-sm">
                      <option value="passport">Passport</option>
                      <option value="dni">DNI / ID Card</option>
                    </select>
                  </div>
                  <div>
                    <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1 block">{{ t('doc_number') }}</label>
                    <input v-model="traveler.doc_number" type="text" placeholder="Document #" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 text-sm" />
                  </div>
                  <div>
                    <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1 block">{{ t('special_needs') }}</label>
                    <input v-model="traveler.special_needs" type="text" placeholder="Optional" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 text-sm" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="travelerError" class="mt-2 bg-red-50 border border-red-200 rounded-xl p-3">
            <p class="text-red-700 text-sm">{{ travelerError }}</p>
          </div>

          <div class="flex gap-2 mt-3">
            <button @click="currentStep = 1" class="flex-1 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-600 active:bg-slate-50">{{ t('back') }}</button>
            <button @click="saveTravelers" :disabled="savingTravelers" class="flex-1 py-2.5 bg-primary text-white rounded-xl text-sm font-bold disabled:opacity-50 flex items-center justify-center gap-2 active:bg-primary/80 active:scale-[0.98] transition-transform">
              <span v-if="savingTravelers" class="material-symbols-outlined animate-spin text-base">progress_activity</span>
              {{ savingTravelers ? t('saving') : t('save_continue') }}
            </button>
          </div>
        </div>

        <!-- Step 3: Complete -->
        <div v-else-if="currentStep === 3" class="text-center">
          <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 md:p-8">
            <div class="inline-flex items-center justify-center size-14 bg-green-100 rounded-full mb-3">
              <span class="material-symbols-outlined text-green-600 text-3xl">celebration</span>
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-1">{{ t('all_set') }}</h3>
            <p class="text-xs text-slate-500 mb-5">We'll send you a confirmation email with all the details.<br />See you on {{ formatDate(booking.tour_date) }}!</p>

            <div class="flex flex-col gap-2">
              <a
                :href="`https://wa.me/51982769453?text=Hi! My booking code is ${booking.booking_code}`"
                target="_blank"
                class="flex items-center justify-center gap-2 py-3 bg-green-500 text-white rounded-xl font-bold text-sm active:bg-green-600 transition-colors"
              >
                <span class="material-symbols-outlined text-lg">chat</span>
                {{ t('whatsapp_contact') }}
              </a>
              <NuxtLink to="/" class="flex items-center justify-center gap-2 py-3 bg-slate-100 rounded-xl font-semibold text-sm text-slate-600 active:bg-slate-200 transition-colors">
                <span class="material-symbols-outlined text-lg">home</span>
                {{ t('back_home') }}
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const { t } = useI18n()
const route = useRoute()
const { api } = useApi()
const config = useRuntimeConfig()

const bookingCode = route.params.bookingCode as string
const email = route.query.email as string
const token = route.query.token as string

const currentStep = ref(0)
const completedSteps = ref(new Set<number>())
const emailSending = ref(false)

const steps = computed(() => [
  { label: t('step_summary') },
  { label: t('step_pickup') },
  { label: t('step_travelers') },
  { label: t('step_complete') },
])

// Fetch booking
const { data: response, pending, error } = await useAsyncData(
  `booking-${bookingCode}`,
  () => {
    if (token) {
      return api(`/bookings/token/${token}`)
    }
    const params = email ? `?email=${encodeURIComponent(email)}` : ''
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
      travelers.value = data.travelers.map((tr: any) => ({
        full_name: tr.full_name || '',
        nationality: tr.nationality || '',
        doc_type: tr.doc_type || 'passport',
        doc_number: tr.doc_number || '',
        age_group: tr.age_group || 'adult',
        special_needs: tr.special_needs || '',
        is_leader: tr.is_leader || false,
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

function getImageUrl(path: string) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${config.public.storageBase}/${path}`
}

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
    travelerError.value = t('leader_required')
    return
  }
  // Filter out empty names
  const validTravelers = travelers.value.filter(tr => tr.full_name?.trim())
  if (validTravelers.length === 0) {
    travelerError.value = t('traveler_required')
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
    travelerError.value = t('error_saving')
  } finally {
    savingTravelers.value = false
  }
}

async function downloadPDF() {
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
