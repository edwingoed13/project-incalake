import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(null)
  const user = ref<any | null>(null)
  const isAuthenticated = ref(false)

  function setToken(newToken: string) {
    token.value = newToken
    isAuthenticated.value = !!newToken
  }

  function setUser(newUser: any) {
    user.value = newUser
  }

  function logout() {
    token.value = null
    user.value = null
    isAuthenticated.value = false
  }

  return {
    token,
    user,
    isAuthenticated,
    setToken,
    setUser,
    logout
  }
})
