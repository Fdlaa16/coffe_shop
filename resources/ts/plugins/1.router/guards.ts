import { useAuthStore } from '@/stores/auth'
import { storeToRefs } from 'pinia'
import type { RouteNamedMap, _RouterTyped } from 'unplugin-vue-router'

export const setupGuards = (router: _RouterTyped<RouteNamedMap & { [key: string]: any }>) => {
  router.beforeEach(async (to) => {
    const authStore = useAuthStore()
    const { isLoggedIn } = storeToRefs(authStore)
    const role = authStore.role

    await authStore.checkSessionTimeout()

    const authPages = ['authentication-login', 'authentication-register']

    // Kalau sudah login, jangan ke halaman login/register lagi
    if (authPages.includes(to.name as string)) {
      if (isLoggedIn.value) {
        return role === 'admin'
          ? { name: 'dashboards-customer-list' }
          : { name: 'front-pages-home' }
      }
      return undefined
    }

    // Halaman public boleh diakses semua
    if (to.meta.public) return undefined

    // Semua route dashboard + profile hanya untuk admin
    const adminRoutes = [
      'dashboards-customer-add', 'dashboards-customer-edit-id', 'dashboards-customer-list',
      'dashboards-menu-add', 'dashboards-menu-edit-id', 'dashboards-menu-list',
      'dashboards-table-add', 'dashboards-table-edit-id', 'dashboards-table-list',
      'dashboards-order-add', 'dashboards-order-edit-id', 'dashboards-order-list',
      'dashboards-invoice-add', 'dashboards-invoice-edit-id', 'dashboards-invoice-list',
      'dashboards-profile', 'profile'
    ]

    const isAdminRoute =
      adminRoutes.includes(to.name as string) ||
      to.name?.toString().includes('dashboard')

    // Kalau butuh login tapi belum login → lempar ke login
    if (isAdminRoute && !isLoggedIn.value) {
      return { name: 'authentication-login' }
    }

    // Kalau sudah login tapi bukan admin → lempar balik ke front-pages-home
    if (isAdminRoute && role !== 'admin') {
      return { name: 'front-pages-home' }
    }

    // Admin jangan sampai nyasar ke halaman user (front-pages-home tetap bisa sih kalau mau)
    if (role === 'admin' && to.name === 'front-pages-home') {
      return { name: 'dashboards-customer-list' }
    }

    return undefined
  })
}
