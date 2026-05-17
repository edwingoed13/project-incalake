// Production env vars (cPanel/Vercel) are frequently pasted with a trailing
// newline or stray whitespace, e.g. "https://api.incalake.com/api\n". That
// produced URLs like ".../api\n/bookings/113/payment/culqi" -> 400. Strip all
// surrounding whitespace and any trailing slash so concatenation is always clean.
export const sanitizeBaseUrl = (raw: unknown): string =>
  String(raw ?? '').trim().replace(/\s+/g, '').replace(/\/+$/, '')

export const useApi = () => {
  const config = useRuntimeConfig()

  const api = $fetch.create({
    baseURL: sanitizeBaseUrl(config.public.apiBase),
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    },
    onRequest({ options }) {
      // Agregar auth token si existe
      const token = useCookie('auth_token')
      if (token.value) {
        options.headers = {
          ...options.headers,
          Authorization: `Bearer ${token.value}`
        }
      }
    },
    onResponseError({ response }) {
      // Manejo global de errores
      console.error('API Error:', response.status, response._data)
    }
  })

  return { api }
}
