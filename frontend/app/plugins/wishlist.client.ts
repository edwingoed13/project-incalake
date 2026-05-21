export default defineNuxtPlugin((nuxtApp) => {
  const wishlist = useWishlistStore()

  // Defer hydration to app:mounted so the first client render matches the
  // SSR HTML (server has no localStorage -> empty wishlist). Same pattern as
  // [[currency-plugin]] — see currency.client.ts for the rationale.
  nuxtApp.hook('app:mounted', () => {
    wishlist.hydrate()
  })
})
