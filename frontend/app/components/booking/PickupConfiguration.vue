<template>
  <div class="max-w-2xl mx-auto">
    <!-- Loading -->
    <div v-if="isLoading" class="flex justify-center py-12">
      <div class="size-10 border-4 border-primary/20 border-t-primary rounded-full animate-spin"></div>
    </div>

    <div v-else class="space-y-5">
      <!-- Hotel Pickup -->
      <div v-if="tourConfig.enable_hotel_pickup" class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100">
          <h4 class="text-base font-bold text-slate-800 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">hotel</span>
            Hotel Pickup
          </h4>
          <p class="text-xs text-slate-500 mt-1">Search your hotel to check if pickup is included</p>
        </div>

        <div class="p-5 space-y-4">
          <!-- Hotel Search -->
          <div>
            <label class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 block">Search your hotel</label>
            <div class="relative">
              <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
              <input
                ref="hotelSearchInput"
                type="text"
                class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                placeholder="Hotel name..."
                :disabled="isValidating"
              />
            </div>
          </div>

          <!-- Map -->
          <div ref="mapContainer" class="w-full h-64 sm:h-80 rounded-xl border border-slate-200 bg-slate-100"></div>

          <!-- Within Radius Result -->
          <div v-if="hotelValidation && isWithinRadius" class="bg-green-50 border border-green-200 rounded-xl p-4">
            <div class="flex items-start gap-3">
              <span class="material-symbols-outlined text-green-600 text-xl">check_circle</span>
              <div class="flex-1">
                <h5 class="text-sm font-bold text-green-800">Pickup Included!</h5>
                <p class="text-xs text-green-700 mt-0.5">Your hotel is within the free pickup area</p>
                <p class="text-xs text-green-600 mt-1 font-medium">{{ hotelValidation.hotel_name }}</p>
              </div>
            </div>
            <button
              @click="selectHotelPickup"
              :disabled="isSaving"
              class="w-full mt-3 bg-green-600 hover:bg-green-700 text-white py-2.5 rounded-xl text-sm font-bold transition-colors disabled:opacity-50"
            >
              {{ isSaving ? 'Saving...' : 'Confirm Hotel Pickup' }}
            </button>
          </div>

          <!-- Outside Radius Result -->
          <div v-else-if="hotelValidation && !isWithinRadius" class="bg-amber-50 border border-amber-200 rounded-xl p-4">
            <div class="flex items-start gap-3">
              <span class="material-symbols-outlined text-amber-600 text-xl">warning</span>
              <div class="flex-1">
                <h5 class="text-sm font-bold text-amber-800">Outside Free Pickup Area</h5>
                <p class="text-xs text-amber-700 mt-0.5">{{ hotelValidation.distance?.toFixed(1) }} km from included area</p>
              </div>
            </div>

            <div class="mt-4 space-y-2">
              <p class="text-xs font-bold text-slate-700">Choose an option:</p>

              <!-- Pay Extra -->
              <label class="block cursor-pointer">
                <div
                  class="border-2 rounded-xl p-3 transition-all"
                  :class="pickupChoice === 'hotel_pickup' ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-slate-300'"
                  @click="pickupChoice = 'hotel_pickup'"
                >
                  <div class="flex items-center gap-3">
                    <input type="radio" v-model="pickupChoice" value="hotel_pickup" class="text-primary focus:ring-primary" />
                    <div class="flex-1">
                      <div class="flex justify-between">
                        <span class="text-sm font-semibold">Hotel pickup (extra charge)</span>
                        <span class="text-primary font-bold text-sm">+${{ extraCost }}</span>
                      </div>
                      <p class="text-[10px] text-slate-500 mt-0.5">{{ requiresApproval ? 'Subject to confirmation' : 'Instant confirmation' }}</p>
                    </div>
                  </div>
                </div>
              </label>

              <!-- Meeting Point -->
              <label v-if="tourConfig.enable_meeting_point" class="block cursor-pointer">
                <div
                  class="border-2 rounded-xl p-3 transition-all"
                  :class="pickupChoice === 'meeting_point' ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-slate-300'"
                  @click="pickupChoice = 'meeting_point'"
                >
                  <div class="flex items-center gap-3">
                    <input type="radio" v-model="pickupChoice" value="meeting_point" class="text-primary focus:ring-primary" />
                    <div class="flex-1">
                      <span class="text-sm font-semibold">Meeting point (free)</span>
                      <p class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ tourConfig.meeting_point_description }}</p>
                      <button @click.stop.prevent="showMeetingPointOnMap" class="text-primary text-xs font-semibold mt-1">View on map</button>
                    </div>
                  </div>
                </div>
              </label>

              <!-- WhatsApp option -->
              <a
                :href="`https://wa.me/51999999999?text=Hi, I need pickup from ${hotelValidation?.hotel_name} for booking ${bookingId}`"
                target="_blank"
                class="flex items-center gap-2 px-3 py-2 bg-green-500/10 text-green-700 rounded-xl text-xs font-semibold hover:bg-green-500/20 transition-colors"
              >
                <span class="material-symbols-outlined text-base">chat</span>
                Contact us via WhatsApp for special arrangements
              </a>
            </div>

            <button
              @click="saveConfig"
              :disabled="!pickupChoice || isSaving"
              class="w-full mt-4 bg-primary hover:brightness-110 text-white py-2.5 rounded-xl text-sm font-bold transition-all disabled:opacity-50"
            >
              {{ isSaving ? 'Saving...' : 'Confirm Selection' }}
            </button>
          </div>

          <!-- Error -->
          <div v-if="validationError" class="bg-red-50 border border-red-200 rounded-xl p-3">
            <p class="text-red-700 text-sm">{{ validationError }}</p>
          </div>
        </div>
      </div>

      <!-- Meeting Point Only -->
      <div v-else-if="tourConfig.enable_meeting_point" class="bg-white rounded-2xl border border-slate-200 p-5">
        <h4 class="text-base font-bold text-slate-800 flex items-center gap-2 mb-4">
          <span class="material-symbols-outlined text-primary">location_on</span>
          Meeting Point
        </h4>
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
          <p class="text-blue-800 text-sm font-semibold">{{ tourConfig.meeting_point_description }}</p>
        </div>
        <div ref="meetingMapContainer" class="w-full h-48 sm:h-64 rounded-xl border border-slate-200 mt-3"></div>
        <button
          @click="confirmMeetingPoint"
          :disabled="isSaving"
          class="w-full mt-4 bg-primary text-white py-2.5 rounded-xl text-sm font-bold hover:brightness-110 transition-all disabled:opacity-50"
        >
          {{ isSaving ? 'Saving...' : 'Confirm Meeting Point' }}
        </button>
      </div>

      <!-- No options -->
      <div v-else class="bg-slate-50 rounded-2xl p-6 text-center">
        <span class="material-symbols-outlined text-slate-300 text-4xl mb-2">info</span>
        <p class="text-sm text-slate-500">No pickup options configured for this tour. We will contact you with details.</p>
        <button @click="emit('completed', {})" class="mt-4 bg-primary text-white px-6 py-2.5 rounded-xl text-sm font-bold">Continue</button>
      </div>

      <div v-if="saveError" class="bg-red-50 border border-red-200 rounded-xl p-3">
        <p class="text-red-700 text-sm">{{ saveError }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useHotelPickupValidation } from '~/composables/useHotelPickupValidation'

const props = defineProps<{ bookingId: number | string }>()
const emit = defineEmits<{ completed: [data: any]; error: [msg: string] }>()

const { api } = useApi()
const hotelSearchInput = ref<HTMLInputElement | null>(null)
const mapContainer = ref<HTMLElement | null>(null)
const meetingMapContainer = ref<HTMLElement | null>(null)
const isLoading = ref(true)
const tourConfig = ref<any>({})
const bookingIdRef = computed(() => props.bookingId)

const {
  hotelValidation, pickupChoice, isValidating, validationError,
  isSaving, saveError, isWithinRadius, extraCost, requiresApproval,
  initializeHotelSearch, savePickupConfiguration, showMeetingPointOnMap, cleanup
} = useHotelPickupValidation(bookingIdRef, tourConfig)

async function loadPickupDetails() {
  try {
    const res = await api(`/bookings/${props.bookingId}/pickup-details`)
    const data = (res as any)?.data || res
    tourConfig.value = data.tour_config || {}
    setTimeout(() => {
      if (tourConfig.value.enable_hotel_pickup && hotelSearchInput.value && mapContainer.value) {
        initializeHotelSearch(hotelSearchInput.value, mapContainer.value)
      } else if (tourConfig.value.enable_meeting_point && meetingMapContainer.value) {
        initMeetingPointMap()
      }
    }, 200)
  } catch (e: any) {
    emit('error', 'Error loading pickup details')
  } finally {
    isLoading.value = false
  }
}

function initMeetingPointMap() {
  if (!meetingMapContainer.value || !window.google) return
  const lat = parseFloat(tourConfig.value.meeting_point_lat) || -15.8402
  const lng = parseFloat(tourConfig.value.meeting_point_lng) || -70.0219
  const map = new google.maps.Map(meetingMapContainer.value, {
    center: { lat, lng }, zoom: 16, mapTypeControl: false, streetViewControl: false
  })
  new google.maps.Marker({ map, position: { lat, lng }, title: 'Meeting Point', animation: google.maps.Animation.DROP })
}

async function selectHotelPickup() {
  pickupChoice.value = 'hotel_pickup'
  await saveConfig()
}

async function confirmMeetingPoint() {
  pickupChoice.value = 'meeting_point'
  await saveConfig()
}

async function saveConfig() {
  const success = await savePickupConfiguration()
  if (success) {
    emit('completed', {
      pickupChoice: pickupChoice.value,
      hotelName: hotelValidation.value?.hotel_name,
      extraCost: extraCost.value
    })
  }
}

onMounted(() => loadPickupDetails())
onUnmounted(() => cleanup())
</script>
