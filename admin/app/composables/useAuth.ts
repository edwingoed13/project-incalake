import { useAuthStore } from '~/stores/auth'

export const useAuth = () => {
    const config = useRuntimeConfig()
    const defaultApiUrl = config.public.apiUrl

    const login = async (email: string, password: string) => {
        try {
            // Hacemos un fetch puro, como buena práctica de SPA
            const response: any = await $fetch(`${defaultApiUrl}/auth/login`, {
              method: 'POST',
              body: {
                  email,
                  password
              }
            })

            if(response && response.success && response.data.token) {
                 const store = useAuthStore()
                 // Guardamos todo en memoria pinia
                 store.setToken(response.data.token)
                 store.setUser(response.data.user)

                 return true
            }

            throw new Error("Credenciales inválidas");
        } catch (error: any) {
            console.error("Login failed", error)
            throw new Error(error.data?.message || 'Error conectando al servidor')
        }
    }

    return {
        login
    }
}
