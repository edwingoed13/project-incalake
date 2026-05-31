// Hydrate the cart from localStorage BEFORE any page mounts.
//
// Without this, the store starts empty on every hard refresh (the page-level
// `cartStore.loadFromLocalStorage()` in cart.vue / checkout.vue only fires
// when the user lands on those pages). Visiting /tour and clicking "Agregar
// al carrito" on a non-hydrated store would push the new item into an empty
// `items` array, then saveToLocalStorage overwrites the persisted N-item
// cart with the single new item — silently losing everything saved before.
//
// Client-only because localStorage doesn't exist during SSR.
export default defineNuxtPlugin(() => {
  const cart = useCartStore()
  cart.loadFromLocalStorage()
})
