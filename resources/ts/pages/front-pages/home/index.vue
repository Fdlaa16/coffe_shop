<script setup lang="ts">

import axios from 'axios'
import QRCode from 'qrcode'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const currentStep = ref(0)
const isPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)

const isFlatSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref<'success' | 'error'>('success')
const isSnackbarVisible = ref(false)
const loading = ref(false)
const menuLoading = ref(false)

const localData = ref({
  email: '',
  name: '',
  phone: '',
  order_type: 'takeaway',
  cartItems: [],  
  method: '',
  method_name: '',
  option: '',
  total_items: 0,
  subtotal: 0,
  tax: 0,
  total_payment: 0
})

const currentPage = ref('barcode')

const windowWidth = ref(window.innerWidth)
const windowHeight = ref(window.innerHeight)
const isMobile = computed(() => windowWidth.value < 768)
const isTablet = computed(() => windowWidth.value >= 768 && windowWidth.value < 1024)
const isDesktop = computed(() => windowWidth.value >= 1024)
const customerForm = ref(null)

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
  
  localData.value.phone = value
}

const isFormValid = computed(() => {
  return localData.value.name.length >= 2 && 
         localData.value.phone.length >= 10 &&
         (localData.value.email === '' || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(localData.value.email))
})

const barcodeCanvas = ref(null)
const updateCalculatedValues = () => {
  const items = localData.value.cartItems
  
  localData.value.total_items = items.reduce((total, item) => total + item.qty, 0)
  localData.value.subtotal = items.reduce((total, item) => total + (item.price * item.qty), 0)
  localData.value.tax = Math.floor(localData.value.subtotal * 0.1)
  localData.value.total_payment = localData.value.subtotal + localData.value.tax
}

const resetAllData = () => {
  localData.value = {
    email: '',
    name: '',
    phone: '',
    order_type: 'takeaway',
    cartItems: [],
    method: '',
    method_name: '',
    option: '',
    total_items: 0,
    subtotal: 0,
    tax: 0,
    total_payment: 0
  }
  
  selectedCategory.value = 'all'
  searchQuery.value = ''
  showMobileSidebar.value = false
  
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
    console.log('Customer data:', localData.value)
    currentPage.value = 'catalog'

    await fetchMenuData()
  }
}

const addToCart = (product) => {
  const existingItem = localData.value.cartItems.find(item => item.id === product.id)
  if (existingItem) {
    existingItem.qty += 1
  } else {
    localData.value.cartItems.push({
      id: product.id,
      menu_id: product.id,
      menu_name: product.name,
      name: product.name,
      qty: 1,
      price: product.price,
      subtotal: product.price,
      notes: '',
      size: 'Regular',
      sugar_level: 'Normal',
      sugarLevel: 'Normal', 
      category: product.type 
    })
  }
  updateCalculatedValues()
}

const removeFromCart = (productId) => {
  localData.value.cartItems = localData.value.cartItems.filter(item => item.id !== productId)
  updateCalculatedValues()
}

const increaseQty = (productId) => {
  const item = localData.value.cartItems.find(item => item.id === productId)
  if (item) {
    item.qty += 1
    item.subtotal = item.price * item.qty
    updateCalculatedValues()
  }
}

const decreaseQty = (productId) => {
  const item = localData.value.cartItems.find(item => item.id === productId)
  if (item && item.qty > 1) {
    item.qty -= 1
    item.subtotal = item.price * item.qty
    updateCalculatedValues()
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
const processPayment = async () => {
  try {
    loading.value = true
    
    updateCalculatedValues()

    const formData = new FormData()
    
    formData.append('name', localData.value.name)
    formData.append('phone', localData.value.phone)
    formData.append('email', localData.value.email)
    
    formData.append('order_type', localData.value.order_type)
  
    localData.value.cartItems.forEach((item, index) => {
      formData.append(`cartItems[${index}][menu_id]`, item.menu_id.toString())
      formData.append(`cartItems[${index}][menu_name]`, item.menu_name)
      formData.append(`cartItems[${index}][qty]`, item.qty.toString())
      formData.append(`cartItems[${index}][price]`, item.price.toString())
      formData.append(`cartItems[${index}][subtotal]`, item.subtotal.toString())
      formData.append(`cartItems[${index}][notes]`, item.notes)
      formData.append(`cartItems[${index}][size]`, item.size)
      formData.append(`cartItems[${index}][sugar_level]`, item.sugar_level)
      formData.append(`cartItems[${index}][category]`, item.category)
    })

    formData.append('method', localData.value.method)
    formData.append('method_name', localData.value.method_name)
    formData.append('option', localData.value.option)
    
    formData.append('total_items', localData.value.total_items.toString())
    formData.append('subtotal', localData.value.subtotal.toString())
    formData.append('tax', localData.value.tax.toString())
    formData.append('total_payment', localData.value.total_payment.toString())
    
    await $api('order/create', {
      method: 'POST',
      body: formData,
    })

    await new Promise(resolve => setTimeout(resolve, 500))
    
    currentPage.value = 'success'
    
  } catch (err: any) {
    loading.value = false 
    
    const errors = err?.data?.errors
    if (err?.status === 422 && errors) {
      const messages = Object.values(errors).flat()
      snackbarMessage.value = 'Validasi gagal: ' + messages.join(', ')
    } else {
      snackbarMessage.value =
        'Gagal mengirim data: ' + (err?.data?.message || err?.message || 'Unknown error')
    }

    snackbarColor.value = 'error'
    isFlatSnackbarVisible.value = true
  } finally {
    loading.value = false 
  }
}

const paymentMethods = ref([
  { id: 'qris', name: 'QRIS', icon: 'tabler-qrcode', info: 'Pembayaran QRIS ke PT. Contoh QRIS' },
  {
    id: 'card',
    name: 'Card',
    icon: 'tabler-credit-card',
    options: ['BCA Virtual Account', 'BRI Virtual Account', 'Mandiri Virtual Account']
  },
  {
    id: 'ewallet',
    name: 'E-Wallet',
    icon: 'tabler-wallet',
    options: ['OVO', 'DANA', 'ShopeePay', 'GoPay']
  }
])

const selectedPayment = computed({
  get: () => localData.value.method,
  set: (value) => {
    localData.value.method = value
    localData.value.method_name = getPaymentMethodName(value)
    if (!value) {
      localData.value.option = ''
    }
  }
})

const selectedPaymentOption = computed({
  get: () => localData.value.option,
  set: (value) => {
    localData.value.option = value
  }
})

const getPaymentMethodName = (methodId) => {
  const method = paymentMethods.value.find(m => m.id === methodId)
  return method ? method.name : ''
}

const startNewOrder = () => {
  resetAllData()
  currentPage.value = 'barcode'
  generateBarcode()
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('id-ID').format(price)
}

const goBackToBarcode = () => {
  resetAllData()
  currentPage.value = 'barcode'
  generateBarcode()
}

const goBackToCustomerForm = () => {
  currentPage.value = 'customer-form'
}

const goToCart = () => {
  currentPage.value = 'cart'
}

const cartItemsCount = computed(() => {
  return localData.value.total_items
})

const subtotal = computed(() => {
  return localData.value.subtotal
})

const tax = computed(() => {
  return localData.value.tax
})

const totalPayment = computed(() => {
  return localData.value.total_payment
})

const showMobileSidebar = ref(false)

const handleResize = () => {
  windowWidth.value = window.innerWidth
  windowHeight.value = window.innerHeight
  
  if (isDesktop.value) {
    showMobileSidebar.value = false
  }
}

onMounted(() => {
  resetAllData()
  generateBarcode()
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})

const closeMobileSidebar = () => {
  showMobileSidebar.value = false
}

const gridCols = computed(() => {
  if (isMobile.value) return 2  
  if (isTablet.value) return 3  
  
  return 4  
})

const sidebarWidth = computed(() => {
  if (isMobile.value) return '280px'  
  if (isTablet.value) return '200px'
  return '220px'
})

const productCardHeight = computed(() => {
  if (isMobile.value) return '240px'
  if (isTablet.value) return '260px'
  return '280px'
})

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

const menuItems = ref([])

// Dynamic categories based on menu data
const categories = computed(() => {
  const uniqueTypes = [...new Set(menuItems.value.map(item => item.type))]
  const categoryList = [{ id: 'all', name: 'All Items' }]
  
  uniqueTypes.forEach(type => {
    if (type === 'food') {
      categoryList.push({ id: 'food', name: 'Food' })
    } else if (type === 'drink') {
      categoryList.push({ id: 'drink', name: 'Drinks' })
    }
  })
  
  return categoryList
})

const selectedCategory = ref('all')
const searchQuery = ref('')
const fetchMenuData = async () => {
  try {
    menuLoading.value = true
    
    const response = await $api('menu', {
      method: 'GET',
      headers: {
        'Accept': 'application/json'
      }
    })

    menuItems.value = response.data.map(item => ({
      id: item.id,
      code: item.code,
      name: item.name,
      price: parseInt(item.price),
      type: item.type, 
      category: item.type, 
      qty: item.qty,
      image: item.menu_photo?.url || null, 
      isSpecial: false, 
      isCombo: false,     
      isPromo: false    
    }))

    console.log('Menu data loaded:', menuItems.value)
    
  } catch (error) {
    console.error('Error fetching menu data:', error)
    snackbarMessage.value = 'Gagal memuat data menu: ' + (error?.data?.message || error?.message || 'Unknown error')
    snackbarColor.value = 'error'
    isFlatSnackbarVisible.value = true
    
    menuItems.value = []
  } finally {
    menuLoading.value = false
  }
}

const getProductImageUrl = (product) => {
  return product.image || product.imageUrl || null
}

const handleImageError = (event) => {
  event.target.style.display = 'none'
  const iconContainer = event.target.parentElement.querySelector('.image-placeholder')
  if (iconContainer) {
    iconContainer.style.display = 'flex'
  }
}

const handleImageLoad = (event) => {
  const iconContainer = event.target.parentElement.querySelector('.image-placeholder')
  if (iconContainer) {
    iconContainer.style.display = 'none'
  }
}

const products = computed(() => menuItems.value)
const getCategoryIcon = (categoryId: string) => {
  const iconMap = {
    'all': 'tabler-apps',
    'food': 'tabler-cookie', 
    'drink': 'tabler-coffee', 
  }
  return iconMap[categoryId] || 'tabler-circle'
}

const getProductIcon = (category: string) => {
  const iconMap = {
    'food': 'tabler-cookie',
    'drink': 'tabler-coffee',
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
                  src="/images/logo/papasans.png"
                  alt="logo papasans"
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
                      v-model="localData.name"
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
                      v-model="localData.phone"
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
                      v-model="localData.email"
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

      <div
        v-if="currentPage === 'catalog'"
        class="catalog-page"
      >
        <VAppBar
          color="white"
          elevation="1"
          class="px-4"
          style="z-index: 1002;"
          fixed
        >
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

        <div class="catalog-container d-flex position-relative" style="padding-top: 64px;">
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

          <div
            v-if="!isDesktop"
            :style="overlayStyle"
            @click="closeMobileSidebar"
          />

          <div
            v-if="!isDesktop"
            class="mobile-sidebar"
            :style="mobileSidebarStyle"
          >
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

          <div 
            class="products-container"
            :style="{
              width: isDesktop ? 'calc(100vw - ' + sidebarWidth + ')' : '100vw',
              padding: isMobile ? '12px' : '24px',
              boxSizing: 'border-box',
              marginLeft: '0px', 
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
                    class="product-card h-100 overflow-hidden"
                    elevation="2"
                    rounded="md"
                    :style="{ 
                      height: productCardHeight, 
                      transition: 'transform 0.2s ease;'
                    }"
                    @mouseover="$event.currentTarget.style.transform = 'translateY(-2px)'"
                    @mouseleave="$event.currentTarget.style.transform = 'translateY(0)'"
                  >
                    <!-- Gambar memenuhi bagian atas card -->
                    <div 
                      class="product-image-container position-relative"
                      :style="{ 
                        height: isMobile ? '120px' : '150px',
                        overflow: 'hidden'
                      }"
                    >
                      <img 
                        v-if="getProductImageUrl(product)"
                        :src="getProductImageUrl(product)"
                        :alt="product.name"
                        class="product-image"
                        :style="{ 
                          width: '100%', 
                          height: '100%',
                          objectFit: 'cover',
                          objectPosition: 'center'
                        }"
                        @error="handleImageError"
                        @load="handleImageLoad"
                      />
                      
                      <div 
                        class="image-placeholder bg-grey-lighten-3 d-flex align-center justify-center" 
                        :style="{ 
                          width: '100%', 
                          height: '100%',
                          position: getProductImageUrl(product) ? 'absolute' : 'static',
                          top: getProductImageUrl(product) ? '0' : 'auto',
                          left: getProductImageUrl(product) ? '0' : 'auto',
                          display: getProductImageUrl(product) ? 'none' : 'flex'
                        }"
                        v-else
                      >
                        <VIcon 
                          :icon="getProductIcon(product.category)" 
                          :size="isMobile ? '40' : '50'"
                          color="grey-darken-1"
                        />
                      </div>

                      <!-- Badges overlay pada gambar -->
                      <VChip
                        v-if="product.isSpecial"
                        color="warning"
                        size="x-small"
                        class="position-absolute badge-chip"
                        style="top: 8px; right: 8px;"
                      >
                        <VIcon icon="tabler-star" size="10" start />
                        <span v-if="!isMobile">Special</span>
                      </VChip>
                      <VChip
                        v-if="product.isCombo"
                        color="success"
                        size="x-small"
                        class="position-absolute badge-chip"
                        style="top: 8px; right: 8px;"
                      >
                        <span v-if="!isMobile">Combo</span>
                        <VIcon v-else icon="tabler-package" size="10" />
                      </VChip>
                      <VChip
                        v-if="product.isPromo"
                        color="primary"
                        size="x-small"
                        class="position-absolute badge-chip"
                        style="top: 8px; right: 8px;"
                      >
                        <span v-if="!isMobile">Promo</span>
                        <VIcon v-else icon="tabler-percentage" size="10" />
                      </VChip>
                    </div>

                    <!-- Konten card di bagian bawah -->
                    <VCardText class="pa-3 d-flex flex-column justify-space-between flex-grow-1">
                      <div>
                        <h4 
                          class="font-weight-bold text-left mb-2"
                          :class="isMobile ? 'text-body-2' : 'text-subtitle-1'"
                          style="line-height: 1.2;"
                        >
                          {{ product.name }}
                        </h4>
                      </div>
                      
                      <div class="d-flex align-center justify-space-between mt-auto">
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

            <VCard 
              v-if="localData.cartItems.length > 0"
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

                <div class="d-flex justify-space-between align-center mb-2">
                  <span class="text-body-1">Subtotal</span>
                  <span class="text-body-1 font-weight-medium">
                    Rp{{ formatPrice(subtotal) }}
                  </span>
                </div>

                <VDivider class="my-3" />

                <div class="d-flex justify-space-between align-center mb-4">
                  <span class="text-h6 font-weight-bold">Total Pembayaran</span>
                  <span class="text-h6 font-weight-bold">
                    Rp{{ formatPrice(totalPayment) }}
                  </span>
                </div>
              </VCardText>
            </VCard>
          </VContainer>

          <div 
            v-if="localData.cartItems.length > 0"
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

          <div v-if="localData.cartItems.length > 0" style="height: 100px;"></div>
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
          margin: '0 auto',
          transition: 'width 0.3s ease'
        }"
      >
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

        <div style="padding-top: 64px;">
          <VContainer class="pa-4">
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
                
                <VRow>
                  <VCol 
                    v-for="method in paymentMethods" 
                    :key="method.id"
                    cols="6"
                    class="mb-3"
                  >
                    <VCard
                      :color="selectedPayment === method.id ? 'primary' : 'default'"
                      :variant="selectedPayment === method.id ? 'flat' : 'outlined'"
                      rounded="lg"
                      class="payment-method-card text-center pa-4 cursor-pointer"
                      elevation="0"
                      @click="selectedPayment = method.id"
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

                <VRow v-if="selectedPayment === 'qris'">
                  <VCol cols="12">
                    <VAlert type="info" border="start" color="primary" elevation="1">
                      {{ paymentMethods.find(m => m.id === 'qris')?.info }}
                    </VAlert>
                  </VCol>
                </VRow>

                <VRow v-if="selectedPayment === 'card'">
                  <VCol cols="12">
                    <VSelect
                      v-model="selectedPaymentOption"
                      :items="paymentMethods.find(m => m.id === 'card')?.options"
                      label="Pilih Virtual Account"
                      variant="outlined"
                    />
                  </VCol>
                </VRow>

                <VRow v-if="selectedPayment === 'ewallet'">
                  <VCol cols="12">
                    <VSelect
                      v-model="selectedPaymentOption"
                      :items="paymentMethods.find(m => m.id === 'ewallet')?.options"
                      label="Pilih E-Wallet"
                      variant="outlined"
                    />
                  </VCol>
                </VRow>

                <VAlert
                  v-if="selectedPaymentOption"
                  type="success"
                  border="start"
                  color="green"
                  elevation="1"
                  class="mt-4"
                >
                  Pembayaran melalui: <strong>{{ selectedPaymentOption }}</strong>
                </VAlert>

              </VCardText>
            </VCard>

            <VCard class="mb-4" elevation="2" rounded="md">
              <VCardText class="pa-4">
                <h3 class="text-h6 font-weight-bold mb-3">Informasi Pembeli</h3>
                
                <div class="mb-2">
                  <VIcon icon="tabler-user" size="16" class="mr-2" color="grey-darken-2" />
                  <span class="text-body-2">{{ localData.name }}</span>
                </div>
                
                <div class="mb-2">
                  <VIcon icon="tabler-phone" size="16" class="mr-2" color="grey-darken-2" />
                  <span class="text-body-2">{{ localData.phone }}</span>
                </div>
                
                <div v-if="localData.email" class="mb-2">
                  <VIcon icon="tabler-mail" size="16" class="mr-2" color="grey-darken-2" />
                  <span class="text-body-2">{{ localData.email }}</span>
                </div>
              </VCardText>
            </VCard>
          </VContainer>

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
                :loading="loading"
                @click="processPayment"
              >
                <span v-if="!selectedPayment">Pilih Metode Pembayaran</span>
                <span v-else>Bayar dengan {{ getPaymentMethodName(selectedPayment) }}</span>
              </VBtn>
            </VContainer>
          </div>

          <div style="height: 100px;"></div>
        </div>
      </div>

      <div
        v-if="currentPage === 'success'"
        class="products-container"
        :style="{
          width: isDesktop ? 'calc(100vw - ' + sidebarWidth + ')' : '100vw',
          maxWidth: '100%',
          padding: isMobile ? '12px' : '24px',
          boxSizing: 'border-box',
          margin: '0 auto',
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
                    src="/images/logo/papasans.png"
                    alt="logo papasans"
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

      <VSnackbar
        v-model="isFlatSnackbarVisible"
        :color="snackbarColor"
        variant="flat"
        location="top"
      >
        {{ snackbarMessage }}
        <template #actions>
          <VBtn
            color="white"
            variant="text"
            @click="isFlatSnackbarVisible = false"
          >
            Close
          </VBtn>
        </template>
      </VSnackbar>
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
  max-width: 150px;
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

@media (max-width: 767px) {
  .products-grid {
    padding: 0 8px;
  }
}

.border-primary {
  border-color: var(--v-primary-base) !important;
}
</style>

