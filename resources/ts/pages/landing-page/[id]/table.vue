<script setup lang="ts">
import QRCode from 'qrcode'
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'

const route = useRoute()
const router = useRouter()

// Ambil ID dari route params
const tableId = route.params.id as string

// Query parameters untuk payment return
const merchantOrderId = route.query.merchantOrderId as string
const resultCode = route.query.resultCode as string
const reference = route.query.reference as string

const isFlatSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref<'success' | 'error'>('success')
const exportLoading = ref(false)
const isSnackbarVisible = ref(false)
const loading = ref(false)
const menuLoading = ref(false)

const orderData = ref(null)
const invoiceData = ref(null)
const customerData = ref(null)

const localData = ref({
  name: '',
  phone: '',
  email: '',
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

const currentPage = ref('customer-form')

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
  return (localData.value.name?.length ?? 0) >= 2 && 
         (localData.value.phone?.length ?? 0) >= 10 &&
         (!localData.value.email || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(localData.value.email))
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
    selectedPayment: '',
    selectedPaymentOption: '',
    method: '',
    method_name: '',
    option: '',
    total_items: 0,
    subtotal: 0,
    tax: 0,
    total_payment: 0
  }
  
  // Reset order data
  orderData.value = null
  invoiceData.value = null
  customerData.value = null
  
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
    // Gunakan tableId dalam QR code
    const qrUrl = `${window.location.origin}/landing-page/${tableId}/table`
    QRCode.toCanvas(barcodeCanvas.value, qrUrl, {
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
  if (product.qty <= 0) {
    snackbarMessage.value = `Maaf, ${product.name} sedang tidak tersedia`
    snackbarColor.value = 'error'
    isFlatSnackbarVisible.value = true
    return
  }

  const existingItem = localData.value.cartItems.find(item => item.id === product.id)
  const currentCartQty = existingItem ? existingItem.qty : 0
  
  if (currentCartQty >= product.qty) {
    snackbarMessage.value = `Maksimal ${product.qty} item untuk ${product.name}`
    snackbarColor.value = 'warning'
    isFlatSnackbarVisible.value = true
    return
  }

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
      category: product.type,
      max_qty: product.qty
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
  const product = menuItems.value.find(menu => menu.id === productId)
  
  if (item && product) {
    if (item.qty >= product.qty) {
      snackbarMessage.value = `Maksimal ${product.qty} item untuk ${product.name}`
      snackbarColor.value = 'warning'
      isFlatSnackbarVisible.value = true
      return
    }
    
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

const createDuitkuPayment = async (paymentData) => {
  try {
    const response = await $api('order/payment/create', {
      method: 'POST',
      body: paymentData
    })
    return response
  } catch (error) {
    throw error
  }
}

const prepareOrderData = () => {
  return {
    name: localData.value.name,
    phone: localData.value.phone,
    email: localData.value.email,
    order_type: localData.value.order_type,
    table_id: tableId, // Tambahkan table_id
    cartItems: localData.value.cartItems.map(item => ({
      menu_id: item.menu_id,
      menu_name: item.menu_name,
      qty: item.qty,
      price: item.price,
      subtotal: item.subtotal,
      notes: item.notes,
      size: item.size,
      sugar_level: item.sugar_level,
      category: item.category
    })),
    method: localData.value.method,
    method_name: localData.value.method_name,
    option: localData.value.option,
    total_items: localData.value.total_items,
    subtotal: localData.value.subtotal,
    tax: localData.value.tax,
    total_payment: localData.value.total_payment
  }
}

const preparePaymentData = () => {
  // ✅ Buat return URL yang benar dengan tableId dan parameter khusus
  const returnUrl = `${window.location.origin}/landing-page/${tableId}/table`
  
  return {
    customer_name: localData.value.name,
    email: localData.value.email,
    phone: localData.value.phone,
    amount: localData.value.total_payment.toString(),
    product_details: generateProductDetails(),
    item_details: localData.value.cartItems.map(item => ({
      name: item.name,
      price: parseInt(item.price),
      quantity: parseInt(item.qty)
    })),
    payment_method: selectedPaymentOption.value.value,
    table_id: tableId, // ✅ Kirim table_id
    return_url: returnUrl // ✅ Kirim return_url yang benar
  }
}

const handlePaymentError = (err) => {
  console.error('Payment error:', err)
  
  const errors = err?.data?.errors
  if (err?.status === 422 && errors) {
    const messages = Object.values(errors).flat()
    snackbarMessage.value = 'Validasi gagal: ' + messages.join(', ')
  } else {
    snackbarMessage.value = 'Gagal memproses pembayaran: ' + (err?.data?.message || err?.message || 'Unknown error')
  }

  snackbarColor.value = 'error'
  isFlatSnackbarVisible.value = true
}

const generateProductDetails = () => {
  const productDetails = localData.value.cartItems.map(item => 
    `${item.name} (${item.qty}x) - Rp${formatPrice(item.price)}`
  ).join(', ')
  
  return `Pesanan: ${productDetails}. Total: ${localData.value.total_items} item(s)`
}

const handlePaymentSuccess = async () => {
  try {
    loading.value = true
    
    const pendingOrderDataStr = localStorage.getItem('pendingOrderData')
    if (!pendingOrderDataStr) {
      throw new Error('Data order tidak ditemukan. Silakan buat pesanan baru.')
    }

    const pendingOrderData = JSON.parse(pendingOrderDataStr)
    const formData = new FormData()
    
    formData.append('name', pendingOrderData.name)
    formData.append('phone', pendingOrderData.phone)
    formData.append('email', pendingOrderData.email)
    formData.append('order_type', pendingOrderData.order_type)
    formData.append('table_id', tableId) // Tambahkan table_id
    
    pendingOrderData.cartItems.forEach((item, index) => {
      formData.append(`cartItems[${index}][menu_id]`, item.menu_id.toString())
      formData.append(`cartItems[${index}][menu_name]`, item.menu_name)
      formData.append(`cartItems[${index}][qty]`, item.qty.toString())
      formData.append(`cartItems[${index}][price]`, item.price.toString())
      formData.append(`cartItems[${index}][subtotal]`, item.subtotal.toString())
      formData.append(`cartItems[${index}][notes]`, item.notes || '')
      formData.append(`cartItems[${index}][size]`, item.size || 'Regular')
      formData.append(`cartItems[${index}][sugar_level]`, item.sugar_level || 'Normal')
      formData.append(`cartItems[${index}][category]`, item.category)
    })

    formData.append('method', pendingOrderData.method)
    formData.append('method_name', pendingOrderData.method_name)
    formData.append('option', pendingOrderData.option)
    formData.append('total_items', pendingOrderData.total_items.toString())
    formData.append('subtotal', pendingOrderData.subtotal.toString())
    formData.append('tax', pendingOrderData.tax.toString())
    formData.append('total_payment', pendingOrderData.total_payment.toString())
    
    if (pendingOrderData.payment_reference) {
      formData.append('payment_reference', pendingOrderData.payment_reference)
    }
    
    const orderResponse = await $api('order/create', {
      method: 'POST',
      body: formData
    })

    if (orderResponse.success && orderResponse.data) {
      orderData.value = orderResponse.data.order
      invoiceData.value = orderResponse.data.invoice
      customerData.value = orderResponse.data.customer
      
      localData.value.method = pendingOrderData.method
      localData.value.method_name = pendingOrderData.method_name
      localData.value.option = pendingOrderData.option
    }

    localStorage.removeItem('pendingOrderData')
    
    resetAllData()    
    currentPage.value = 'success'
    
    snackbarMessage.value = 'Pesanan berhasil dibuat!'
    snackbarColor.value = 'success'
    isFlatSnackbarVisible.value = true
    
  } catch (err) {
    console.error('Error creating order:', err)
    
    let errorMessage = 'Pembayaran berhasil tetapi gagal membuat pesanan. Silakan hubungi customer service.'
    if (err?.data?.message) {
      errorMessage += ' Detail: ' + err.data.message
    } else if (err?.message) {
      errorMessage += ' Detail: ' + err.message
    }
    
    snackbarMessage.value = errorMessage
    snackbarColor.value = 'error'
    isFlatSnackbarVisible.value = true    
  } finally {
    loading.value = false
  }
}

const processPayment = async () => {
  try {
    processing.value = true
    updateCalculatedValues()

    if (!selectedPaymentOption.value) {
      throw new Error('Pilih metode pembayaran terlebih dahulu')
    }

    const orderData = prepareOrderData()
    const paymentData = preparePaymentData()

    const paymentResponse = await createDuitkuPayment(paymentData)
    
    if (!paymentResponse.success) {
      throw new Error(paymentResponse.message || 'Gagal membuat pembayaran')
    }

    const dataToSave = {
      ...orderData,
      payment_reference: paymentResponse.data.reference,
      payment_url: paymentResponse.data.payment_url
    }

    // Simpan tableId di localStorage juga
    localStorage.setItem('pendingOrderData', JSON.stringify(dataToSave))
    localStorage.setItem('currentTableId', tableId)
    
    window.location.href = paymentResponse.data.payment_url
    
  } catch (err) {
    processing.value = false
    handlePaymentError(err)
  }
}

const getPaymentMethodName = (methodId) => {
  const method = paymentMethods.value.find(m => m.id === methodId)
  return method ? method.name : ''
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('id-ID').format(price)
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

onMounted(async () => {
  console.log('Route params:', route.params);
  console.log('Route query:', route.query);
  
  // ✅ Cek apakah ini return dari payment dengan lebih robust
  const merchantOrderId = route.query.merchantOrderId as string
  const resultCode = route.query.resultCode as string
  const reference = route.query.reference as string
  const fromPayment = route.query.from as string
  
  console.log('Payment return params:', { merchantOrderId, resultCode, reference, fromPayment });
  
  try {
    // ✅ Jika ada parameter payment, proses sebagai return dari payment
    if (merchantOrderId && resultCode) {
      console.log('Processing payment return...');
      await updateTransaction()
    } 
    // ✅ Jika ada parameter 'from=payment' tapi tidak ada merchantOrderId
    else if (fromPayment === 'payment') {
      console.log('Return from payment but no transaction data, resetting...');
      resetAllData()
      currentPage.value = 'customer-form'
      generateBarcode()
    }
    // ✅ Normal flow - fresh start
    else {
      console.log('Normal flow - fresh start');
      resetAllData()
      currentPage.value = 'customer-form'
      generateBarcode()
    }
    
    // ✅ Load payment methods di semua kondisi
    await loadPaymentMethods()
    
  } catch (error) {
    console.error('Error in onMounted:', error);
    // ✅ Jika ada error, fallback ke normal flow
    resetAllData()
    currentPage.value = 'customer-form'
    generateBarcode()
    await loadPaymentMethods()
  }
  
  // ✅ Setup window resize listener
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
  product_details: '',
  item_details: []
})

watch(() => currentPage.value, (newPage) => {
  if (newPage === 'payment') {
    paymentForm.value.customer_name = localData.value.name
    paymentForm.value.email = localData.value.email
    paymentForm.value.phone = localData.value.phone
    paymentForm.value.amount = localData.value.total_payment.toString()
    
    const productDetails = localData.value.cartItems.map(item => 
      `${item.name} (${item.qty}x) - Rp${formatPrice(item.price)}`
    ).join(', ')
    
    paymentForm.value.product_details = `Pesanan: ${productDetails}. Total: ${localData.value.total_items} item(s)`

    paymentForm.value.item_details = localData.value.cartItems.map(item => ({
      name: item.name,
      price: parseInt(item.price), 
      quantity: parseInt(item.qty) 
    }))
  }
})

const selectedPaymentMethod = computed(() => {
  return paymentMethods.value.find(method => method.id === selectedPayment.value)
})

const loadPaymentMethods = async () => {
  try {
    loading.value = true
    error.value = ''
    
    const response = await $api('order/payment/methods', {
      method: 'GET'
    })
    
    if (response.success) {
      paymentMethods.value = response.data.filter(
        (method: any) => method.id === 'va'
      )
    } else {
      error.value = response.message || 'Gagal memuat metode pembayaran'
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

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(amount)
}

const apiError = ref<string | null>(null)

// Fungsi updateTransaction yang sudah diperbaiki
const updateTransaction = async () => {
  if (merchantOrderId && resultCode) {
    try {
      loading.value = true
      
      const verificationResponse = await $api('order/payment/success', {
        method: 'POST',
        body: {
          order_id: merchantOrderId,
          result_code: resultCode,
          reference: reference,
        },
      })
      
      let isSuccess = false
      let responseMessage = ''
      
      console.log('verificationResponse', verificationResponse);
      
      if (verificationResponse && typeof verificationResponse === 'object') {
        isSuccess = verificationResponse.success === true
        responseMessage = verificationResponse.message || ''
      } else {
        isSuccess = false
        responseMessage = 'Response tidak valid dari server'
      }
      
      console.log('isSuccess', isSuccess);
      
      if (isSuccess) {
        await handlePaymentSuccess()
        
        snackbarMessage.value = responseMessage || 'Pembayaran berhasil diverifikasi'
        snackbarColor.value = 'success'
        isFlatSnackbarVisible.value = true
        
      } else {
        snackbarMessage.value = responseMessage || 'Pembayaran gagal atau dibatalkan'
        snackbarColor.value = 'error'
        isFlatSnackbarVisible.value = true
        currentPage.value = 'payment'
      }

      // PENTING: Clear query params setelah processing selesai
      // tetapi tetap di route yang sama dengan tableId
      await router.replace({ 
        name: 'landing-page-id-table',
        params: { id: tableId }
      })
      
    } catch (err) {
      apiError.value = err.message || 'Failed to verify payment'
      
      let errorMessage = 'Gagal memverifikasi pembayaran'
      if (err?.data?.message) {
        errorMessage += ': ' + err.data.message
      } else if (err?.message) {
        errorMessage += ': ' + err.message
      }
      
      snackbarMessage.value = errorMessage
      snackbarColor.value = 'error'
      isFlatSnackbarVisible.value = true
      currentPage.value = 'payment'
      
      // Clear query params even on error
      await router.replace({ 
        name: 'landing-page-id-table',
        params: { id: tableId }
      })
      
    } finally {
      loading.value = false
    }
  }
}

const handleDownloadReceipt = async (invoiceId) => {
  exportLoading.value = true
  
  try {
    // Gunakan invoice ID yang diterima dari parameter
    const id = invoiceId || invoiceData.value?.id || orderData.value?.id
    
    if (!id) {
      throw new Error('Invoice ID tidak ditemukan')
    }

    const response = await $api(`order/invoice/${id}/download-receipt`, {
      method: 'GET',
      responseType: 'blob',
    })

    const blob = new Blob([response], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    
    // Buat nama file dengan invoice number jika ada
    const invoiceNumber = invoiceData.value?.invoice_number || `invoice_${id}`
    const timestamp = new Date().toISOString().slice(0, 19).replace(/[:.]/g, '-')
    link.download = `receipt_${invoiceNumber}_${timestamp}.pdf`
    
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)

    snackbarMessage.value = 'Receipt berhasil diunduh'
    snackbarColor.value = 'success'
    isFlatSnackbarVisible.value = true

  } catch (err) {
    console.error('Export error:', err)
    snackbarMessage.value = err?.response?.data?.message || err?.message || 'Gagal mengunduh receipt'
    snackbarColor.value = 'error'
    isFlatSnackbarVisible.value = true
  } finally {
    exportLoading.value = false
  }
}

definePage({
  meta: {
    layout: 'blank',
    public: true,
  },
})
</script>

<template>
  <VContainer fluid class="pa-0 fill-height">
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

      <div
        v-if="currentPage === 'cart'"
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
            @click="currentPage = 'catalog'"
          />
          
          <VAppBarTitle class="font-weight-bold">
            Detail Pesanan
          </VAppBarTitle>
          
          <VSpacer />
        </VAppBar>

        <div style="padding-top: 64px;">
          <VContainer class="pa-4">
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

                <div v-if="localData.cartItems.length > 0">
                  <div
                    v-for="(item, index) in localData.cartItems"
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
                            {{ item.size || 'Regular' }} Size
                          </p>
                        </div>
                      </VCol>
                      
                      <VCol cols="4" class="pa-2">
                        <div class="text-right">
                          <p class="text-body-1 font-weight-bold mb-2">
                            Rp{{ formatPrice(item.price) }}
                          </p>
                          
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
                              @click="decreaseQty(item.id)"
                              :disabled="item.qty <= 1"
                              style="min-width: 24px; width: 24px; height: 24px;"
                            />
                            
                            <span class="mx-2 text-body-1 font-weight-bold" style="min-width: 20px; text-align: center;">
                              {{ item.qty }}
                            </span>
                            
                            <VBtn
                              icon="tabler-plus"
                              variant="flat"
                              size="x-small"
                              color="primary"
                              @click="increaseQty(item.id)"
                              style="min-width: 24px; width: 24px; height: 24px;"
                            />
                          </div>
                        </div>
                      </VCol>
                    </VRow>
                    
                    <VDivider v-if="index < localData.cartItems.length - 1" class="mt-3" />
                  </div>
                </div>

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
                
                <!-- <div class="d-flex justify-space-between align-center mb-2">
                  <span class="text-body-2">{{ cartItemsCount }} Item</span>
                  <span class="text-body-2">Rp{{ formatPrice(subtotal) }}</span>
                </div> -->
                <div v-for="(item, index) in localData.cartItems" :key="index" class="d-flex justify-space-between align-center py-1">
                  <div class="flex-grow-1">
                    <span class="text-body-2">{{ item.name }}</span>
                    <span class="text-body-2 ms-2">
                      ({{ item.qty }}x)
                    </span>
                  </div>
                  <div class="text-right">
                    <span class="text-body-2">Rp{{ formatPrice(item.price * item.qty) }}</span>
                  </div>
                </div>
                
                <div class="d-flex justify-space-between align-center mb-2 mt-2">
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
                    sm="6"
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
                      <!-- Product Details Display - Compact Version -->
                     
                    </VRow>
                  </VCardText>
                </VCard>

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
              :disabled="!selectedPayment || processing"
              :loading="processing"
              @click="processPayment"
            >
              <template v-if="processing">
                Memproses Pembayaran...
              </template>
              <template v-else-if="!selectedPayment">
                Pilih Metode Pembayaran
              </template>
              <template v-else>
                Bayar dengan {{ getPaymentMethodName(selectedPayment) }}
              </template>
            </VBtn>
            
            <!-- Optional: Progress indicator -->
            <div v-if="processing" class="text-center mt-2">
              <VProgressLinear 
                indeterminate 
                color="primary" 
                height="2"
                rounded
              />
              <div class="text-caption text-medium-emphasis mt-1">
                Jangan tutup halaman ini...
              </div>
            </div>
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
          transition: 'width 0.3s ease',
          background: '#f9fafb' // background abu-abu lembut
        }"
      >
        <VRow class="ma-0 fill-height">
          <VCol cols="12" class="d-flex align-center justify-center pa-6">
            <VCard
              class="pa-8 text-center"
              max-width="480"
              width="100%"
              elevation="10"
              rounded="xl"
            >
              <VCardText>
                <!-- Logo -->
                <div class="mb-4">
                  <img
                    src="/images/logo/papasans.png"
                    alt="logo papasans"
                    class="logo mb-3"
                    style="max-width: 120px;"
                  />
                </div>

                <!-- Ikon sukses -->
                <VIcon icon="tabler-check-circle" size="72" color="success" class="mb-4" />

                <!-- Judul -->
                <h1 class="text-h4 font-weight-bold text-success mb-3">
                  Pembayaran Berhasil!
                </h1>

                <!-- Pesan -->
                <p class="text-body-1 text-medium-emphasis mb-4">
                  Terima kasih telah berbelanja di <strong>Papasans</strong>.<br />
                  Pesanan Anda sedang diproses.
                </p>

                <p class="text-body-2 text-medium-emphasis">
                  Untuk informasi lebih lanjut, akan kami informasikan kembali melalui email kami.
                </p>
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
