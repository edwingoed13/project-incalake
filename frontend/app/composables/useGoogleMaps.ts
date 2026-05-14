// Lazily injects the Google Maps JS API. Replaces the global <script> tag that
// was loaded on every page from nuxt.config — now only the pages/components that
// actually need maps (tour detail, booking pickup, hotel validator) pay the cost.
//
// All callers share the same in-flight promise, so concurrent calls only ever
// insert one <script>. Subsequent calls resolve instantly once loaded.

declare global {
  interface Window {
    __googleMapsLoader?: Promise<void>
  }
}

const SCRIPT_ID = 'google-maps-script'

export function useGoogleMaps(): Promise<void> {
  if (typeof window === 'undefined') {
    // SSR guard — never load on the server.
    return new Promise(() => {})
  }

  if ((window as any).google?.maps) {
    return Promise.resolve()
  }

  if (window.__googleMapsLoader) {
    return window.__googleMapsLoader
  }

  const config = useRuntimeConfig()
  const apiKey = (config.public as any).googleMapsKey
    || 'AIzaSyCC2CAVXwufsdT5TX3UPk7hZ3HHw3NZl_c' // legacy fallback to preserve current behavior

  window.__googleMapsLoader = new Promise<void>((resolve, reject) => {
    const existing = document.getElementById(SCRIPT_ID) as HTMLScriptElement | null
    if (existing) {
      existing.addEventListener('load', () => resolve())
      existing.addEventListener('error', () => reject(new Error('Google Maps failed to load')))
      return
    }

    const script = document.createElement('script')
    script.id = SCRIPT_ID
    // No `loading=async` — that flag makes the loader itself async, so
    // `google.maps.Map` is NOT constructable when `onload` fires (you'd have to
    // use `google.maps.importLibrary("maps")` instead). All callers still use
    // the classic synchronous API, so we omit the flag.
    script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places`
    script.async = true
    script.defer = true
    script.onload = () => resolve()
    script.onerror = () => reject(new Error('Google Maps failed to load'))
    document.head.appendChild(script)
  })

  return window.__googleMapsLoader
}
