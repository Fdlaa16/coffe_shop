<script lang="ts" setup>
import OrderEditable from '@/views/dashboards/order/OrderEditable.vue';
import type { OrderData } from '@/views/dashboards/order/types';
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const orderId = route.params.id as string;
const orderData = ref<OrderData | null>(null)
const error = ref<string | null>(null)
const loading = ref(false)
const isFlatSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref<'success' | 'error'>('success')

const fetchOrder = async () => {
  loading.value = true
  try {
    const res = await $api(`order/${orderId}/edit`)
    orderData.value = res.data
  } catch (err: any) {
    error.value = err.message || 'Gagal mengambil data order'
  } finally {
    loading.value = false
  }
}

const handleSubmit = async (payload: OrderData) => {
  loading.value = true
  try {
    const formData = new FormData()
    formData.append('_method', 'PUT')
    formData.append('order_date', payload.order_date)
    formData.append('total_net', String(payload.total_net))
    formData.append('status', String(payload.status))

    // Customer data
    if (payload.customer) {
      formData.append('customer[id]', String(payload.customer.id))
      formData.append('customer[name]', payload.customer.name)
      formData.append('customer[phone]', payload.customer.phone)
      if (payload.customer.address) {
        formData.append('customer[address]', payload.customer.address)
      }
      
      // User data (jika ada)
      if (payload.customer.user) {
        formData.append('customer[user][id]', String(payload.customer.user.id))
        formData.append('customer[user][email]', payload.customer.user.email)
        if (payload.customer.user.password) {
          formData.append('customer[user][password]', payload.customer.user.password)
        }
      }
    }

    // Table data
    if (payload.table) {
      formData.append('table[id]', String(payload.table.id))
      formData.append('table[code]', payload.table.code)
      formData.append('table[name]', payload.table.name)
      formData.append('table[status]', payload.table.status)
    }

    // Payment method dan notes
    if (payload.payment_method) {
      formData.append('payment_method', payload.payment_method)
    }
    if (payload.notes) {
      formData.append('notes', payload.notes)
    }

    // Order items
    payload.order_items.forEach((item, i) => {
      if (item.id) formData.append(`order_items[${i}][id]`, String(item.id))
      formData.append(`order_items[${i}][order_id]`, String(item.order_id))
      formData.append(`order_items[${i}][type]`, item.type)
      formData.append(`order_items[${i}][code]`, item.code)
      formData.append(`order_items[${i}][qty]`, String(item.qty))
      formData.append(`order_items[${i}][total]`, String(item.total))
      formData.append(`order_items[${i}][status]`, item.Status)
    })

    const response = await $api(`order/${payload.id}`, { 
      method: 'POST', 
      body: formData 
    })

    // Show success message
    snackbarMessage.value = 'Data berhasil diperbarui!'
    snackbarColor.value = 'success'
    isFlatSnackbarVisible.value = true

    // Refresh data
    await fetchOrder()
    
  } catch (err: any) {
    console.error('Gagal update order', err)
    
    const errors = err?.data?.errors
    if (err?.status === 422 && errors) {
      const messages = Object.values(errors).flat()
      snackbarMessage.value = 'Validasi gagal: ' + messages.join(', ')
    } else {
      snackbarMessage.value = 'Gagal mengupdate data: ' + (err?.message || 'Unknown error')
    }
    
    snackbarColor.value = 'error'
    isFlatSnackbarVisible.value = true
  } finally {
    loading.value = false
  }
}

onMounted(fetchOrder)
</script>

<template>
  <div>
    <!-- Show error state if fetch failed -->
    <VAlert
      v-if="error && !loading"
      type="error"
      variant="tonal"
      class="mb-4"
      prominent
    >
      <template #prepend>
        <VIcon>mdi-alert-circle</VIcon>
      </template>
      {{ error }}
      
      <template #append>
        <VBtn 
          variant="outlined" 
          size="small" 
          @click="fetchOrder"
        >
          Coba Lagi
        </VBtn>
      </template>
    </VAlert>

    <!-- Pass data dan loading state ke child component -->
    <OrderEditable
      v-if="orderData && !error"
      :data="orderData"
      :loading="loading"
      @submit="handleSubmit"
    />

    <!-- Loading state untuk initial fetch -->
    <div 
      v-else-if="loading && !orderData" 
      class="d-flex justify-center align-center" 
      style="height: 400px;"
    >
      <div class="text-center">
        <VProgressCircular indeterminate color="primary" size="60" width="6" />
        <p class="mt-4 text-h6">Memuat data pesanan...</p>
      </div>
    </div>

    <!-- Snackbar untuk feedback -->
    <VSnackbar
      v-model="isFlatSnackbarVisible"
      :color="snackbarColor"
      location="bottom start"
      variant="flat"
      timeout="3000"
    >
      {{ snackbarMessage }}
    </VSnackbar>
  </div>
</template>
