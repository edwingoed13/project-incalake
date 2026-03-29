import { ref, computed } from 'vue'

export interface Permissions {
  [key: string]: boolean
}

export interface PermissionsData {
  role: string
  permissions: Permissions
  is_admin: boolean
  is_staff: boolean
  can_access_admin: boolean
}

const permissions = ref<Permissions>({})
const role = ref<string>('customer')
const isAdmin = ref(false)
const isStaff = ref(false)
const canAccessAdmin = ref(false)

export const usePermissions = () => {
  const config = useRuntimeConfig()

  /**
   * Load permissions from API
   */
  const loadPermissions = async () => {
    try {
      const token = localStorage.getItem('auth_token')
      if (!token) return

      const response = await fetch(`${config.public.apiUrl}/auth/permissions`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })

      if (!response.ok) return

      const data = await response.json()
      if (data.success && data.data) {
        permissions.value = data.data.permissions || {}
        role.value = data.data.role || 'customer'
        isAdmin.value = data.data.is_admin || false
        isStaff.value = data.data.is_staff || false
        canAccessAdmin.value = data.data.can_access_admin || false
      }
    } catch (error) {
      console.error('Error loading permissions:', error)
    }
  }

  /**
   * Check if user has a specific permission
   */
  const hasPermission = (permission: string): boolean => {
    return permissions.value[permission] === true
  }

  /**
   * Check if user has any of the given permissions
   */
  const hasAnyPermission = (permissionList: string[]): boolean => {
    return permissionList.some(p => hasPermission(p))
  }

  /**
   * Check if user has all of the given permissions
   */
  const hasAllPermissions = (permissionList: string[]): boolean => {
    return permissionList.every(p => hasPermission(p))
  }

  /**
   * Get role label
   */
  const roleLabel = computed(() => {
    const labels: { [key: string]: string } = {
      admin: 'Administrador',
      staff: 'Staff',
      customer: 'Cliente'
    }
    return labels[role.value] || role.value
  })

  return {
    // State
    permissions,
    role,
    isAdmin,
    isStaff,
    canAccessAdmin,
    roleLabel,

    // Methods
    loadPermissions,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions
  }
}
