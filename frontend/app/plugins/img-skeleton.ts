// v-skeleton: while a (tour) image loads, show a shimmer on its container and
// fade the image in when ready — so users see "an image is loading here" instead
// of a blank/abrupt pop. Client-only; if the image is already loaded (cached /
// SSR-painted) it does nothing, so there's no flash.
export default defineNuxtPlugin((nuxtApp) => {
  nuxtApp.vueApp.directive('skeleton', {
    mounted(el: HTMLImageElement) {
      const parent = el.parentElement
      // Already loaded (cached) → leave as-is, no skeleton, no fade.
      if (el.complete && el.naturalWidth > 0) return

      parent?.classList.add('img-skeleton')
      el.style.opacity = '0'
      el.style.transition = 'opacity .5s ease'

      const reveal = () => {
        el.style.opacity = '1'
        parent?.classList.remove('img-skeleton')
      }
      el.addEventListener('load', reveal, { once: true })
      el.addEventListener('error', reveal, { once: true })
    },
  })
})
