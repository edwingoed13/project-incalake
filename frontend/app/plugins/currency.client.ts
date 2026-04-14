export default defineNuxtPlugin(() => {
  const currencyStore = useCurrencyStore()
  currencyStore.initCurrency()
})
