<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import authV1BottomShape from '@images/svg/auth-v1-bottom-shape.svg?raw'
import authV1TopShape from '@images/svg/auth-v1-top-shape.svg?raw'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'

definePage({
  meta: {
    layout: 'blank',
    public: true,
    x: 'login'
  },
})

onMounted(() => {
  if (route.query.success) {
    snackbarMessage.value = String(route.query.success)
    snackbarColor.value = 'success'
    isFlatSnackbarVisible.value = true

    setTimeout(() => {
      router.replace({ query: {} })
    }, 100)
  }
})

const authStore = useAuthStore()
const route = useRoute()
const router = useRouter()
const isFlatSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref<'success' | 'error'>('success')
const isSnackbarVisible = ref(false)


const rulesPassword = {
  required: (value: string) => !!value || 'Password harus diisi',
  minLength: (value: string) => value.length >= 8 || 'Minimal 8 karakter',
}

const rulesConfirmPassword = {
  required: (value: string) => !!value || 'Konfirmasi password harus diisi',
  sameAsPassword: (value: string) => value === localData.value.password || 'Konfirmasi password tidak cocok',
}


const localData = ref({
  email: '',
  password: '',
  remember: false,
})

const isPasswordVisible = ref(false)

const onSubmit = async () => {
    const loginData = {
        email: localData.value.email,
        password: localData.value.password
    }
    
    try {
        const response = await $api('login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(loginData)
        });
        
        const { role, user, token, message, login_type } = response;
        
        // Store company user data
        authStore.storeUserData({
            user: user,
            token: token,
            role: role,
            login_type: login_type
        });
        
        snackbarMessage.value = message || 'Company Login Berhasil!';
        snackbarColor.value = 'success';
        isFlatSnackbarVisible.value = true;
        
        // Company routing
        if (role === 'admin') {
            await router.push({ name: 'dashboards-customer-list' });
        } else {
            snackbarMessage.value = 'Unauthorized access to company portal2';
            snackbarColor.value = 'error';
            isFlatSnackbarVisible.value = true;
        }
        
    } catch (err) {
        handleLoginError(err);
    }
}

const handleLoginError = (err: any) => {
    console.error('Login error:', err);
    
    if (err?.status === 422 && err?.data?.errors) {
        const messages = Object.values(err.data.errors).flat();
        snackbarMessage.value = 'Validasi gagal: ' + messages.join(', ');
    } else if (err?.status === 401) {
        snackbarMessage.value = 'Email atau password salah';
    } else if (err?.status === 403) {
        snackbarMessage.value = err?.data?.message || 'Akses tidak diizinkan';
    } else {
        snackbarMessage.value = 'Gagal login: ' + (err?.data?.message || err?.message || 'Unknown error');
    }
    
    snackbarColor.value = 'error';
    isFlatSnackbarVisible.value = true;
}

</script>

<template>
  <div class="auth-wrapper d-flex align-center justify-center pa-4">
    <div class="position-relative my-sm-16">
      <!-- 👉 Top shape -->
      <VNodeRenderer
        :nodes="h('div', { innerHTML: authV1TopShape })"
        class="text-primary auth-v1-top-shape d-none d-sm-block"
      />

      <!-- 👉 Bottom shape -->
      <VNodeRenderer
        :nodes="h('div', { innerHTML: authV1BottomShape })"
        class="text-primary auth-v1-bottom-shape d-none d-sm-block"
      />

      <!-- 👉 Auth Card -->
      <VCard
        class="auth-card"
        max-width="460"
        :class="$vuetify.display.smAndUp ? 'pa-6' : 'pa-0'"
      >
        <VCardItem class="justify-center">
          <VCardTitle>
            <div class="app-logo">
              <img
                src="/images/logo/papasans.png"
                alt="logo papasans"
                class="app-logo-img"
                style="height: 170px;"
              />
            </div>
          </VCardTitle>
        </VCardItem>

        <VCardText>
          <h4 class="text-h4 text-center mb-1">
            PAPASANS COFFEE SHOP
          </h4>
          <p class="text-center mb-0">
            Silakan masuk ke akun Anda dan mulai petualangan
          </p>
        </VCardText>

        <VCardText>
          <VForm @submit.prevent="() => {onSubmit()}">
            <VRow>
              <!-- email -->
              <VCol cols="12">
                <AppTextField
                  v-model="localData.email"
                  autofocus
                  label="Email"
                  type="email"
                  placeholder="budi@email.com"
                />
              </VCol>

              <!-- password -->
              <VCol cols="12">
                <AppTextField
                  v-model="localData.password"
                  label="Password"
                  placeholder="············"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  autocomplete="password"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                  :rules="[rulesPassword.required, rulesPassword.minLength]"
                />

                <!-- remember me checkbox -->
                <!-- <div class="d-flex align-center justify-space-between flex-wrap my-6">
                  <VCheckbox
                    v-model="localData.remember"
                    label="Remember me"
                  />

                  <RouterLink
                    class="text-primary"
                    :to="{ name: 'pages-authentication-forgot-password-v1' }"
                  >
                    Forgot Password?
                  </RouterLink>
                </div> -->

                <!-- login button -->
                <VBtn
                  block
                  type="submit"
                  class="mt-5"
                >
                  Login
                </VBtn>
              </VCol>

              <!-- <VCol
                cols="12"
                class="d-flex align-center"
              >
                <VDivider />
                <span class="mx-4 text-high-emphasis">or</span>
                <VDivider />
              </VCol> -->

              <!-- auth providers -->
              <!-- <VCol
                cols="12"
                class="text-center"
              >
                <AuthProvider />
              </VCol> -->
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </div>
  </div>

  <VSnackbar
    v-model="isFlatSnackbarVisible"
    :color="snackbarColor"
    location="bottom start"
    variant="flat"
    timeout="3000"
  >
    {{ snackbarMessage }}
  </VSnackbar>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth";
</style>
