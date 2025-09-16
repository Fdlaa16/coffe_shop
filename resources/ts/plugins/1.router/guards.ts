import { useAuthStore } from '@/stores/auth'
import { storeToRefs } from 'pinia'
import type { RouteNamedMap, _RouterTyped } from 'unplugin-vue-router'

export const setupGuards = (router: _RouterTyped<RouteNamedMap & { [key: string]: any }>) => {
  router.beforeEach(async (to, from) => {
    
    const authStore = useAuthStore()
    
    const storedAuth = localStorage.getItem('papasans-auth')
    
    if (storedAuth) {
      try {
        const parsed = JSON.parse(storedAuth)
        console.log('Parsed localStorage:', parsed)
      } catch (e) {
        console.error('Failed to parse localStorage:', e)
      }
    }
    
    if (!authStore.isLoggedIn && storedAuth) {
      authStore.loadFromStorage()
    }
    
    const { isLoggedIn } = storeToRefs(authStore)
    const role = authStore.role

    if (isLoggedIn.value) {
      authStore.refreshSession()
    }

    const authPages = ['authentication-login']
    
    if (authPages.includes(to.name as string)) {      
      if (isLoggedIn.value) {
        if (role === 'admin') {
          return { name: 'dashboards-customer-list' }
        } else if (role === 'user') {
          return { name: 'landing-page-id-table' }
        } else {
          return { name: 'landing-page-id-table' }
        }
      }

      return undefined
    }

    if (to.meta.public) {
      return undefined
    }

    const adminRoutes = [
      'dashboards-customer-add', 'dashboards-customer-edit-id', 'dashboards-customer-list',
      'dashboards-menu-add', 'dashboards-menu-edit-id', 'dashboards-menu-list',
      'dashboards-table-add', 'dashboards-table-edit-id', 'dashboards-table-list',
      'dashboards-order-add', 'dashboards-order-edit-id', 'dashboards-order-list',
      'dashboards-invoice-add', 'dashboards-invoice-edit-id', 'dashboards-invoice-list',
    ]

    const userRoutes = [
      'landing-page-id-table'
    ]

    const authRequiredRoutes = ['dashboards-profile']

    const needsAuth = adminRoutes.includes(to.name as string) || 
                     userRoutes.includes(to.name as string) || 
                     authRequiredRoutes.includes(to.name as string) ||
                     to.name?.toString().includes('dashboard') || 
                     to.meta.requiresAuth

    console.log('Route needs auth:', needsAuth)

    if (needsAuth && !isLoggedIn.value) {
      return { name: 'authentication-login' }
    }

    if (isLoggedIn.value) {      
      if (role === 'admin' && userRoutes.includes(to.name as string)) {
        return { name: 'dashboards-customer-list' }
      }
      
      if (role === 'user' && adminRoutes.includes(to.name as string)) {
        return { name: 'landing-page-id-table' }
      }
      
      if (role === 'admin' && (to.name?.toString().includes('user') || to.meta.requiresUser)) {
        return { name: 'dashboards-customer-list' }
      }
      
      if (role === 'user' && (
        to.name?.toString().startsWith('dashboards-') || 
        to.name?.toString().includes('admin') ||
        to.meta.requiresAdmin
      )) {
        return { name: 'landing-page-id-table' }
      }
    }

    return undefined
  })

  router.afterEach((to, from, failure) => {
    if (failure) {
      console.error('Navigation failed:', failure)
    } else {
      console.log('✅ Navigation completed from', from.name, 'to', to.name)
    }
  })
}
