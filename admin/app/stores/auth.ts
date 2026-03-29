import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(null)
  const user = ref<any | null>(null)
  const isAuthenticated = ref(false)
  const permissions = ref<any>({})
  const role = ref<string>('customer')

  // Inicializar desde localStorage al crear el store
  function init() {
    if (process.client) {
      const savedToken = localStorage.getItem('auth_token')
      const savedUser = localStorage.getItem('auth_user')
      const savedPermissions = localStorage.getItem('auth_permissions')
      const savedRole = localStorage.getItem('auth_role')

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

      if (savedPermissions) {
        try {
          permissions.value = JSON.parse(savedPermissions)
        } catch (e) {
          console.error('Error parsing saved permissions:', e)
        }
      }

      if (savedRole) {
        role.value = savedRole
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

  function setPermissions(newPermissions: any, newRole: string) {
    permissions.value = newPermissions
    role.value = newRole

    if (process.client) {
      if (newPermissions) {
        localStorage.setItem('auth_permissions', JSON.stringify(newPermissions))
        localStorage.setItem('auth_role', newRole)
      } else {
        localStorage.removeItem('auth_permissions')
        localStorage.removeItem('auth_role')
      }
    }
  }

  function hasPermission(permission: string): boolean {
    return permissions.value[permission] === true
  }

  function logout() {
    token.value = null
    user.value = null
    isAuthenticated.value = false
    permissions.value = {}
    role.value = 'customer'

    if (process.client) {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      localStorage.removeItem('auth_permissions')
      localStorage.removeItem('auth_role')
    }
  }

  // Inicializar al crear el store
  init()

  return {
    token,
    user,
    isAuthenticated,
    permissions,
    role,
    setToken,
    setUser,
    setPermissions,
    hasPermission,
    logout
  }
})
