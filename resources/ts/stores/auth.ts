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
  const sessionTimeout = 30 * 60 * 1000 

  const AUTH_STORAGE_KEY = 'papasans-auth'

  function saveToStorage() {
    try {
      const authData = {
        user: user.value,
        token: token.value,
        role: role.value,
        loginType: loginType.value,
        lastActivity: lastActivity.value
      }
      localStorage.setItem(AUTH_STORAGE_KEY, JSON.stringify(authData))
      console.log('Auth data saved to localStorage:', authData)
    } catch (error) {
      console.error('Failed to save auth data to localStorage:', error)
    }
  }

  function loadFromStorage() {
    try {
      const storedData = localStorage.getItem(AUTH_STORAGE_KEY)
      if (storedData) {
        const authData = JSON.parse(storedData)
        console.log('Loading auth data from localStorage:', authData)
        
        user.value = authData.user
        token.value = authData.token
        role.value = authData.role
        loginType.value = authData.loginType
        lastActivity.value = authData.lastActivity
        
        return true
      }
      return false
    } catch (error) {
      console.error('Failed to load auth data from localStorage:', error)
      return false
    }
  }

  function clearStorage() {
    try {
      localStorage.removeItem(AUTH_STORAGE_KEY)
      console.log('Auth data cleared from localStorage')
    } catch (error) {
      console.error('Failed to clear auth data from localStorage:', error)
    }
  }

  function storeUserData(userData: UserData) {
    user.value = userData.user
    token.value = userData.token
    role.value = userData.role
    loginType.value = userData.login_type
    lastActivity.value = Date.now()
    
    saveToStorage()
    
    startActivityTracking()
  }

  function deleteUserData() {
    user.value = null
    token.value = null
    role.value = null
    loginType.value = null
    lastActivity.value = null
    
    clearStorage()
    
    // Stop activity tracking
    stopActivityTracking()
  }

  function updateLastActivity() {
    if (isLoggedIn.value) {
      lastActivity.value = Date.now()
      // Update localStorage immediately
      saveToStorage()
    }
  }

  // Activity tracking variables
  let activityTimer: NodeJS.Timeout | null = null
  let visibilityTimer: NodeJS.Timeout | null = null
  let activityHandlers: Array<() => void> = []
  let isTrackingActive = false

  function startActivityTracking() {
    // Hanya start jika belum ada tracking yang aktif
    if (isTrackingActive) {
      console.log('Activity tracking already active')
      return
    }
    
    console.log('Starting activity tracking')
    isTrackingActive = true
    
    // Track user activity (mouse, keyboard, touch)
    const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click']
    
    const handleActivity = () => {
      updateLastActivity()
    }

    // Store handlers untuk cleanup nanti
    activityHandlers = activityEvents.map(() => handleActivity)
    
    activityEvents.forEach((event, index) => {
      document.addEventListener(event, activityHandlers[index], { passive: true })
    })

    // Track page visibility changes
    const handleVisibilityChange = () => {
      if (document.hidden) {
        // Page is hidden, start timer
        if (visibilityTimer) clearTimeout(visibilityTimer)
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

    // Periodic check setiap 5 menit
    activityTimer = setInterval(() => {
      const now = Date.now()
      const timeSinceLastActivity = lastActivity.value ? now - lastActivity.value : 0
      
      // Hanya check timeout jika idle lebih dari 25 menit
      if (timeSinceLastActivity > (25 * 60 * 1000)) {
        checkSessionTimeout()
      }
    }, 5 * 60 * 1000) // Check every 5 minutes
  }

  function stopActivityTracking() {
    if (!isTrackingActive) return
    
    console.log('Stopping activity tracking')
    isTrackingActive = false
    
    // Remove event listeners
    const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click']
    
    activityEvents.forEach((event, index) => {
      if (activityHandlers[index]) {
        document.removeEventListener(event, activityHandlers[index])
      }
    })

    // Clear handlers array
    activityHandlers = []

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

      console.log('Checking session timeout:', {
        timeSinceLastActivity: timeSinceLastActivity / 1000 / 60,
        sessionTimeout: sessionTimeout / 1000 / 60
      })

      if (timeSinceLastActivity > sessionTimeout) {
        console.log('Session expired after', timeSinceLastActivity / 1000 / 60, 'minutes')
        logout('session_timeout')
        resolve(true)
      } else {
        resolve(false)
      }
    })
  }

  function initializeStore() {
    const loaded = loadFromStorage()
    
    if (loaded && isLoggedIn.value) {
      
      if (lastActivity.value) {
        const now = Date.now()
        const timeSinceLastActivity = now - lastActivity.value
        
        
        if (timeSinceLastActivity > sessionTimeout) {
          deleteUserData()
        } else {
          updateLastActivity()
          startActivityTracking()
        }
      } else {
        updateLastActivity()
        startActivityTracking()
      }
    }
  }

  // ========== COMPUTED PROPERTIES ==========
  const getCurrentUser = computed(() => user.value)
  const getUserName = computed(() => user.value?.name || '')
  const getUserEmail = computed(() => user.value?.email || '')
  const getUserId = computed(() => user.value?.id || null)
  const getUserRole = computed(() => role.value || '')
  const getLoginType = computed(() => loginType.value || '')
  const isLoggedIn = computed(() => !!(user.value && token.value))

  const hasRole = (requiredRole: string) => role.value === requiredRole
  const isCompanyUser = computed(() => loginType.value === 'company' && role.value === 'user')
  const isAdmin = computed(() => role.value === 'admin')
  const isRegularUser = computed(() => role.value === 'user')
  const canAccessAdminDashboard = computed(() => isLoggedIn.value && (role.value === 'admin' || role.value === 'super_admin'))
  const canAccessUserPages = computed(() => isLoggedIn.value && role.value === 'user')

  const logout = async (reason?: string) => {
    try {
      console.log('Logging out due to:', reason || 'manual logout')
      
      if (token.value) {
        try {
          await $api('/logout', { 
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${token.value}`,
              'Accept': 'application/json'
            }
          })
        } catch (apiError) {
          console.warn('API logout failed:', apiError)
        }
      }
      
      deleteUserData()
      
      const router = useRouter()
      await router.push({ name: 'authentication-login' })
      
    } catch (error) {
      console.error('Logout error:', error)
      deleteUserData()
    }
  }

  const refreshSession = () => {
    if (isLoggedIn.value) {
      updateLastActivity()
      console.log('Session refreshed')
    }
  }

  // Initialize store when created
  initializeStore()

  return {
    // State
    user,
    token,
    role,
    loginType,
    lastActivity,
    sessionTimeout,
    
    // Computed
    getCurrentUser,
    getUserName,
    getUserEmail,
    getUserId,
    getUserRole,
    getLoginType,
    isLoggedIn,
    isCompanyUser,
    isAdmin,
    isRegularUser,
    canAccessAdminDashboard,
    canAccessUserPages,
    
    // Functions
    storeUserData,
    deleteUserData,
    hasRole,
    logout,
    updateLastActivity,
    checkSessionTimeout,
    refreshSession,
    initializeStore,
    loadFromStorage,
    saveToStorage,
    startActivityTracking,
    stopActivityTracking,
  }
})

if (import.meta.hot)
  import.meta.hot.accept(acceptHMRUpdate(useAuthStore, import.meta.hot))
