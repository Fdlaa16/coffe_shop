import { useAuthStore } from '@/stores/auth'
import { ofetch } from 'ofetch'

export const $api = ofetch.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  async onRequest({ options }) {
    const authStore = useAuthStore()
    options.headers.set('Accept', 'application/json')
    if (authStore && authStore.token)
      options.headers.append('Authorization', `Bearer ${authStore.token}`)
  },
})
