import { ref, onMounted, onUnmounted } from 'vue'

declare global {
  interface Window {
    google: any
    initGooglePlaces: () => void
  }
}

let googleLoaded = ref(false)
let loadPromise: Promise<void> | null = null

export const useGooglePlaces = () => {
  const config = useRuntimeConfig()
  const apiKey = config.public.googleMapsApiKey

  // Load Google Maps script
  const loadGoogleMaps = (): Promise<void> => {
    if (loadPromise) return loadPromise

    loadPromise = new Promise((resolve, reject) => {
      // Check if already loaded
      if (window.google?.maps?.places) {
        googleLoaded.value = true
        resolve()
        return
      }

      // Check if script already exists
      const existingScript = document.querySelector('script[src*="maps.googleapis.com"]')
      if (existingScript) {
        existingScript.addEventListener('load', () => {
          googleLoaded.value = true
          resolve()
        })
        return
      }

      // Create and load script
      const script = document.createElement('script')
      script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places&callback=initGooglePlaces`
      script.async = true
      script.defer = true

      window.initGooglePlaces = () => {
        googleLoaded.value = true
        resolve()
      }

      script.onerror = () => {
        reject(new Error('Failed to load Google Maps'))
      }

      document.head.appendChild(script)
    })

    return loadPromise
  }

  // Initialize city autocomplete on an input element
  const initCityAutocomplete = async (
    inputElement: HTMLInputElement,
    onPlaceSelected?: (place: any) => void
  ) => {
    if (!inputElement) return null

    try {
      await loadGoogleMaps()

      if (!window.google?.maps?.places) {
        console.error('Google Places API not available')
        return null
      }

      // Check if already initialized
      if (inputElement.hasAttribute('data-autocomplete-initialized')) {
        return null
      }

      inputElement.setAttribute('data-autocomplete-initialized', 'true')

      // Create autocomplete instance
      const autocomplete = new window.google.maps.places.Autocomplete(inputElement, {
        types: ['(cities)'],
        fields: ['formatted_address', 'name', 'address_components', 'geometry']
      })

      // Add listener for place selection
      autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace()

        if (place && place.formatted_address) {
          // Extract city name and country
          let cityName = place.name || ''
          let countryName = ''

          if (place.address_components) {
            for (const component of place.address_components) {
              if (component.types.includes('country')) {
                countryName = component.long_name
              }
              if (component.types.includes('locality')) {
                cityName = component.long_name
              }
            }
          }

          // Format the display value
          const displayValue = countryName ? `${cityName}, ${countryName}` : place.formatted_address

          // Update input value
          inputElement.value = displayValue

          // Call callback if provided
          if (onPlaceSelected) {
            onPlaceSelected({
              cityName,
              countryName,
              formatted_address: place.formatted_address,
              lat: place.geometry?.location?.lat(),
              lng: place.geometry?.location?.lng()
            })
          }

          // Dispatch events for Vue reactivity
          inputElement.dispatchEvent(new Event('input', { bubbles: true }))
          inputElement.dispatchEvent(new Event('change', { bubbles: true }))
        }
      })

      return autocomplete
    } catch (error) {
      console.error('Error initializing Google Places:', error)
      return null
    }
  }

  // Initialize generic place autocomplete (POIs, landmarks, hotels, restaurants...)
  // Use this for map route points where users may type "mirador", "hotel X", etc.
  const initPlaceAutocomplete = async (
    inputElement: HTMLInputElement,
    onPlaceSelected?: (place: any) => void
  ) => {
    if (!inputElement) return null

    try {
      await loadGoogleMaps()

      if (!window.google?.maps?.places) {
        console.error('Google Places API not available')
        return null
      }

      if (inputElement.hasAttribute('data-autocomplete-initialized')) {
        return null
      }
      inputElement.setAttribute('data-autocomplete-initialized', 'true')

      const autocomplete = new window.google.maps.places.Autocomplete(inputElement, {
        // No types restriction = matches establishments, geocoded addresses, regions, etc.
        fields: ['formatted_address', 'name', 'address_components', 'geometry', 'types'],
      })

      autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace()
        if (!place) return

        const lat = place.geometry?.location?.lat()
        const lng = place.geometry?.location?.lng()

        if (onPlaceSelected) {
          onPlaceSelected({
            name: place.name || '',
            formatted_address: place.formatted_address || '',
            types: place.types || [],
            lat,
            lng,
          })
        }

        inputElement.dispatchEvent(new Event('input', { bubbles: true }))
        inputElement.dispatchEvent(new Event('change', { bubbles: true }))
      })

      return autocomplete
    } catch (error) {
      console.error('Error initializing Google Places autocomplete:', error)
      return null
    }
  }

  // Cleanup function
  const cleanup = (inputElement: HTMLInputElement) => {
    if (inputElement) {
      inputElement.removeAttribute('data-autocomplete-initialized')
      // Google Places doesn't provide a destroy method, so we just remove the attribute
    }
  }

  return {
    googleLoaded,
    loadGoogleMaps,
    initCityAutocomplete,
    initPlaceAutocomplete,
    cleanup
  }
}