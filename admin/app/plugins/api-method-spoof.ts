// The cPanel/Apache host in front of api.incalake.com downgrades PUT/PATCH/
// DELETE requests to GET before they reach PHP, so Laravel's router 404s on
// every non-POST write (observed: PUT /admin/reviews/{id} → "Ruta no
// encontrada"). Tunnel those methods through POST using Laravel's native
// `_method` query override, which needs no extra CORS headers.
export default defineNuxtPlugin(() => {
  const apiUrl = String(useRuntimeConfig().public.apiUrl || '')
  if (!apiUrl) return

  const original = globalThis.$fetch
  const SPOOFED = ['PUT', 'PATCH', 'DELETE']

  const wrapped = ((request: any, options: any = {}) => {
    const method = String(options?.method || 'GET').toUpperCase()
    const url = typeof request === 'string' ? request : String(request?.url ?? '')
    if (SPOOFED.includes(method) && url.startsWith(apiUrl)) {
      const sep = url.includes('?') ? '&' : '?'
      return original(`${url}${sep}_method=${method}`, { ...options, method: 'POST' })
    }
    return original(request, options)
  }) as typeof globalThis.$fetch

  wrapped.raw = original.raw
  wrapped.create = original.create
  wrapped.native = original.native
  globalThis.$fetch = wrapped
})
