import { defineStore } from 'pinia'

export const CURRENCIES = [
  { code: 'USD', symbol: '$',   name: 'US Dollar (USD)' },
  { code: 'PEN', symbol: 'S/.', name: 'Peruvian Sol (PEN)' },
  { code: 'EUR', symbol: '€',   name: 'Euro (EUR)' },
  { code: 'GBP', symbol: '£',   name: 'British Pound (GBP)' },
  { code: 'BRL', symbol: 'R$',  name: 'Brazilian Real (BRL)' },
  { code: 'COP', symbol: 'COP', name: 'Colombian Peso (COP)' },
  { code: 'CAD', symbol: 'CA$', name: 'Canadian Dollar (CAD)' },
  { code: 'AUD', symbol: 'A$',  name: 'Australian Dollar (AUD)' },
]

const CACHE_KEY = 'currency_rates_cache'
const CACHE_TTL = 60 * 60 * 1000 // 1 hour in ms

export const useCurrencyStore = defineStore('currency', () => {
  const selectedCurrency = ref<string>('USD')
  const rates = ref<Record<string, number>>({})
  const loading = ref(false)
  const error = ref<string | null>(null)

  const currentCurrency = computed(() =>
    CURRENCIES.find(c => c.code === selectedCurrency.value) ?? CURRENCIES[0]
  )

  function loadFromCache(): boolean {
    if (import.meta.client) {
      try {
        const raw = localStorage.getItem(CACHE_KEY)
        if (!raw) return false
        const { timestamp, data } = JSON.parse(raw)
        if (Date.now() - timestamp > CACHE_TTL) return false
        rates.value = data
        return true
      } catch {
        return false
      }
    }
    return false
  }

  function saveToCache(data: Record<string, number>) {
    if (import.meta.client) {
      localStorage.setItem(CACHE_KEY, JSON.stringify({ timestamp: Date.now(), data }))
    }
  }

  async function fetchRates() {
    if (loadFromCache()) return

    loading.value = true
    error.value = null
    try {
      const res = await fetch(
        'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/usd.json'
      )
      const json = await res.json()
      // json.usd contains all rates relative to USD
      const usdRates: Record<string, number> = json.usd
      const result: Record<string, number> = { USD: 1 }
      for (const cur of CURRENCIES) {
        const key = cur.code.toLowerCase()
        if (usdRates[key]) result[cur.code] = usdRates[key]
      }
      rates.value = result
      saveToCache(result)
    } catch (e) {
      error.value = 'Could not fetch exchange rates'
      // Fallback approximate rates
      rates.value = {
        USD: 1, PEN: 3.75, EUR: 0.92, GBP: 0.79,
        BRL: 4.97, COP: 3900, CAD: 1.36, AUD: 1.53
      }
    } finally {
      loading.value = false
    }
  }

  function convert(amountUSD: number): number {
    if (selectedCurrency.value === 'USD') return amountUSD
    const rate = rates.value[selectedCurrency.value]
    if (!rate) return amountUSD
    return amountUSD * rate
  }

  function formatConverted(amountUSD: number): string {
    // Validate input
    if (amountUSD == null || isNaN(amountUSD)) {
      return '$0.00'
    }

    const converted = convert(amountUSD)
    const { symbol, code } = currentCurrency.value

    // COP needs no decimals (large numbers)
    const decimals = code === 'COP' ? 0 : 2
    const formatted = converted.toFixed(decimals)

    // Add thousand separators for COP
    if (code === 'COP') {
      return `${symbol} ${Number(formatted).toLocaleString('es-CO')}`
    }
    return `${symbol}${formatted}`
  }

  function setCurrency(code: string) {
    selectedCurrency.value = code
    if (import.meta.client) {
      localStorage.setItem('selected_currency', code)
    }
  }

  function initCurrency() {
    if (import.meta.client) {
      const saved = localStorage.getItem('selected_currency')
      if (saved && CURRENCIES.find(c => c.code === saved)) {
        selectedCurrency.value = saved
      }
    }
    fetchRates()
  }

  return {
    selectedCurrency,
    rates,
    loading,
    error,
    currentCurrency,
    CURRENCIES,
    fetchRates,
    convert,
    formatConverted,
    setCurrency,
    initCurrency,
  }
})
