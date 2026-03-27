import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(null)
  const user = ref<any | null>(null)
  const isAuthenticated = ref(false)

  // Inicializar desde localStorage al crear el store
  function init() {
    if (process.client) {
      const savedToken = localStorage.getItem('auth_token')
      const savedUser = localStorage.getItem('auth_user')

      if (savedToken) {
        token.value = savedToken
        isAuthenticated.value = true
      }

      if (savedUser) {
        try {
          user.value = JSON.parse(savedUser)
        } catch (e) {
          console.error('Error parsing saved user:', e)
        }
      }
    }
  }

  function setToken(newToken: string) {
    token.value = newToken
    isAuthenticated.value = !!newToken

    if (process.client) {
      if (newToken) {
        localStorage.setItem('auth_token', newToken)
      } else {
        localStorage.removeItem('auth_token')
      }
    }
  }

  function setUser(newUser: any) {
    user.value = newUser

    if (process.client) {
      if (newUser) {
        localStorage.setItem('auth_user', JSON.stringify(newUser))
      } else {
        localStorage.removeItem('auth_user')
      }
    }
  }

  function logout() {
    token.value = null
    user.value = null
    isAuthenticated.value = false

    if (process.client) {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
    }
  }

  // Inicializar al crear el store
  init()

  return {
    token,
    user,
    isAuthenticated,
    setToken,
    setUser,
    logout
  }
})
