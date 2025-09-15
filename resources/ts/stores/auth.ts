import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'

interface User {
  id: number
  name: string
  email: string
  role: string
  company_id?: number
}

interface UserData {
  user: User
  token: string
  role: string
  login_type: string
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)
  const role = ref<string | null>(null)
  const loginType = ref<string | null>(null)
  const lastActivity = ref<number | null>(null)
  const sessionTimeout = 30 * 60 * 1000 // 10 minutes in milliseconds

  function storeUserData(userData: UserData) {
    user.value = userData.user
    token.value = userData.token
    role.value = userData.role
    loginType.value = userData.login_type
    lastActivity.value = Date.now()
    
    // Start activity tracking
    startActivityTracking()
  }

  function deleteUserData() {
    user.value = null
    token.value = null
    role.value = null
    loginType.value = null
    lastActivity.value = null
    
    // Stop activity tracking
    stopActivityTracking()
  }

  function updateLastActivity() {
    if (isLoggedIn.value) {
      lastActivity.value = Date.now()
    }
  }

  // Activity tracking variables
  let activityTimer: NodeJS.Timeout | null = null
  let visibilityTimer: NodeJS.Timeout | null = null

  function startActivityTracking() {
    // Track user activity (mouse, keyboard, touch)
    const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click']
    
    const handleActivity = () => {
      updateLastActivity()
    }

    activityEvents.forEach(event => {
      document.addEventListener(event, handleActivity, true)
    })

    // Track page visibility changes
    const handleVisibilityChange = () => {
      if (document.hidden) {
        // Page is hidden, start timer
        visibilityTimer = setTimeout(() => {
          checkSessionTimeout()
        }, sessionTimeout)
      } else {
        // Page is visible again, cancel timer and update activity
        if (visibilityTimer) {
          clearTimeout(visibilityTimer)
          visibilityTimer = null
        }
        updateLastActivity()
      }
    }

    document.addEventListener('visibilitychange', handleVisibilityChange)

    // Periodic check every minute
    activityTimer = setInterval(() => {
      checkSessionTimeout()
    }, 60000) // Check every minute
  }

  function stopActivityTracking() {
    // Remove event listeners
    const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click']
    
    const handleActivity = () => {
      updateLastActivity()
    }

    activityEvents.forEach(event => {
      document.removeEventListener(event, handleActivity, true)
    })

    document.removeEventListener('visibilitychange', () => {})

    // Clear timers
    if (activityTimer) {
      clearInterval(activityTimer)
      activityTimer = null
    }
    if (visibilityTimer) {
      clearTimeout(visibilityTimer)
      visibilityTimer = null
    }
  }

  function checkSessionTimeout(): Promise<boolean> {
    return new Promise((resolve) => {
      if (!isLoggedIn.value || !lastActivity.value) {
        resolve(false)
        return
      }

      const now = Date.now()
      const timeSinceLastActivity = now - lastActivity.value

      if (timeSinceLastActivity > sessionTimeout) {
        // Session expired
        logout('session_timeout')
        resolve(true) // Session was expired
      } else {
        resolve(false) // Session still valid
      }
    })
  }

  // ========== FUNGSI UNTUK MENGAMBIL DATA USER ==========
  // Mendapatkan data user lengkap
  const getCurrentUser = computed(() => {
    return user.value
  })

  // Mendapatkan nama user
  const getUserName = computed(() => {
    return user.value?.name || ''
  })

  // Mendapatkan email user
  const getUserEmail = computed(() => {
    return user.value?.email || ''
  })

  // Mendapatkan ID user
  const getUserId = computed(() => {
    return user.value?.id || null
  })

  // Mendapatkan role user
  const getUserRole = computed(() => {
    return role.value || ''
  })

  // Mendapatkan login type
  const getLoginType = computed(() => {
    return loginType.value || ''
  })

  // ========== FUNGSI UNTUK CHECKING STATUS LOGIN ==========
  // Helper untuk check apakah user sudah login
  const isLoggedIn = computed(() => {
    return !!(user.value && token.value)
  })

  // Helper untuk check role
  const hasRole = (requiredRole: string) => {
    return role.value === requiredRole
  }

  // Helper untuk check apakah user adalah company user
  const isCompanyUser = computed(() => {
    return loginType.value === 'company' && (role.value === 'user')
  })

  // ========== FUNGSI UNTUK ROLE-BASED ACCESS ==========
  // Check apakah user adalah admin
  const isAdmin = computed(() => {
    return role.value === 'admin'
  })

  // Check apakah user adalah user biasa
  const isRegularUser = computed(() => {
    return role.value === 'user'
  })

  // Check apakah user bisa akses dashboard admin
  const canAccessAdminDashboard = computed(() => {
    return isLoggedIn.value && (role.value === 'admin' || role.value === 'super_admin')
  })

  // Check apakah user bisa akses halaman user
  const canAccessUserPages = computed(() => {
    return isLoggedIn.value && role.value === 'user'
  })

  // Function untuk logout
  const logout = async () => {
    try {
      // Optional: Call API logout endpoint
      // await $api('/logout', { method: 'POST' })
      
      deleteUserData()
      
      // Redirect ke halaman login sesuai dengan role sebelumnya
      const router = useRouter()
      if (role.value === 'admin') {
        await router.push({ name: 'authentication-login' })
      } else {
        await router.push({ name: 'authentication-login' })
      }
    } catch (error) {
      console.error('Logout error:', error)
      // Tetap hapus data lokal meskipun API gagal
      deleteUserData()
    }
  }

  // Initialize activity tracking if user is already logged in (for page refresh)
  if (isLoggedIn.value) {
    startActivityTracking()
  }

  return {
    // State
    user,
    token,
    role,
    loginType,
    lastActivity,
    
    // Computed untuk mengambil data user
    getCurrentUser,
    getUserName,
    getUserEmail,
    getUserId,
    getUserRole,
    getLoginType,
    
    // Status login
    isLoggedIn,
    isCompanyUser,
    isAdmin,
    isRegularUser,
    
    // Access control
    canAccessAdminDashboard,
    canAccessUserPages,
    
    // Functions
    storeUserData,
    deleteUserData,
    hasRole,
    logout,
    updateLastActivity,
    checkSessionTimeout,
  }
}, {
  persist: {
    key: 'auth-store',
    storage: localStorage,
    paths: ['user', 'token', 'role', 'loginType', 'lastActivity']
  }
})

if (import.meta.hot)
  import.meta.hot.accept(acceptHMRUpdate(useAuthStore, import.meta.hot))
