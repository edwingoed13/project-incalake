// v-skeleton: while a (tour) image loads, show a shimmer on its container and
// fade the image in when ready — so users see "an image is loading here" instead
// of a blank/abrupt pop OR a flash of the PREVIOUS image. Client-only behaviour
// (mounted/updated run on the client); already-loaded images are shown as-is.
export default defineNuxtPlugin((nuxtApp) => {
  const clearListener = (el: any) => {
    if (el._skOn) {
      el.removeEventListener('load', el._skOn)
      el.removeEventListener('error', el._skOn)
      el._skOn = null
    }
  }

  const attach = (el: any) => {
    clearListener(el) // drop a previous src's listener so it can't reveal the wrong image
    // Already loaded (cached / SSR-painted) → show as-is, no skeleton.
    if (el.complete && el.naturalWidth > 0) {
      el.style.opacity = '1'
      el.parentElement?.classList.remove('img-skeleton')
      return
    }
    // Hide the (possibly stale) image + shimmer the container until the new one loads.
    el.parentElement?.classList.add('img-skeleton')
    el.style.transition = 'opacity .4s ease'
    el.style.opacity = '0'
    el._skOn = () => {
      el.style.opacity = '1'
      el.parentElement?.classList.remove('img-skeleton')
      clearListener(el)
    }
    el.addEventListener('load', el._skOn)
    el.addEventListener('error', el._skOn)
  }

  nuxtApp.vueApp.directive('skeleton', {
    mounted(el: any) {
      el._skSrc = el.src
      attach(el)
    },
    updated(el: any) {
      // src swapped on a reused element (list filter/paginate, tour→tour nav):
      // re-skeleton until the NEW image loads instead of flashing the previous one.
      if (el.src !== el._skSrc) {
        el._skSrc = el.src
        attach(el)
      }
    },
  })
})
