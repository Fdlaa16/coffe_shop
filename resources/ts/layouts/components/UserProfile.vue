<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import { useAbility } from '@casl/vue'
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

// Store dan Router
const authStore = useAuthStore()
const route = useRoute()
const router = useRouter()
const ability = useAbility()
const avatarPreview = ref<string | null>(null)

// ✅ Interface yang disesuaikan dengan struktur API
interface User {
  id: number
  name: string
  email: string
  role: string
}

interface Avatar {
  url: string
}

interface UserData {
  id: number
  user_id: number
  name: string
  date_of_birth: string
  department: string
  avatar: Avatar | null
  user: User
}

// Ref user data
const userData = ref<UserData | null>(null)
const isLoading = ref(true)
const error = ref<string | null>(null)

// Fetch data user
const fetchUserProfile = async () => {
  try {
    isLoading.value = true
    error.value = null
    
    const res = await $api('profile')
    userData.value = res.data

    // Set avatar preview jika ada
    if (userData.value?.avatar?.url) {
      avatarPreview.value = getImageUrl(userData.value.avatar.url)
    }
  } catch (err) {
    console.error('Error fetching profile:', err)
    error.value = 'Gagal memuat data profil'
  } finally {
    isLoading.value = false
  }
}

const getImageUrl = (path: string) => {
  return import.meta.env.VITE_APP_URL + path
}

// Ambil data saat komponen dimount
onMounted(() => {
  fetchUserProfile()
})

// Fungsi logout
const onLogout = async () => {
  try {
    const loginType = authStore.loginType
    const endpoint = loginType === 'dashboard' ? '/logout' : '/company/logout'

    await $api(endpoint, {
      method: 'POST'
    })

    authStore.deleteUserData()
    await router.push({ name: 'authentication-login' })

  } catch (err) {
    console.error('Logout error:', err)
    authStore.deleteUserData()
    await router.push({ name: 'authentication-login' })
  }
}

// List menu profil
const userProfileList = [
  { type: 'divider' },
  { type: 'navItem', icon: 'tabler-user', title: 'Profile', to: { name: 'dashboards-profile' } },
]

const departments = [
  { title: 'Ketua Umum', value: 'chief' },
  { title: 'Official', value: 'official' }, 
  { title: 'Admin', value: 'admin' }, 
  { title: 'Pelatih', value: 'coach' }
]

// Computed properties untuk kemudahan akses data
const displayName = computed(() => userData.value?.name || userData.value?.user?.name || 'User')
const displayRole = computed(() => userData.value?.user?.role || 'Unknown Role')
const displayDepartment = computed(() => {
  const deptValue = userData.value?.department
  const found = departments.find(d => d.value === deptValue)
  return found ? found.title : 'No Department'
})
</script>

<template>
  <VBadge
    dot
    bordered
    location="bottom right"
    offset-x="1"
    offset-y="2"
    color="success"
  >
    <VAvatar
      size="38"
      class="cursor-pointer"
      :color="!(userData && userData.avatar) ? 'primary' : undefined"
      :variant="!(userData && userData.avatar) ? 'tonal' : undefined"
    >
      <!-- <VImg
        v-if="userData && userData.avatar"
        :src="userData.avatar"
      /> -->
      <!-- v-else -->
      <VIcon
        icon="tabler-user"
      />

      <!-- SECTION Menu -->
      <VMenu
        activator="parent"
        width="240"
        location="bottom end"
        offset="12px"
      >
        <VList>
          <VListItem>
            <div class="d-flex gap-2 align-center">
              <VListItemAction>
                <VBadge
                  dot
                  location="bottom right"
                  offset-x="3"
                  offset-y="3"
                  color="success"
                  bordered
                >
                  <VAvatar color="primary" variant="tonal">
                    <template v-if="avatarPreview">
                      <img
                        :src="avatarPreview"
                        alt="Avatar"
                        style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;"
                      />
                    </template>
                    <VIcon v-else icon="tabler-user" />
                  </VAvatar>
                </VBadge>
              </VListItemAction>

              <div>
                <!-- <h6 class="text-h6 font-weight-medium">
                  {{ userData.fullName || userData.username }}
                </h6>
                <VListItemSubtitle class="text-capitalize text-disabled">
                  {{ userData.role }}
                </VListItemSubtitle> -->
              </div>
            </div>
          </VListItem>

          <PerfectScrollbar :options="{ wheelPropagation: false }">
            <template v-for="item in userProfileList" :key="item.title">
              <VListItem
                v-if="item.type === 'navItem'"
                :to="item.to"
              >
                <template #prepend>
                  <VIcon :icon="item.icon" size="22" />
                </template>
                <VListItemTitle>{{ item.title }}</VListItemTitle>
              </VListItem>

              <VDivider v-else class="my-2" />
            </template>

            <div class="px-4 py-2">
              <VBtn
                block
                size="small"
                color="error"
                append-icon="tabler-logout"
                :disabled="isLoading"
                @click="onLogout"
              >
                Logout
              </VBtn>
            </div>
          </PerfectScrollbar>
        </VList>
      </VMenu>
    </VAvatar>
  </VBadge>
</template>
