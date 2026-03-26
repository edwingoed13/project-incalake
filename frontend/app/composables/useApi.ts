export const useApi = () => {
  const config = useRuntimeConfig()

  const api = $fetch.create({
    baseURL: config.public.apiBase as string,
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
