<script setup lang="ts">

import axios from 'axios'
import QRCode from 'qrcode'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// Page navigation state
const currentPage = ref('barcode')

// Reactive data for responsive design
const windowWidth = ref(window.innerWidth)
const windowHeight = ref(window.innerHeight)
const isMobile = computed(() => windowWidth.value < 768)
const isTablet = computed(() => windowWidth.value >= 768 && windowWidth.value < 1024)
const isDesktop = computed(() => windowWidth.value >= 1024)

// Form and order data
const customerForm = ref(null)
const orderType = ref('takeaway') // 'takeaway' or 'delivery'

const customerData = ref({
  name: '',
  phone: '',
  email: ''
})

// Validation rules
const nameRules = [
  (v: any) => !!v || 'Nama wajib diisi',
  (v: string | any[]) => (v && v.length >= 2) || 'Nama minimal 2 karakter',
  (v: string | any[]) => (v && v.length <= 50) || 'Nama maksimal 50 karakter',
  (v: string) => /^[a-zA-Z\s.]+$/.test(v) || 'Nama hanya boleh berisi huruf, spasi, dan titik'
]

const phoneRules = [
  (v: any) => !!v || 'Nomor telepon wajib diisi',
  (v: string) => /^(\+62|62|0)8[1-9][0-9]{6,11}$/.test(v) || 'Format nomor telepon tidak valid (contoh: 081234567890)',
  (v: string | any[]) => (v && v.length >= 10) || 'Nomor telepon minimal 10 digit',
  (v: string | any[]) => (v && v.length <= 15) || 'Nomor telepon maksimal 15 digit'
]

const emailRules = [
  (v: string) => !v || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || 'Format email tidak valid'
]

const formatPhoneNumber = (event: { target: { value: string } }) => {
  let value = event.target.value.replace(/[^\d+]/g, '')
  
  if (value.startsWith('+62')) {
    value = '+62' + value.substring(3).replace(/[^\d]/g, '')
  } else if (value.startsWith('62')) {
    value = '+62' + value.substring(2)
  } else if (value.startsWith('0')) {
    // Keep as is
  } else if (value.startsWith('8')) {
    value = '0' + value
  }
  
  customerData.value.phone = value
}

const isFormValid = computed(() => {
  return customerData.value.name.length >= 2 && 
         customerData.value.phone.length >= 10 &&
         (customerData.value.email === '' || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(customerData.value.email))
})

// Navigation methods
const cartItems = ref([])
const barcodeCanvas = ref(null)

// Function to reset all data to initial state
const resetAllData = () => {
  // Reset customer data
  customerData.value = {
    name: '',
    phone: '',
    email: ''
  }
  
  // Reset cart
  cartItems.value = []
  
  // Reset payment data
  selectedPayment.value = ''
  selectedPaymentOption.value = ''
  
  // Reset order type
  orderType.value = 'takeaway'
  
  // Reset category and search
  selectedCategory.value = 'all'
  searchQuery.value = ''
  
  // Reset mobile sidebar
  showMobileSidebar.value = false
  
  // Reset form validation if form exists
  if (customerForm.value) {
    customerForm.value.reset()
    customerForm.value.resetValidation()
  }
}

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
    console.log('Customer data:', customerData.value)
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
      quantity: 1,
      notes: '',
      size: 'Regular',
      sugarLevel: 'Normal'
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

// const processPayment = () => {
//   if (selectedPayment.value) {
//     setTimeout(() => {
//       currentPage.value = 'success'
//     }, 1000)
//   }
// }

// // Payment methods
// const paymentMethods = ref([
//   { id: 'qris', name: 'QRIS', icon: 'tabler-qrcode', info: 'Pembayaran QRIS ke PT. Contoh QRIS' },
//   {
//     id: 'card',
//     name: 'Card',
//     icon: 'tabler-credit-card',
//     options: ['BCA Virtual Account', 'BRI Virtual Account', 'Mandiri Virtual Account']
//   },
//   {
//     id: 'ewallet',
//     name: 'E-Wallet',
//     icon: 'tabler-wallet',
//     options: ['OVO', 'DANA', 'ShopeePay', 'GoPay']
//   }
// ])

// const selectedPayment = ref('')
// const selectedPaymentOption = ref('')

const getPaymentMethodName = (methodId) => {
  const method = paymentMethods.value.find(m => m.id === methodId)
  return method ? method.name : ''
}

const startNewOrder = () => {
  // Reset all data completely
  resetAllData()
  
  // Go to barcode page
  currentPage.value = 'barcode'
  
  // Regenerate barcode
  generateBarcode()
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('id-ID').format(price)
}

const goBackToBarcode = () => {
  // Reset all data when going back to barcode
  resetAllData()
  
  // Go to barcode page
  currentPage.value = 'barcode'
  
  // Regenerate barcode
  generateBarcode()
}

const goBackToCustomerForm = () => {
  currentPage.value = 'customer-form'
}

const goToCart = () => {
  currentPage.value = 'cart'
}

// Cart computations
const cartItemsCount = computed(() => {
  return cartItems.value.reduce((total, item) => total + item.quantity, 0)
})

const subtotal = computed(() => {
  return cartItems.value.reduce((total, item) => total + (item.price * item.quantity), 0)
})

const jiwaPoints = computed(() => {
  return Math.floor(subtotal.value * 0.022) // 2.2% of subtotal as points
})

const totalXP = computed(() => {
  return Math.floor(cartItemsCount.value * 1.5) // 1.5 XP per item
})

const tax = computed(() => {
  return Math.floor(subtotal.value * 0.1) // 10% tax
})

const totalPayment = computed(() => {
  return subtotal.value + tax.value
})

// Mobile sidebar state
const showMobileSidebar = ref(false)

// Handle window resize
const handleResize = () => {
  windowWidth.value = window.innerWidth
  windowHeight.value = window.innerHeight
  
  // Auto close mobile sidebar when resizing to desktop
  if (isDesktop.value) {
    showMobileSidebar.value = false
  }
}

onMounted(() => {
  // Initialize with clean state
  resetAllData()
  generateBarcode()
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})

// Close mobile sidebar when clicking outside
const closeMobileSidebar = () => {
  showMobileSidebar.value = false
}

// Responsive grid columns computation
const gridCols = computed(() => {
  if (isMobile.value) return 2  // Minimum 2 columns on mobile
  if (isTablet.value) return 3  // 3 columns on tablet
  return 4  // 4 columns on desktop
})

// Sidebar width computation
const sidebarWidth = computed(() => {
  if (isMobile.value) return '280px'  // Wider for mobile popup
  if (isTablet.value) return '200px'
  return '220px'
})

// Product card height computation
const productCardHeight = computed(() => {
  if (isMobile.value) return '240px'
  if (isTablet.value) return '260px'
  return '280px'
})

// Computed styles
const sidebarStyle = computed(() => ({
  width: sidebarWidth.value,
  minWidth: sidebarWidth.value,
  backgroundColor: '#ffffff',
  borderRight: '1px solid #e0e0e0',
  height: 'calc(100vh - 64px)',
  overflowY: 'auto',
  overflowX: 'hidden',
  position: 'sticky',
  top: '64px',
  zIndex: 999
}))

const mobileSidebarStyle = computed(() => ({
  position: 'fixed',
  top: '64px',
  left: '0',
  width: sidebarWidth.value,
  height: 'calc(100vh - 64px)',
  backgroundColor: '#ffffff',
  boxShadow: '4px 0 20px rgba(0, 0, 0, 0.15)',
  overflowY: 'auto',
  overflowX: 'hidden',
  zIndex: 1001,
  transform: showMobileSidebar.value ? 'translateX(0)' : 'translateX(-100%)',
  transition: 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)'
}))

const overlayStyle = computed(() => ({
  position: 'fixed',
  top: '64px',
  left: '0',
  right: '0',
  bottom: '0',
  backgroundColor: 'rgba(0, 0, 0, 0.5)',
  zIndex: 1000,
  opacity: showMobileSidebar.value ? 1 : 0,
  visibility: showMobileSidebar.value ? 'visible' : 'hidden',
  transition: 'opacity 0.3s ease, visibility 0.3s ease'
}))

const productsContainerStyle = computed(() => {
  if (isDesktop.value) {
    return {
      marginLeft: sidebarWidth.value,
      width: `calc(100% - ${sidebarWidth.value})`,
      padding: '24px',
      boxSizing: 'border-box',
      transition: 'margin-left 0.3s ease'
    }
  }

  return {
    marginLeft: '0px',
    width: '100%',
    padding: '12px',
    boxSizing: 'border-box'
  }
})

// Mock data - categories and products
const categories = ref([
  { id: 'all', name: 'All Items' },
  { id: 'coffee', name: 'Coffee' },
  { id: 'tea', name: 'Tea' },
  { id: 'pastry', name: 'Pastries' },
  { id: 'snacks', name: 'Snacks' }
])

const selectedCategory = ref('all')
const searchQuery = ref('')

// Mock products data
const products = ref([
  { id: 1, name: 'Americano', price: 25000, category: 'coffee', isSpecial: false, isCombo: false, isPromo: false },
  { id: 2, name: 'Cappuccino', price: 35000, category: 'coffee', isSpecial: true, isCombo: false, isPromo: false },
  { id: 3, name: 'Espresso', price: 20000, category: 'coffee', isSpecial: false, isCombo: false, isPromo: true },
  { id: 4, name: 'Latte', price: 40000, category: 'coffee', isSpecial: false, isCombo: true, isPromo: false },
  { id: 5, name: 'Green Tea', price: 18000, category: 'tea', isSpecial: false, isCombo: false, isPromo: false },
  { id: 6, name: 'Earl Grey', price: 20000, category: 'tea', isSpecial: true, isCombo: false, isPromo: false },
  { id: 7, name: 'Croissant', price: 15000, category: 'pastry', isSpecial: false, isCombo: false, isPromo: false },
  { id: 8, name: 'Danish', price: 18000, category: 'pastry', isSpecial: false, isCombo: true, isPromo: false },
  { id: 9, name: 'Sandwich', price: 25000, category: 'snacks', isSpecial: false, isCombo: false, isPromo: true },
  { id: 10, name: 'Kopi Susu Sahabat', price: 19000, category: 'coffee', isSpecial: false, isCombo: false, isPromo: false }
])

// Utility methods
const getCategoryIcon = (categoryId: string) => {
  const iconMap = {
    'all': 'tabler-apps',
    'coffee': 'tabler-coffee',
    'tea': 'tabler-cup',
    'pastry': 'tabler-cake',
    'snacks': 'tabler-cookie'
  }
  return iconMap[categoryId] || 'tabler-circle'
}

const getProductIcon = (category: string) => {
  const iconMap = {
    'coffee': 'tabler-coffee',
    'tea': 'tabler-cup',
    'pastry': 'tabler-cake',
    'snacks': 'tabler-cookie'
  }
  return iconMap[category] || 'tabler-circle'
}

const getCategoryClass = (categoryId: string) => {
  return selectedCategory.value === categoryId 
    ? 'category-active bg-primary-lighten-5 text-primary border border-primary'
    : 'category-inactive bg-grey-lighten-4 text-grey-darken-2 hover:bg-grey-lighten-3 border border-transparent'
}

const getProductCount = (categoryId: string) => {
  if (categoryId === 'all') return products.value.length
  return products.value.filter(p => p.category === categoryId).length
}

const selectCategory = (categoryId: string) => {
  selectedCategory.value = categoryId
  if (!isDesktop.value) {
    showMobileSidebar.value = false
  }
}

const getCurrentCategoryName = computed(() => {
  const category = categories.value.find(cat => cat.id === selectedCategory.value)
  return category ? category.name : 'All Items'
})

const filteredProducts = computed(() => {
  let filtered = products.value
  
  if (selectedCategory.value !== 'all') {
    filtered = filtered.filter(product => product.category === selectedCategory.value)
  }
  
  if (searchQuery.value) {
    filtered = filtered.filter(product => 
      product.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  }
  
  return filtered
})

definePage({
  meta: {
    layout: 'blank',
    public: true,
  },
})

//Baru 13-09-25


// Reactive data
const loading = ref(true)
const processing = ref(false)
const error = ref('')
const paymentMethods = ref([])
const selectedPayment = ref('')
const selectedPaymentOption = ref(null)

const paymentForm = ref({
  customer_name: '',
  email: '',
  phone: '',
  amount: '',
  product_details: ''
})

// Computed
const selectedPaymentMethod = computed(() => {
  return paymentMethods.value.find(method => method.id === selectedPayment.value)
})

const isFormValidPayment = computed(() => {
  const form = paymentForm.value
  return form.customer_name && 
         form.email && 
         form.phone && 
         form.amount && 
         form.product_details &&
         selectedPaymentOption.value
})

// Methods
const loadPaymentMethods = async () => {
  try {
    loading.value = true
    error.value = ''
    
    const response = await axios.get('/api/payment/methods')
    
    if (response.data.success) {
      paymentMethods.value = response.data.data
    } else {
      error.value = response.data.message || 'Gagal memuat metode pembayaran'
    }
  } catch (err) {
    console.error('Error loading payment methods:', err)
    error.value = 'Terjadi kesalahan saat memuat metode pembayaran'
  } finally {
    loading.value = false
  }
}

const selectPaymentMethod = (methodId) => {
  selectedPayment.value = methodId
  selectedPaymentOption.value = null
  
  // Auto select if only one option
  const method = paymentMethods.value.find(m => m.id === methodId)
  if (method && method.options?.length === 1) {
    selectedPaymentOption.value = method.options[0]
  }
}

const processPayment = async () => {
  try {
    processing.value = true
    
    const paymentData = {
      ...paymentForm.value,
      payment_method: selectedPaymentOption.value.value
    }
    
    const response = await axios.post('/api/payment/create', paymentData)
    
    if (response.data.success) {
      // Redirect to payment page
      window.location.href = response.data.data.payment_url
    } else {
      alert('Gagal membuat pembayaran: ' + response.data.message)
    }
  } catch (err) {
    console.error('Error processing payment:', err)
    alert('Terjadi kesalahan saat memproses pembayaran')
  } finally {
    processing.value = false
  }
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(amount)
}

// Lifecycle
onMounted(() => {
  loadPaymentMethods()
})
</script>

<template>
  <VContainer fluid class="pa-0 fill-height">
    <!-- BARCODE SCAN PAGE -->
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
                <img
                  src="/storage/logo/papasans-logo.png"
                  alt="Logo SSB"
                  class="me-3 logo"
                />
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
  style="min-height: 100vh; width: 100%;"
>
  <VCol
    cols="12"
    class="d-flex align-center justify-center"
  >
    <VCard
      class="pa-8 mx-4"
      max-width="800"
      width="100%"
      elevation="12"
      style="box-sizing: border-box;"
    >
      <VCardText>
        <div class="text-center mb-8">
          <VIcon icon="tabler-user" size="64" color="primary" />
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

          <div class="d-flex flex-column flex-sm-row justify-space-between mt-8" style="gap: 16px;">
            <!-- <VBtn
              color="default"
              variant="outlined"
              size="large"
              @click="goBackToBarcode()"
              class="flex-grow-1"
            >
              <VIcon icon="tabler-arrow-left" start />
              Kembali
            </VBtn> -->

            <VBtn
              color="primary"
              type="submit"
              size="large"
              :disabled="!isFormValid"
              class="flex-grow-1"
            >
              Selanjutnya
              <VIcon icon="tabler-arrow-right" end />
            </VBtn>
          </div>
        </VForm>
      </VCardText>
    </VCard>
  </VCol>
</VRow>


    <!-- CATALOG PAGE -->
    <div
      v-if="currentPage === 'catalog'"
      class="catalog-page"
    >
      <!-- Header -->
      <VAppBar
        color="white"
        elevation="1"
        class="px-4"
        style="z-index: 1002;"
        fixed
      >
        <!-- Menu Button for Mobile/Tablet or Back Button for Desktop -->
        <VBtn
          v-if="!isDesktop"
          icon="tabler-menu-2"
          variant="text"
          @click="showMobileSidebar = !showMobileSidebar"
        />
        <VBtn
          v-else
          icon="tabler-arrow-left"
          variant="text"
          @click="goBackToCustomerForm"
        />
        
        <VSpacer />
        
        <!-- Search Bar - Hidden on small mobile -->
        <VTextField
          v-if="!isMobile || windowWidth > 500"
          v-model="searchQuery"
          placeholder="Search menu"
          prepend-inner-icon="tabler-search"
          variant="outlined"
          hide-details
          density="compact"
          rounded="pill"
          class="mx-4"
          :style="{ maxWidth: isMobile ? '200px' : '300px' }"
        />
        
        <VSpacer />
        
        <VBtn
          color="primary"
          variant="elevated"
          rounded="pill"
          class="position-relative"
          @click="goToCart"
        >
          <VIcon icon="tabler-shopping-cart" />
          <VBadge
            v-if="cartItemsCount > 0"
            :content="cartItemsCount"
            color="warning"
            floating
          />
        </VBtn>
      </VAppBar>

      <!-- Main Content -->
      <div class="catalog-container d-flex position-relative" style="padding-top: 64px;">
        <!-- Desktop Sidebar -->
        <div 
          v-if="isDesktop"
          class="sidebar" 
          :style="sidebarStyle"
        >
          <div class="pa-4">
            <div class="mb-4">
              <h3 class="text-h6 font-weight-bold text-grey-darken-3">Categories</h3>
              <p class="text-body-2 text-grey-darken-1">Choose your favorite items</p>
            </div>

            <div
              v-for="category in categories"
              :key="category.id"
              class="category-item pa-3 mb-2 rounded-lg cursor-pointer transition-all"
              :class="getCategoryClass(category.id)"
              @click="selectCategory(category.id)"
            >
              <div class="text-left d-flex align-center">
                <VIcon
                  :icon="getCategoryIcon(category.id)"
                  size="20"
                  class="mr-3"
                />
                <div>
                  <span class="category-name text-sm font-weight-medium d-block">
                    {{ category.name }}
                  </span>
                  <span class="category-count text-xs opacity-75">
                    {{ getProductCount(category.id) }} items
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Mobile/Tablet Overlay -->
        <div
          v-if="!isDesktop"
          :style="overlayStyle"
          @click="closeMobileSidebar"
        />

        <!-- Mobile/Tablet Sliding Sidebar -->
        <div
          v-if="!isDesktop"
          class="mobile-sidebar"
          :style="mobileSidebarStyle"
        >
          <!-- Close Button -->
          <div class="d-flex justify-end pa-2 border-bottom">
            <VBtn
              icon="tabler-x"
              variant="text"
              size="small"
              @click="closeMobileSidebar"
            />
          </div>

          <div class="pa-4">
            <div class="mb-4">
              <h3 class="text-h6 font-weight-bold text-grey-darken-3">Categories</h3>
              <p class="text-body-2 text-grey-darken-1">Choose your favorite items</p>
            </div>

            <div
              v-for="category in categories"
              :key="category.id"
              class="category-item pa-3 mb-2 rounded-lg cursor-pointer transition-all"
              :class="getCategoryClass(category.id)"
              @click="selectCategory(category.id)"
            >
              <div class="text-left d-flex align-center">
                <VIcon
                  :icon="getCategoryIcon(category.id)"
                  size="20"
                  class="mr-3"
                />
                <div>
                  <span class="category-name text-sm font-weight-medium d-block">
                    {{ category.name }}
                  </span>
                  <span class="category-count text-xs opacity-75">
                    {{ getProductCount(category.id) }} items
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Products Container -->
        <div 
          class="products-container"
          :style="{
            width: isDesktop ? 'calc(100vw - ' + sidebarWidth + ')' : '100vw',
            padding: isMobile ? '12px' : '24px',
            boxSizing: 'border-box',
            marginLeft: '0px', // Hapus margin left
            transition: 'width 0.3s ease'
          }"
        >
          <div class="products-header mb-4 d-flex align-center justify-space-between">
            <div>
              <h3 
                class="font-weight-bold"
                :class="isMobile ? 'text-h6' : 'text-h5'"
              >
                {{ getCurrentCategoryName }}
              </h3>
              <p class="text-body-2 text-medium-emphasis mt-1">
                Showing {{ filteredProducts.length }} products
              </p>
            </div>
            
            <!-- Mobile Search -->
            <VTextField
              v-if="isMobile && windowWidth <= 500"
              v-model="searchQuery"
              placeholder="Search..."
              prepend-inner-icon="tabler-search"
              variant="outlined"
              density="compact"
              style="max-width: 150px;"
              hide-details
            />
          </div>

          <!-- Products Grid - Responsive -->
          <div class="products-grid">
            <div 
              class="product-grid-container"
              :style="{
                display: 'grid',
                gridTemplateColumns: `repeat(${gridCols}, 1fr)`,
                gap: isMobile ? '12px' : '16px'
              }"
            >
              <div
                v-for="product in filteredProducts"
                :key="product.id"
                class="product-card-wrapper"
              >
                <VCard
                  class="product-card h-100"
                  elevation="2"
                  rounded="md"
                  :style="{ 
                    height: productCardHeight, 
                    transition: 'transform 0.2s ease;'
                  }"
                  @mouseover="$event.currentTarget.style.transform = 'translateY(-2px)'"
                  @mouseleave="$event.currentTarget.style.transform = 'translateY(0)'"
                >
                  <!-- Product Image -->
                  <div class="product-image-container text-center pa-4 position-relative">
                    <div 
                      class="product-image bg-grey-lighten-3 rounded-circle mx-auto d-flex align-center justify-center" 
                      :style="{ 
                        width: isMobile ? '60px' : '80px', 
                        height: isMobile ? '60px' : '80px' 
                      }"
                    >
                      <VIcon 
                        :icon="getProductIcon(product.category)" 
                        :size="isMobile ? '24' : '32'"
                        color="grey-darken-1"
                      />
                    </div>
                    
                    <!-- Special badges -->
                    <VChip
                      v-if="product.isSpecial"
                      color="warning"
                      size="x-small"
                      class="position-absolute badge-chip"
                      style="top: 4px; right: 4px;"
                    >
                      <VIcon icon="tabler-star" size="10" start />
                      <span v-if="!isMobile">Special</span>
                    </VChip>
                    <VChip
                      v-if="product.isCombo"
                      color="success"
                      size="x-small"
                      class="position-absolute badge-chip"
                      style="top: 4px; right: 4px;"
                    >
                      <span v-if="!isMobile">Combo</span>
                      <VIcon v-else icon="tabler-package" size="10" />
                    </VChip>
                    <VChip
                      v-if="product.isPromo"
                      color="primary"
                      size="x-small"
                      class="position-absolute badge-chip"
                      style="top: 4px; right: 4px;"
                    >
                      <span v-if="!isMobile">Promo</span>
                      <VIcon v-else icon="tabler-percentage" size="10" />
                    </VChip>
                  </div>

                  <VCardText class="pa-3 d-flex flex-column justify-space-between">
                    <div>
                      <h4 
                        class="font-weight-bold text-left"
                        :class="isMobile ? 'text-body-2' : 'text-subtitle-1'"
                        style="line-height: 1.2;"
                      >
                        {{ product.name }}
                      </h4>
                    </div>
                    
                    <div class="d-flex align-center justify-space-between">
                      <span 
                        class="text-primary font-weight-bold"
                        :class="isMobile ? 'text-body-2' : 'text-h6'"
                      >
                        {{ isMobile ? formatPrice(product.price) : `Rp${formatPrice(product.price)}` }}
                      </span>
                      <VBtn
                        icon="tabler-plus"
                        color="primary"
                        :size="isMobile ? 'x-small' : 'small'"
                        rounded="circle"
                        @click="addToCart(product)"
                        elevation="2"
                      />
                    </div>
                  </VCardText>
                </VCard>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="filteredProducts.length === 0" class="text-center py-8">
            <VIcon icon="tabler-search-off" size="48" color="grey-lighten-1" class="mb-4" />
            <h4 class="text-h6 text-medium-emphasis mb-2">No products found</h4>
            <p class="text-body-2 text-medium-emphasis">
              Try adjusting your search or filter criteria
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- CART PAGE (placeholder) -->
    <!-- CART & INVOICE PAGE -->
    <div
      v-if="currentPage === 'cart'"
      class="products-container"
      :style="{
        width: isDesktop ? 'calc(100vw - ' + sidebarWidth + ')' : '100vw',
        maxWidth: '100%',
        padding: isMobile ? '12px' : '24px',
        boxSizing: 'border-box',
        margin: '0 auto', // Tengahkan secara horizontal
        transition: 'width 0.3s ease'
      }"
    >
      <!-- Header -->
      <VAppBar
        color="white"
        elevation="1"
        class="px-4"
        style="z-index: 1002;"
        fixed
      >
        <VBtn
          icon="tabler-arrow-left"
          variant="text"
          @click="currentPage = 'catalog'"
        />
        
        <VAppBarTitle class="font-weight-bold">
          Detail Pesanan
        </VAppBarTitle>
        
        <VSpacer />
      </VAppBar>

      <!-- Main Content -->
      <div style="padding-top: 64px;">
        <VContainer class="pa-4">
          <!-- Cart Items Section -->
          <VCard class="mb-4" elevation="2" rounded="md">
            <VCardText class="pa-4">
              <div class="d-flex justify-space-between align-center mb-4">
                <h3 class="text-h6 font-weight-bold">Daftar Pesanan</h3>
                <VBtn
                  color="primary"
                  variant="outlined"
                  size="small"
                  rounded="pill"
                  @click="currentPage = 'catalog'"
                  class="text-none"
                >
                  <VIcon 
                    icon="tabler-plus" 
                    start 
                    size="16"
                  />
                  Tambah Menu
                </VBtn>
              </div>

              <!-- Cart Items List -->
              <div v-if="cartItems.length > 0">
                <div
                  v-for="(item, index) in cartItems"
                  :key="item.id"
                  class="cart-item mb-4"
                >
                  <VRow align="center">
                    <VCol cols="3" class="pa-2">
                      <div 
                        class="product-image bg-grey-lighten-3 rounded-lg d-flex align-center justify-center mx-auto"
                        style="width: 60px; height: 60px;"
                      >
                        <VIcon 
                          :icon="getProductIcon(item.category)" 
                          size="24"
                          color="grey-darken-1"
                        />
                      </div>
                    </VCol>
                    
                    <VCol cols="5" class="pa-2">
                      <div>
                        <h4 class="text-body-1 font-weight-bold mb-1">
                          {{ item.name }}
                        </h4>
                        <p class="text-body-2 text-grey-darken-1 mb-0">
                          {{ item.size || 'Regular' }} Size, {{ item.sugarLevel || 'Normal' }} Sugar
                        </p>
                      </div>
                    </VCol>
                    
                    <VCol cols="4" class="pa-2">
                      <div class="text-right">
                        <p class="text-body-1 font-weight-bold mb-2">
                          Rp{{ formatPrice(item.price) }}
                        </p>
                        
                        <!-- Quantity Controls -->
                        <div class="d-flex align-center justify-end">
                          <VBtn
                            icon="tabler-trash"
                            variant="text"
                            size="x-small"
                            color="primary"
                            class="mr-2"
                            @click="removeFromCart(item.id)"
                          />
                          
                          <VBtn
                            icon="tabler-minus"
                            variant="outlined"
                            size="x-small"
                            color="grey-darken-2"
                            @click="decreaseQuantity(item.id)"
                            :disabled="item.quantity <= 1"
                            style="min-width: 24px; width: 24px; height: 24px;"
                          />
                          
                          <span class="mx-2 text-body-1 font-weight-bold" style="min-width: 20px; text-align: center;">
                            {{ item.quantity }}
                          </span>
                          
                          <VBtn
                            icon="tabler-plus"
                            variant="flat"
                            size="x-small"
                            color="primary"
                            @click="increaseQuantity(item.id)"
                            style="min-width: 24px; width: 24px; height: 24px;"
                          />
                        </div>
                      </div>
                    </VCol>
                  </VRow>
                  
                  <!-- Divider -->
                  <VDivider v-if="index < cartItems.length - 1" class="mt-3" />
                </div>
              </div>

              <!-- Empty Cart State -->
              <div v-else class="text-center py-8">
                <VIcon 
                  icon="tabler-shopping-cart-off" 
                  size="48" 
                  color="grey-lighten-1" 
                  class="mb-4" 
                />
                <h4 class="text-h6 text-medium-emphasis mb-2">Keranjang Kosong</h4>
                <p class="text-body-2 text-medium-emphasis mb-4">
                  Tambahkan item ke keranjang untuk melanjutkan
                </p>
                <VBtn
                  color="primary"
                  variant="flat"
                  rounded="pill"
                  @click="currentPage = 'catalog'"
                >
                  <VIcon icon="tabler-plus" start />
                  Tambah Item
                </VBtn>
              </div>
            </VCardText>
          </VCard>

          <!-- Payment Summary -->
          <VCard 
            v-if="cartItems.length > 0"
            class="mb-4" 
            elevation="2" 
            rounded="md"
          >
            <VCardText class="pa-4">
              <div class="d-flex align-center mb-3">
                <h3 class="text-h6 font-weight-bold">Ringkasan Pembayaran</h3>
                <VIcon 
                  icon="tabler-info-circle" 
                  size="16" 
                  color="grey-darken-2"
                  class="ml-2"
                />
              </div>

              <!-- Subtotal -->
              <div class="d-flex justify-space-between align-center mb-2">
                <span class="text-body-1">Subtotal</span>
                <span class="text-body-1 font-weight-medium">
                  Rp{{ formatPrice(subtotal) }}
                </span>
              </div>

              <VDivider class="my-3" />

              <!-- Total -->
              <div class="d-flex justify-space-between align-center mb-4">
                <span class="text-h6 font-weight-bold">Total Pembayaran</span>
                <span class="text-h6 font-weight-bold">
                  Rp{{ formatPrice(totalPayment) }}
                </span>
              </div>

              <!-- Rewards Info -->
              <!-- <VRow>
                <VCol cols="6">
                  <div class="d-flex align-center">
                    <VIcon 
                      icon="tabler-info-circle" 
                      size="16" 
                      color="grey-darken-2"
                      class="mr-1"
                    />
                    <span class="text-body-2 text-grey-darken-2">Jiwa Point</span>
                  </div>
                  <div class="d-flex align-center mt-1">
                    <VIcon 
                      icon="tabler-coin" 
                      size="16" 
                      color="warning"
                      class="mr-1"
                    />
                    <span class="text-body-2 font-weight-bold text-warning">
                      Rp{{ jiwaPoints }}
                    </span>
                  </div>
                </VCol>
                
                <VCol cols="6" class="text-right">
                  <div>
                    <span class="text-body-2 text-grey-darken-2">Total XP</span>
                  </div>
                  <div class="mt-1">
                    <span class="text-body-2 font-weight-bold">
                      {{ totalXP }}
                    </span>
                  </div>
                </VCol>
              </VRow> -->
            </VCardText>
          </VCard>
        </VContainer>

        <!-- Fixed Bottom Payment Button -->
        <div 
          v-if="cartItems.length > 0"
          class="payment-button-container position-fixed w-100"
          style="bottom: 0; left: 0; right: 0; background: white; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); z-index: 1001;"
        >
          <VContainer class="pa-4">
            <VBtn
              color="primary"
              block
              size="large"
              rounded="md"
              elevation="0"
              class="text-none font-weight-bold"
              @click="currentPage = 'payment'"
            >
              Pilih Pembayaran
            </VBtn>
          </VContainer>
        </div>

        <!-- Add bottom padding when cart has items to prevent content hiding behind fixed button -->
        <div v-if="cartItems.length > 0" style="height: 100px;"></div>
      </div>
    </div>

    <div
      v-if="currentPage === 'payment'"
      class="products-container"
      :style="{
        width: isDesktop ? 'calc(100vw - ' + sidebarWidth + ')' : '100vw',
        maxWidth: '100%',
        padding: isMobile ? '12px' : '24px',
        boxSizing: 'border-box',
        margin: '0 auto', // Tengahkan secara horizontal
        transition: 'width 0.3s ease'
      }"
    >
      <!-- Header -->
      <VAppBar
        color="white"
        elevation="1"
        class="px-4"
        style="z-index: 1002;"
        fixed
      >
        <VBtn
          icon="tabler-arrow-left"
          variant="text"
          @click="currentPage = 'cart'"
        />
        
        <VAppBarTitle class="font-weight-bold">
          Pilih Pembayaran
        </VAppBarTitle>
        
        <VSpacer />
      </VAppBar>

      <!-- Main Content -->
      <div style="padding-top: 64px;">
        <VContainer class="pa-4">
          <!-- Order Summary -->
          <VCard class="mb-4" elevation="2" rounded="md">
            <VCardText class="pa-4">
              <h3 class="text-h6 font-weight-bold mb-3">Ringkasan Pesanan</h3>
              
              <div class="d-flex justify-space-between align-center mb-2">
                <span class="text-body-2">{{ cartItemsCount }} Item</span>
                <span class="text-body-2">Rp{{ formatPrice(subtotal) }}</span>
              </div>
              
              <div class="d-flex justify-space-between align-center mb-2">
                <span class="text-body-2">Pajak (10%)</span>
                <span class="text-body-2">Rp{{ formatPrice(tax) }}</span>
              </div>
              
              <VDivider class="my-2" />
              
              <div class="d-flex justify-space-between align-center">
                <span class="text-h6 font-weight-bold">Total</span>
                <span class="text-h6 font-weight-bold text-primary">
                  Rp{{ formatPrice(totalPayment) }}
                </span>
              </div>
            </VCardText>
          </VCard>

  <VCard class="mb-4" elevation="2" rounded="md">
    <VCardText class="pa-4">
      <h3 class="text-h6 font-weight-bold mb-4">Metode Pembayaran</h3>
      
      <!-- Loading state -->
      <div v-if="loading" class="text-center py-4">
        <VProgressCircular indeterminate color="primary" />
        <p class="mt-2">Memuat metode pembayaran...</p>
      </div>
      
      <!-- Payment methods grid -->
      <VRow v-else-if="paymentMethods.length">
        <VCol 
          v-for="method in paymentMethods" 
          :key="method.id"
          cols="6"
          sm="4"
          class="mb-3"
        >
          <VCard
            :color="selectedPayment === method.id ? 'primary' : 'default'"
            :variant="selectedPayment === method.id ? 'flat' : 'outlined'"
            rounded="lg"
            class="payment-method-card text-center pa-4 cursor-pointer"
            elevation="0"
            @click="selectPaymentMethod(method.id)"
          >
            <VIcon 
              :icon="method.icon" 
              size="32"
              :color="selectedPayment === method.id ? 'white' : 'grey-darken-2'"
              class="mb-2"
            />
            <p 
              class="text-body-2 font-weight-medium mb-0"
              :class="selectedPayment === method.id ? 'text-white' : 'text-grey-darken-2'"
            >
              {{ method.name }}
            </p>
          </VCard>
        </VCol>
      </VRow>

      <!-- Error state -->
      <VAlert v-else-if="error" type="error" class="mb-4">
        {{ error }}
      </VAlert>

      <!-- Payment options dropdown -->
      <VRow v-if="selectedPaymentMethod && selectedPaymentMethod.options?.length > 1">
        <VCol cols="12">
          <VSelect
            v-model="selectedPaymentOption"
            :items="selectedPaymentMethod.options"
            :label="`Pilih ${selectedPaymentMethod.name}`"
            variant="outlined"
            item-title="title"
            item-value="value"
            return-object
          >
            <template #item="{ props, item }">
              <VListItem v-bind="props">
                <template #append>
                  <VChip size="small" color="info" v-if="item.raw.fee > 0">
                    +{{ formatCurrency(item.raw.fee) }}
                  </VChip>
                  <VChip size="small" color="success" v-else>
                    Gratis
                  </VChip>
                </template>
              </VListItem>
            </template>
          </VSelect>
        </VCol>
      </VRow>

      <!-- Selected payment info -->
      <VAlert
        v-if="selectedPaymentOption"
        type="success"
        border="start"
        color="green"
        elevation="1"
        class="mt-4"
      >
        <div class="d-flex justify-space-between align-center">
          <div>
            <strong>{{ selectedPaymentOption.title }}</strong>
            <br>
            <small class="text-grey-darken-1">{{ selectedPaymentMethod.name }}</small>
          </div>
          <div v-if="selectedPaymentOption.fee > 0" class="text-right">
            <div class="text-caption text-grey-darken-1">Biaya Admin</div>
            <div class="font-weight-bold">{{ formatCurrency(selectedPaymentOption.fee) }}</div>
          </div>
        </div>
      </VAlert>

      <!-- Payment form -->
      <VCard v-if="selectedPaymentOption" class="mt-4" variant="outlined">
        <VCardTitle class="text-h6">Informasi Pembayaran</VCardTitle>
        <VCardText>
          <VRow>
            <VCol cols="12" md="6">
              <VTextField
                v-model="paymentForm.customer_name"
                label="Nama Lengkap"
                variant="outlined"
                required
              />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField
                v-model="paymentForm.email"
                label="Email"
                type="email"
                variant="outlined"
                required
              />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField
                v-model="paymentForm.phone"
                label="Nomor Telepon"
                variant="outlined"
                required
              />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField
                v-model="paymentForm.amount"
                label="Jumlah Pembayaran"
                type="number"
                variant="outlined"
                prefix="Rp"
                required
              />
            </VCol>
            <VCol cols="12">
              <VTextarea
                v-model="paymentForm.product_details"
                label="Detail Produk/Layanan"
                variant="outlined"
                rows="3"
                required
              />
            </VCol>
          </VRow>
          
          <div class="d-flex justify-end mt-4">
            <VBtn
              color="primary"
              size="large"
              :loading="processing"
              @click="processPayment"
              :disabled="!isFormValidPayment"
            >
              Bayar Sekarang
            </VBtn>
          </div>
        </VCardText>
      </VCard>

    </VCardText>
  </VCard>


          <!-- Customer Info Summary -->
          <VCard class="mb-4" elevation="2" rounded="md">
            <VCardText class="pa-4">
              <h3 class="text-h6 font-weight-bold mb-3">Informasi Pembeli</h3>
              
              <div class="mb-2">
                <VIcon icon="tabler-user" size="16" class="mr-2" color="grey-darken-2" />
                <span class="text-body-2">{{ customerData.name }}</span>
              </div>
              
              <div class="mb-2">
                <VIcon icon="tabler-phone" size="16" class="mr-2" color="grey-darken-2" />
                <span class="text-body-2">{{ customerData.phone }}</span>
              </div>
              
              <div v-if="customerData.email" class="mb-2">
                <VIcon icon="tabler-mail" size="16" class="mr-2" color="grey-darken-2" />
                <span class="text-body-2">{{ customerData.email }}</span>
              </div>
              
              <div>
                <VIcon 
                  :icon="orderType === 'takeaway' ? 'tabler-walk' : 'tabler-truck-delivery'" 
                  size="16" 
                  class="mr-2" 
                  color="grey-darken-2" 
                />
                <span class="text-body-2 text-capitalize">{{ orderType }}</span>
              </div>
            </VCardText>
          </VCard>
        </VContainer>

        <!-- Fixed Bottom Process Button -->
        <div 
          class="payment-button-container position-fixed w-100"
          style="bottom: 0; left: 0; right: 0; background: white; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); z-index: 1001;"
        >
          <VContainer class="pa-4">
            <VBtn
              color="primary"
              block
              size="large"
              rounded="md"
              elevation="0"
              class="text-none font-weight-bold"
              :disabled="!selectedPayment"
              @click="processPayment"
            >
              <span v-if="!selectedPayment">Pilih Metode Pembayaran</span>
              <span v-else>Bayar dengan {{ getPaymentMethodName(selectedPayment) }}</span>
            </VBtn>
          </VContainer>
        </div>

        <!-- Bottom padding -->
        <div style="height: 100px;"></div>
      </div>
    </div>

    <!-- SUCCESS PAGE -->
    <div
      v-if="currentPage === 'success'"
      class="products-container"
      :style="{
        width: isDesktop ? 'calc(100vw - ' + sidebarWidth + ')' : '100vw',
        maxWidth: '100%',
        padding: isMobile ? '12px' : '24px',
        boxSizing: 'border-box',
        margin: '0 auto', // Tengahkan secara horizontal
        transition: 'width 0.3s ease'
      }"
    >
      <VRow class="ma-0 fill-height">
        <VCol cols="12" class="d-flex align-center justify-center pa-4">
          <VCard
            class="pa-8 text-center"
            max-width="400"
            width="100%"
            elevation="12"
            rounded="md"
          >
            <VCardText>
              <div class="success-icon mb-4">
                <img
                  src="/storage/logo/papasans-logo.png"
                  alt="Logo SSB"
                  class="logo"
                />
              </div>
              
              <h1 class="text-h4 font-weight-bold mb-3">Pembayaran Berhasil!</h1>
              
              <p class="text-body-1 text-medium-emphasis mb-4">
                Terima kasih telah berbelanja. Pesanan Anda sedang diproses.
              </p>
              
              <VCard variant="outlined" class="pa-4 mb-4" rounded="lg">
                <div class="d-flex justify-space-between align-center mb-2">
                  <span class="text-body-2 text-start">Total Pembayaran:</span>
                  <span class="text-h6 font-weight-bold text-success text-end">
                    Rp{{ formatPrice(totalPayment) }}
                  </span>
                </div>

                <div class="d-flex justify-space-between align-center mb-2">
                  <span class="text-body-2 text-start">Metode Pembayaran:</span>
                  <span class="text-body-2 font-weight-medium text-end">
                    {{ getPaymentMethodName(selectedPayment) }}
                    <template v-if="selectedPaymentOption"> - {{ selectedPaymentOption }}</template>
                  </span>
                </div>

                <!-- <div class="d-flex justify-space-between align-center">
                  <span class="text-body-2 text-start">Jiwa Points Earned:</span>
                  <span class="text-body-2 font-weight-bold text-warning text-end">
                    +{{ jiwaPoints }}
                  </span>
                </div> -->
              </VCard>

              <VBtn
                color="primary"
                size="large"
                rounded="pill"
                class="text-none font-weight-bold mb-3"
                block
                @click="startNewOrder"
              >
                <VIcon icon="tabler-printer" start />
                Cetak Struk
              </VBtn>
              
              <VBtn
                color="success"
                size="large"
                rounded="pill"
                class="text-none font-weight-bold mb-3"
                block
                @click="startNewOrder"
              >
                <VIcon icon="tabler-plus" start />
                Pesan Lagi
              </VBtn>
              
              <VBtn
                variant="outlined"
                size="large"
                rounded="pill"
                class="text-none"
                block
                @click="goBackToBarcode()"
              >
                <VIcon icon="tabler-home" start />
                Kembali ke Beranda
              </VBtn>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </div>
  </VContainer>
</template>


<script setup>


</script>


<style scoped>

.payment-method-card {
  transition: all 0.3s ease;
  min-height: 100px;
}

.payment-method-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.12);
}

.logo {
  max-width: 150px; /* atau sesuaikan nilainya */
  height: auto;
}

.catalog-container {
  min-height: calc(100vh - 64px);
}

.sidebar {
  flex-shrink: 0;
}

.mobile-sidebar {
  flex-shrink: 0;
}

.products-container {
  transition: all 0.3s ease;
}

.category-item {
  position: relative;
  cursor: pointer;
  border: 1px solid transparent;
}

.category-item:hover {
  transform: translateX(2px);
}

.category-active {
  border-color: rgb(var(--v-theme-error)) !important;
}

.product-card {
  cursor: pointer;
  transition: all 0.2s ease;
}

.product-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.badge-chip {
  font-size: 10px !important;
  height: 20px !important;
  min-width: 20px !important;
}

/* Smooth scrollbar */
.sidebar::-webkit-scrollbar,
.mobile-sidebar::-webkit-scrollbar {
  width: 4px;
}

.sidebar::-webkit-scrollbar-track,
.mobile-sidebar::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.sidebar::-webkit-scrollbar-thumb,
.mobile-sidebar::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 2px;
}

.sidebar::-webkit-scrollbar-thumb:hover,
.mobile-sidebar::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Responsive adjustments */
@media (max-width: 767px) {
  .products-grid {
    padding: 0 8px;
  }
}

.border-primary {
  border-color: var(--v-primary-base) !important;
}
</style>

