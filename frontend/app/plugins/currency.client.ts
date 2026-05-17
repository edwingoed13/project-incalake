export default defineNuxtPlugin((nuxtApp) => {
  const currencyStore = useCurrencyStore()

  // IMPORTANT: do NOT mutate the store before hydration.
  // This plugin runs on the client *before* Vue hydrates. If we call
  // initCurrency() here it reads localStorage and switches selectedCurrency
  // (e.g. USD -> PEN) + loads cached rates, so the client's first render no
  // longer matches the server-rendered HTML (which is always USD) — that's the
  // "Hydration completed but contains mismatches" warning, on every price.
  //
  // Deferring to app:mounted keeps the first client render identical to SSR.
  // The currency then switches as a normal post-mount reactive update
  // (re-render, no mismatch) — same pattern as theme/locale preference.
  nuxtApp.hook('app:mounted', () => {
    currencyStore.initCurrency()
  })
})
