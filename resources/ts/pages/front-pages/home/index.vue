<script setup lang="ts">
import americano from '@images/eCommerce/americano.svg'
import cappuccino from '@images/eCommerce/cappuccino.svg'
import espresso from '@images/eCommerce/espresso.svg'
import latte from '@images/eCommerce/latte.svg'
import macchiato from '@images/eCommerce/macchiato.svg'
import mocha from '@images/eCommerce/mocha.svg'

import QRCode from 'qrcode'
import { computed, nextTick, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// Reactive data
const currentPage = ref('barcode')
const customerForm = ref(null)
const customerData = ref({
  name: '',
  phone: '',
  email: ''
})

const nameRules = [
  (  v: any) => !!v || 'Nama wajib diisi',
  (  v: string | any[]) => (v && v.length >= 2) || 'Nama minimal 2 karakter',
  (  v: string | any[]) => (v && v.length <= 50) || 'Nama maksimal 50 karakter',
  (  v: string) => /^[a-zA-Z\s.]+$/.test(v) || 'Nama hanya boleh berisi huruf, spasi, dan titik'
]

const phoneRules = [
  (  v: any) => !!v || 'Nomor telepon wajib diisi',
  (  v: string) => /^(\+62|62|0)8[1-9][0-9]{6,11}$/.test(v) || 'Format nomor telepon tidak valid (contoh: 081234567890)',
  (  v: string | any[]) => (v && v.length >= 10) || 'Nomor telepon minimal 10 digit',
  (  v: string | any[]) => (v && v.length <= 15) || 'Nomor telepon maksimal 15 digit'
]

const emailRules = [
  (  v: string) => !v || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || 'Format email tidak valid'
]

// Methods
const formatPhoneNumber = (event: { target: { value: string } }) => {
  // Remove non-numeric characters except + at the beginning
  let value = event.target.value.replace(/[^\d+]/g, '')
  
  // If starts with +62, keep it
  if (value.startsWith('+62')) {
    value = '+62' + value.substring(3).replace(/[^\d]/g, '')
  }
  // If starts with 62, convert to +62
  else if (value.startsWith('62')) {
    value = '+62' + value.substring(2)
  }
  // If starts with 0, keep it
  else if (value.startsWith('0')) {
    // Keep as is
  }
  // If starts with 8, add 0
  else if (value.startsWith('8')) {
    value = '0' + value
  }
  
  customerData.value.phone = value
}

const isFormValid = computed(() => {
  return customerData.value.name.length >= 2 && 
         customerData.value.phone.length >= 10 &&
         (customerData.value.email === '' || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(customerData.value.email))
})

const products = ref([
  {
    id: 1,
    name: 'Espresso',
    price: 25000,
    description: 'Kopi espresso murni dengan rasa yang kuat dan aroma yang khas',
    image: espresso // path ke file SVG
  },
  {
    id: 2,
    name: 'Cappuccino',
    price: 35000,
    description: 'Perpaduan sempurna antara espresso, steamed milk, dan foam',
    image: cappuccino
  },
  {
    id: 3,
    name: 'Latte',
    price: 40000,
    description: 'Kopi latte dengan susu yang creamy dan lembut',
    image: latte
  },
  {
    id: 4,
    name: 'Americano',
    price: 30000,
    description: 'Espresso yang dicampur dengan air panas',
    image: americano
  },
  {
    id: 5,
    name: 'Macchiato',
    price: 38000,
    description: 'Espresso dengan sedikit steamed milk dan foam',
    image: macchiato
  },
  {
    id: 6,
    name: 'Mocha',
    price: 45000,
    description: 'Perpaduan kopi, cokelat, dan whipped cream',
    image: mocha
  }
])

const cartItems = ref([])
const selectedPayment = ref(null)
const barcodeCanvas = ref(null)

const paymentMethods = ref([
  {
    id: 'cash',
    name: 'Tunai',
    icon: 'tabler-cash'
  },
  {
    id: 'card',
    name: 'Kartu Kredit',
    icon: 'tabler-credit-card'
  },
  {
    id: 'ewallet',
    name: 'E-Wallet',
    icon: 'tabler-device-mobile'
  },
  {
    id: 'bank',
    name: 'Transfer Bank',
    icon: 'tabler-building-bank'
  }
])

// Computed properties
const cartTotal = computed(() => {
  return cartItems.value.reduce((total, item) => total + (item.price * item.quantity), 0)
})

const cartItemsCount = computed(() => {
  return cartItems.value.reduce((total, item) => total + item.quantity, 0)
})

// Methods
const generateBarcode = async () => {
  await nextTick()
  if (barcodeCanvas.value && typeof QRCode !== 'undefined') {
    QRCode.toCanvas(barcodeCanvas.value, 'https://coffee-shop.com/order', {
      width: 200,
      height: 200,
      margin: 2,
      color: {
        dark: '#2c3e50',
        light: '#ffffff'
      }
    })
  }
}

const scanBarcode = () => {
  currentPage.value = 'customer-form'
}

const submitCustomerData = async () => {
  const { valid } = await customerForm.value.validate()
  
  if (valid) {
    // Process form data
    console.log('Customer data:', customerData.value)
    
    // Navigate to next page
     currentPage.value = 'catalog'
  }
  
}

const addToCart = (product) => {
  const existingItem = cartItems.value.find(item => item.id === product.id)
  if (existingItem) {
    existingItem.quantity += 1
  } else {
    cartItems.value.push({
      ...product,
      quantity: 1
    })
  }
}

const removeFromCart = (productId) => {
  cartItems.value = cartItems.value.filter(item => item.id !== productId)
}

const increaseQuantity = (productId) => {
  const item = cartItems.value.find(item => item.id === productId)
  if (item) {
    item.quantity += 1
  }
}

const decreaseQuantity = (productId) => {
  const item = cartItems.value.find(item => item.id === productId)
  if (item && item.quantity > 1) {
    item.quantity -= 1
  }
}

const processPayment = () => {
  if (selectedPayment.value) {
    setTimeout(() => {
      currentPage.value = 'success'
    }, 1000)
  }
}

const getPaymentMethodName = (methodId) => {
  const method = paymentMethods.value.find(m => m.id === methodId)
  return method ? method.name : ''
}

const startNewOrder = () => {
  customerData.value = { name: '', phone: '', email: '' }
  cartItems.value = []
  selectedPayment.value = null
  currentPage.value = 'barcode'
  generateBarcode()
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('id-ID').format(price)
}

const goBack = () => {
  const pageFlow = ['barcode', 'customer-form', 'catalog', 'cart', 'payment', 'success']
  const currentIndex = pageFlow.indexOf(currentPage.value)
  if (currentIndex > 0) {
    currentPage.value = pageFlow[currentIndex - 1]
  }
}

const goNext = () => {
  const pageFlow = ['barcode', 'customer-form', 'catalog', 'cart', 'payment', 'success']
  const currentIndex = pageFlow.indexOf(currentPage.value)
  if (currentIndex < pageFlow.length - 1) {
    currentPage.value = pageFlow[currentIndex + 1]
  }
}

// Lifecycle
onMounted(() => {
  generateBarcode()
})

// Page meta
definePage({
  meta: {
    layout: 'blank',
    public: true,
  },
})
</script>

<template>
  <div class="coffee-shop-wrapper">
    <!-- Loading overlay -->
    <VOverlay
      v-if="currentPage === 'loading'"
      class="align-center justify-center"
      persistent
      contained
    >
      <VProgressCircular
        color="primary"
        indeterminate
        size="64"
      />
    </VOverlay>

    <!-- Main Container -->
    <VContainer
      fluid
      class="pa-0"
    >
      <!-- Barcode Page -->
      <VRow
        v-if="currentPage === 'barcode'"
        class="ma-0"
        style="min-height: 100vh;"
      >
        <VCol
          cols="12"
          class="d-flex align-center justify-center"
        >
          <VCard
            class="pa-8"
            max-width="600"
            elevation="12"
          >
            <VCardText class="text-center">
              <div class="mb-6">
                <VIcon
                  icon="tabler-coffee"
                  size="64"
                  color="primary"
                />
                <h1 class="text-h3 mt-4 mb-2">Coffee Shop</h1>
                <p class="text-body-1 text-medium-emphasis">
                  Scan barcode untuk mulai berbelanja
                </p>
              </div>

              <VCard
                variant="outlined"
                class="pa-8 mb-6"
                style="cursor: pointer;"
                @click="scanBarcode"
              >
                <canvas
                  ref="barcodeCanvas"
                  class="mx-auto d-block"
                />
                <div class="mt-4">
                  <VIcon
                    icon="tabler-qrcode"
                    size="48"
                    class="mb-2"
                  />
                  <p class="text-body-2">Klik untuk scan barcode</p>
                </div>
              </VCard>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Customer Form Page -->
      <VRow
        v-if="currentPage === 'customer-form'"
        class="ma-0"
        style="min-height: 100vh;"
      >
        <VCol
          cols="12"
          class="d-flex align-center justify-center"
        >
          <VCard
            class="pa-8"
            max-width="800"
            width="100%"
            elevation="12"
            style="margin: 0 16px;"
          >
            <VCardText>
              <div class="text-center mb-8">
                <VIcon
                  icon="tabler-user"
                  size="64"
                  color="primary"
                />
                <h1 class="text-h3 mt-4 mb-2">Data Pembeli</h1>
                <p class="text-body-1 text-medium-emphasis">
                  Silakan isi data diri Anda dengan lengkap
                </p>
              </div>

              <VForm 
                ref="customerForm"
                @submit.prevent="submitCustomerData"
              >
                <VRow>
                  <VCol cols="12">
                    <VTextField
                      v-model="customerData.name"
                      label="Nama Lengkap"
                      placeholder="Masukkan nama lengkap"
                      prepend-inner-icon="tabler-user"
                      :rules="nameRules"
                      required
                      counter="50"
                      variant="outlined"
                      class="mb-2"
                    />
                  </VCol>
                  
                  <VCol cols="12">
                    <VTextField
                      v-model="customerData.phone"
                      label="Nomor Telepon"
                      placeholder="Contoh: 081234567890"
                      prepend-inner-icon="tabler-phone"
                      :rules="phoneRules"
                      required
                      counter="15"
                      variant="outlined"
                      class="mb-2"
                      @input="formatPhoneNumber"
                    />
                  </VCol>
                  
                  <VCol cols="12">
                    <VTextField
                      v-model="customerData.email"
                      label="Email (Opsional)"
                      placeholder="Contoh: nama@email.com"
                      prepend-inner-icon="tabler-mail"
                      type="email"
                      :rules="emailRules"
                      variant="outlined"
                      class="mb-2"
                    />
                  </VCol>
                </VRow>

                <div class="d-flex justify-space-between mt-8">
                  <VBtn
                    color="default"
                    variant="outlined"
                    size="large"
                    @click="currentPage = 'barcode'"
                  >
                    <VIcon
                      icon="tabler-arrow-left"
                      start
                    />
                    Kembali
                  </VBtn>
                  
                  <VBtn
                    color="primary"
                    type="submit"
                    size="large"
                    :disabled="!isFormValid"
                  >
                    Selanjutnya
                    <VIcon
                      icon="tabler-arrow-right"
                      end
                    />
                  </VBtn>
                </div>
              </VForm>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Catalog Page -->
      <VRow
        v-if="currentPage === 'catalog'"
        class="ma-0"
        style="min-height: 100vh;"
      >
        <VCol
          cols="12"
          class="pa-6"
        >
          <div class="text-center mb-8">
            <VIcon
              icon="tabler-coffee"
              size="64"
              color="white"
            />
            <h1 class="text-h3 mt-4 mb-2">Katalog Kopi</h1>
            <p class="text-body-1 text-medium-emphasis">
              Pilih kopi favorit Anda
            </p>
          </div>

          <VRow>
            <VCol
              v-for="product in products"
              :key="product.id"
              cols="12"
              md="6"
              lg="4"
            >
              <VCard
                elevation="8"
                class="h-100"
              >
                <div
                  class="d-flex align-center justify-center"
                  style="height: 200px;background: #2c1810;"
                >
                  <VIcon
                    size="150"
                    :icon="product.image"
                  />
                </div>
                <VCardText>
                  <h3 class="text-h5 mb-2">{{ product.name }}</h3>
                  <p class="text-h6 text-error mb-3">
                    Rp {{ formatPrice(product.price) }}
                  </p>
                  <p class="text-body-2 text-medium-emphasis mb-4">
                    {{ product.description }}
                  </p>
                  <VBtn
                    color="primary"
                    block
                    @click="addToCart(product)"
                  >
                    <VIcon
                      icon="tabler-plus"
                      start
                    />
                    Tambah ke Keranjang
                  </VBtn>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>

          <div class="d-flex justify-space-between align-center mt-8">
            <VBtn
              color="default"
              variant="outlined"
              @click="currentPage = 'customer-form'"
            >
              <VIcon
                icon="tabler-arrow-left"
                start
              />
              Kembali
            </VBtn>
            <VBtn
              color="success"
              @click="currentPage = 'cart'"
            >
              <VIcon
                icon="tabler-shopping-cart"
                start
              />
              Keranjang
              <VBadge
                v-if="cartItemsCount > 0"
                :content="cartItemsCount"
                color="error"
                inline
              />
            </VBtn>
          </div>
        </VCol>
      </VRow>

      <!-- Cart Page -->
      <VRow
        v-if="currentPage === 'cart'"
        class="ma-0"
        style="min-height: 100vh;"
      >
        <VCol
          cols="12"
          class="pa-6"
        >
          <div class="text-center mb-8">
            <VIcon
              icon="tabler-shopping-cart"
              size="64"
              color="white"
            />
            <h1 class="text-h3 mt-4 mb-2">Keranjang Belanja</h1>
            <p class="text-body-1 text-medium-emphasis">
              Review pesanan Anda
            </p>
          </div>

          <VCard
            v-if="cartItems.length === 0"
            class="text-center pa-8"
          >
            <VIcon
              icon="tabler-shopping-cart"
              size="64"
              color="grey"
            />
            <h3 class="text-h5 mt-4 mb-4">Keranjang masih kosong</h3>
            <VBtn
              color="primary"
              @click="currentPage = 'catalog'"
            >
              <VIcon
                icon="tabler-arrow-left"
                start
              />
              Kembali ke Katalog
            </VBtn>
          </VCard>

          <div v-else>
            <VCard class="mb-6">
              <div
                v-for="(item, index) in cartItems"
                :key="item.id"
                class="pa-4"
                :class="{ 'border-b': index < cartItems.length - 1 }"
              >
                <VRow align="center">
                  <VCol
                    cols="auto"
                    class="pe-4"
                  >
                    <VAvatar
                      size="60"
                      color="brown"
                    >
                      <VIcon
                        icon="tabler-coffee"
                        color="white"
                      />
                    </VAvatar>
                  </VCol>
                  <VCol>
                    <h4 class="text-h6 mb-1">{{ item.name }}</h4>
                    <p class="text-error font-weight-medium">
                      Rp {{ formatPrice(item.price) }}
                    </p>
                    <div class="d-flex align-center mt-2">
                      <VBtn
                        icon="tabler-minus"
                        size="small"
                        variant="outlined"
                        @click="decreaseQuantity(item.id)"
                      />
                      <span class="mx-3 text-h6">{{ item.quantity }}</span>
                      <VBtn
                        icon="tabler-plus"
                        size="small"
                        variant="outlined"
                        @click="increaseQuantity(item.id)"
                      />
                      <VBtn
                        icon="tabler-trash"
                        size="small"
                        color="error"
                        variant="outlined"
                        class="ms-3"
                        @click="removeFromCart(item.id)"
                      />
                    </div>
                  </VCol>
                </VRow>
              </div>
            </VCard>

            <VCard class="pa-4 mb-6">
              <div class="text-end">
                <h3 class="text-h4">Total: Rp {{ formatPrice(cartTotal) }}</h3>
              </div>
            </VCard>

            <div class="d-flex justify-space-between">
              <VBtn
                color="default"
                variant="outlined"
                @click="currentPage = 'catalog'"
              >
                <VIcon
                  icon="tabler-arrow-left"
                  start
                />
                Kembali
              </VBtn>
              <VBtn
                color="success"
                @click="currentPage = 'payment'"
              >
                Lanjut Pembayaran
                <VIcon
                  icon="tabler-arrow-right"
                  end
                />
              </VBtn>
            </div>
          </div>
        </VCol>
      </VRow>

      <!-- Payment Page -->
      <VRow
        v-if="currentPage === 'payment'"
        class="ma-0"
        style="min-height: 100vh;"
      >
        <VCol
          cols="12"
          class="pa-6"
        >
          <div class="text-center mb-8">
            <VIcon
              icon="tabler-credit-card"
              size="64"
              color="white"
            />
            <h1 class="text-h3 mt-4 mb-2">Pembayaran</h1>
            <p class="text-body-1 text-medium-emphasis">
              Pilih metode pembayaran
            </p>
          </div>

          <VCard class="pa-4 mb-6">
            <h3 class="text-h5 mb-4">Ringkasan Pesanan</h3>
            <div
              v-for="item in cartItems"
              :key="item.id"
              class="d-flex justify-space-between mb-2"
            >
              <span>{{ item.name }} x{{ item.quantity }}</span>
              <span>Rp {{ formatPrice(item.price * item.quantity) }}</span>
            </div>
            <VDivider class="my-4" />
            <div class="d-flex justify-space-between">
              <h4 class="text-h5">Total</h4>
              <h4 class="text-h5">Rp {{ formatPrice(cartTotal) }}</h4>
            </div>
          </VCard>

          <VCard class="pa-4 mb-6">
          <h3 class="text-h5 mb-4">Metode Pembayaran</h3>
          <VRow class="mb-6">
            <VCol
              v-for="method in paymentMethods"
              :key="method.id"
              cols="12"
              md="6"
            >
              <VCard
                variant="outlined"
                class="pa-4 text-center"
                :class="{ 'border-primary': selectedPayment === method.id }"
                style="cursor: pointer;"
                @click="selectedPayment = method.id"
              >
                <VIcon
                  :icon="method.icon"
                  size="48"
                  color="white"
                  class="mb-2"
                />
                <h4 class="text-h6">{{ method.name }}</h4>
              </VCard>
            </VCol>
          </VRow>
          </VCard>
          <div class="d-flex justify-space-between">
            <VBtn
              color="default"
              variant="outlined"
              @click="currentPage = 'cart'"
            >
              <VIcon
                icon="tabler-arrow-left"
                start
              />
              Kembali
            </VBtn>
            <VBtn
              color="success"
              :disabled="!selectedPayment"
              @click="processPayment"
            >
              <VIcon
                icon="tabler-check"
                start
              />
              Bayar Sekarang
            </VBtn>
          </div>
        </VCol>
      </VRow>

      <!-- Success Page -->
      <VRow
        v-if="currentPage === 'success'"
        class="ma-0"
        style="min-height: 100vh;"
      >
        <VCol
          cols="12"
          class="d-flex align-center justify-center"
        >
          <VCard
            class="pa-8 text-center"
            max-width="600"
            elevation="12"
          >
            <VIcon
              icon="tabler-check"
              size="80"
              color="success"
              class="mb-4"
            />
            <h1 class="text-h3 mb-2 text-success">Pembayaran Berhasil!</h1>
            <p class="text-body-1 text-medium-emphasis mb-6">
              Terima kasih atas pesanan Anda
            </p>

            <VCard
              variant="outlined"
              class="pa-4 mb-6"
            >
              <h3 class="text-h5 mb-4">Detail Pesanan</h3>
              <div class="text-start">
                <p><strong>Nama:</strong> {{ customerData.name }}</p>
                <p><strong>Telepon:</strong> {{ customerData.phone }}</p>
                <p><strong>Total:</strong> Rp {{ formatPrice(cartTotal) }}</p>
                <p><strong>Metode Pembayaran:</strong> {{ getPaymentMethodName(selectedPayment) }}</p>
              </div>
            </VCard>

            <VBtn
              color="primary"
              size="large"
              @click="startNewOrder"
            >
              <VIcon
                icon="tabler-plus"
                start
              />
              Pesanan Baru
            </VBtn>
          </VCard>
        </VCol>
      </VRow>
    </VContainer>
  </div>
</template>

<style lang="scss" scoped>
.coffee-shop-wrapper {
  min-height: 100vh;
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)) 0%, rgb(var(--v-theme-secondary)) 100%);
}

.border-b {
  border-bottom: 1px solid rgb(var(--v-border-color));
}

.border-primary {
  border-color: rgb(var(--v-theme-primary)) !important;
}

@media (max-width: 960px) and (min-width: 600px) {
  .coffee-shop-wrapper {
    .v-container {
      padding-inline: 2rem !important;
    }
  }
}
</style>
