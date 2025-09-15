// composables/useAuth.ts
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

export const useAuth = () => {
  const authStore = useAuthStore()
  const router = useRouter()

  const logout = async () => {
    try {
      // Call logout API jika user masih memiliki token
      if (authStore.token) {
        await $api('logout', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${authStore.token}`
          }
        });
      }
    } catch (error) {
      console.error('Logout API error:', error);
      // Tetap lanjutkan logout meskipun API gagal
    } finally {
      // Clear auth data from store
      authStore.deleteUserData();
      
      // Redirect to login page
      await router.push({ name: 'authentication-login' });
    }
  }

  return {
    logout,
    user: authStore.user,
    token: authStore.token,
    isLoggedIn: authStore.isLoggedIn,
    isCompanyUser: authStore.isCompanyUser,
  }
}

// Contoh penggunaan di component (misalnya di navbar atau sidebar)
/*
<script setup>
import { useAuth } from '@/composables/useAuth'

const { logout, user, isLoggedIn } = useAuth()

const handleLogout = async () => {
  const confirm = await $swal.fire({
    title: 'Konfirmasi Logout',
    text: 'Apakah Anda yakin ingin keluar?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Keluar',
    cancelButtonText: 'Batal'
  })

  if (confirm.isConfirmed) {
    await logout()
  }
}
</script>

<template>
  <div v-if="isLoggedIn">
    <p>Welcome, {{ user?.name }}!</p>
    <VBtn @click="handleLogout">Logout</VBtn>
  </div>
</template>
*/
