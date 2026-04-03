import { ref, computed, type Ref } from 'vue'

export function useHotelPickupValidation(bookingId: Ref<number | string>, tourConfig: Ref<any>) {
  const { api } = useApi()

  const hotelValidation = ref<any>(null)
  const pickupChoice = ref<string | null>(null)
  const isValidating = ref(false)
  const validationError = ref<string | null>(null)
  const isSaving = ref(false)
  const saveError = ref<string | null>(null)

  let map: any = null
  let hotelMarker: any = null
  let radiusCircle: any = null
  let meetingPointMarker: any = null
  let autocomplete: any = null

  const initializeHotelSearch = (inputElement: HTMLInputElement, mapElement: HTMLElement) => {
    if (!window.google) return

    autocomplete = new google.maps.places.Autocomplete(inputElement, {
      types: ['lodging'],
      componentRestrictions: { country: ['PE', 'BO'] },
      fields: ['name', 'geometry', 'formatted_address', 'place_id']
    })

    const centerLat = parseFloat(tourConfig.value.pickup_center_lat) || -15.8402
    const centerLng = parseFloat(tourConfig.value.pickup_center_lng) || -70.0219

    map = new google.maps.Map(mapElement, {
      center: { lat: centerLat, lng: centerLng },
      zoom: 13,
      mapTypeControl: false,
      streetViewControl: false,
    })

    if (tourConfig.value.enable_hotel_pickup && tourConfig.value.pickup_radius_km) {
      const radiusKm = parseFloat(tourConfig.value.pickup_radius_km) || 1
      radiusCircle = new google.maps.Circle({
        map,
        center: { lat: centerLat, lng: centerLng },
        radius: radiusKm * 1000,
        fillColor: '#10B981',
        fillOpacity: 0.15,
        strokeColor: '#10B981',
        strokeOpacity: 0.8,
        strokeWeight: 2
      })
    }

    if (tourConfig.value.enable_meeting_point && tourConfig.value.meeting_point_lat) {
      const mLat = parseFloat(tourConfig.value.meeting_point_lat)
      const mLng = parseFloat(tourConfig.value.meeting_point_lng)
      if (!isNaN(mLat) && !isNaN(mLng)) {
        meetingPointMarker = new google.maps.Marker({
          map,
          position: { lat: mLat, lng: mLng },
          title: 'Meeting Point',
          icon: { url: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png' }
        })
      }
    }

    autocomplete.addListener('place_changed', handlePlaceSelection)
  }

  const handlePlaceSelection = async () => {
    const place = autocomplete.getPlace()
    if (!place.geometry) {
      validationError.value = 'Hotel not found'
      return
    }

    if (hotelMarker) hotelMarker.setMap(null)

    hotelMarker = new google.maps.Marker({
      map,
      position: place.geometry.location,
      title: place.name,
      animation: google.maps.Animation.DROP
    })

    const bounds = new google.maps.LatLngBounds()
    bounds.extend(place.geometry.location)
    if (radiusCircle) bounds.extend(radiusCircle.getCenter())
    map.fitBounds(bounds)

    await validateHotelLocation({
      hotel_name: place.name,
      hotel_address: place.formatted_address,
      hotel_lat: place.geometry.location.lat(),
      hotel_lng: place.geometry.location.lng(),
      hotel_place_id: place.place_id
    })
  }

  const validateHotelLocation = async (hotelData: any) => {
    isValidating.value = true
    validationError.value = null
    try {
      const res = await api(`/bookings/${bookingId.value}/validate-hotel`, {
        method: 'POST',
        body: hotelData
      })
      const data = (res as any)?.data || res
      if (data) {
        hotelValidation.value = {
          ...data,
          hotel_lat: hotelData.hotel_lat,
          hotel_lng: hotelData.hotel_lng,
          hotel_place_id: hotelData.hotel_place_id
        }
        if (hotelMarker) {
          hotelMarker.setIcon({
            url: hotelValidation.value.is_within_radius
              ? 'http://maps.google.com/mapfiles/ms/icons/green-dot.png'
              : 'http://maps.google.com/mapfiles/ms/icons/red-dot.png'
          })
        }
      }
    } catch (e: any) {
      validationError.value = 'Error validating hotel location'
    } finally {
      isValidating.value = false
    }
  }

  const savePickupConfiguration = async (): Promise<boolean> => {
    if (!pickupChoice.value) {
      saveError.value = 'Please select a pickup option'
      return false
    }
    isSaving.value = true
    saveError.value = null
    try {
      const payload: any = { final_choice: pickupChoice.value }
      if (pickupChoice.value === 'hotel_pickup' && hotelValidation.value) {
        payload.hotel_name = hotelValidation.value.hotel_name
        payload.hotel_address = hotelValidation.value.hotel_address
        payload.hotel_lat = hotelValidation.value.hotel_lat
        payload.hotel_lng = hotelValidation.value.hotel_lng
        payload.hotel_place_id = hotelValidation.value.hotel_place_id
        payload.distance = hotelValidation.value.distance
        payload.extra_cost = hotelValidation.value.extra_cost
      }
      await api(`/bookings/${bookingId.value}/save-pickup`, { method: 'POST', body: payload })
      return true
    } catch (e: any) {
      saveError.value = 'Error saving pickup configuration'
      return false
    } finally {
      isSaving.value = false
    }
  }

  const showMeetingPointOnMap = () => {
    if (meetingPointMarker && map) {
      map.setCenter(meetingPointMarker.getPosition())
      map.setZoom(16)
      meetingPointMarker.setAnimation(google.maps.Animation.BOUNCE)
      setTimeout(() => meetingPointMarker.setAnimation(null), 2000)
    }
  }

  const cleanup = () => {
    if (hotelMarker) { hotelMarker.setMap(null); hotelMarker = null }
    if (radiusCircle) { radiusCircle.setMap(null); radiusCircle = null }
    if (meetingPointMarker) { meetingPointMarker.setMap(null); meetingPointMarker = null }
    if (autocomplete) { google.maps.event.clearInstanceListeners(autocomplete); autocomplete = null }
    map = null
  }

  const isWithinRadius = computed(() => hotelValidation.value?.is_within_radius || false)
  const extraCost = computed(() => hotelValidation.value?.extra_cost || 0)
  const requiresApproval = computed(() => hotelValidation.value?.requires_approval || false)

  return {
    hotelValidation, pickupChoice, isValidating, validationError,
    isSaving, saveError, isWithinRadius, extraCost, requiresApproval,
    initializeHotelSearch, savePickupConfiguration, showMeetingPointOnMap, cleanup
  }
}
