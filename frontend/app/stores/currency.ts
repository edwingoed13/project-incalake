import { defineStore } from 'pinia'

export const CURRENCIES = [
  { code: 'USD', symbol: '$',    name: 'US Dollar (USD)' },
  { code: 'PEN', symbol: 'S/',   name: 'Sol Peruano (PEN)' },
  { code: 'EUR', symbol: '€',    name: 'Euro (EUR)' },
  { code: 'BRL', symbol: 'R$',   name: 'Real Brasileño (BRL)' },
  { code: 'MXN', symbol: 'Mex$', name: 'Peso Mexicano (MXN)' },
]

const CACHE_KEY = 'currency_rates_cache'
const CACHE_TTL = 24 * 60 * 60 * 1000 // 24 hours

export const useCurrencyStore = defineStore('currency', () => {
  const selectedCurrency = ref<string>('USD')
  const rates = ref<Record<string, number>>({})
  const loading = ref(false)
  const error = ref<string | null>(null)

  const currentCurrency = computed(() =>
    CURRENCIES.find(c => c.code === selectedCurrency.value) ?? CURRENCIES[0]
  )

  const isForeignCurrency = computed(() => selectedCurrency.value !== 'USD')

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
      // Try multiple APIs for CORS compatibility
      const symbols = CURRENCIES.filter(c => c.code !== 'USD').map(c => c.code).join(',')
      let result: Record<string, number> = { USD: 1 }
      let fetched = false

      // Option 1: open.er-api.com (free, CORS enabled)
      try {
        const res = await fetch('https://open.er-api.com/v6/latest/USD')
        const json = await res.json()
        if (json?.rates) {
          for (const c of CURRENCIES) {
            if (c.code !== 'USD' && json.rates[c.code]) {
              result[c.code] = json.rates[c.code]
            }
          }
          fetched = true
        }
      } catch {}

      // Option 2: Frankfurter (fallback, may have CORS issues)
      if (!fetched) {
        try {
          const res = await fetch(`https://api.frankfurter.app/latest?from=USD&to=${symbols}`)
          const json = await res.json()
          if (json?.rates) {
            for (const [code, rate] of Object.entries(json.rates)) {
              result[code] = rate as number
            }
            fetched = true
          }
        } catch {}
      }

      if (fetched) {
        rates.value = result
        saveToCache(result)
      }
    } catch (e) {
      error.value = 'Could not fetch exchange rates'
      // Fallback approximate rates (updated 2026)
      rates.value = {
        USD: 1, PEN: 3.75, EUR: 0.92, BRL: 5.1, MXN: 18.5
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

  function formatConverted(amountUSD: number | null | undefined, showDecimals = true): string {
    if (amountUSD == null || isNaN(amountUSD)) {
      return '$0.00'
    }

    const converted = convert(amountUSD)
    const { symbol } = currentCurrency.value
    const decimals = showDecimals ? 2 : 0
    const formatted = converted.toLocaleString('en-US', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals
    })
    return `${symbol} ${formatted}`
  }

  function setCurrency(code: string) {
    if (!CURRENCIES.find(c => c.code === code)) return
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
    isForeignCurrency,
    CURRENCIES,
    fetchRates,
    convert,
    formatConverted,
    setCurrency,
    initCurrency,
  }
})
