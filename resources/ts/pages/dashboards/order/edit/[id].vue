<script lang="ts" setup>
import OrderEditable from '@/views/dashboards/order/OrderEditable.vue';
import type { OrderData } from '@/views/dashboards/order/types';
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const orderId = route.params.id as string;
const error = ref<string | null>(null)
const loading = ref(false)
const isFlatSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref<'success' | 'error'>('success')

const orderData = ref<OrderData>({
  id: 0,
  customer: {
    id: 0,
    user: {
      id: 0,
      email: '',
      password: '',
    },
    name: '',
    phone: '',
  },
  table: {
    id: 0,
    code: '',
    name: '',
    status: false,
  },
  orderItem: [ 
    {
      id: 0,
      code: '',
      qty: '',
      price: '',
      status: false,
    },
  ],
  order_date: '',
  total_net: '',
  status: false,
});

const fetchOrder = async () => {
  loading.value = true;
  try {
    const res = await $api(`order/${orderId}/edit`);

    orderData.value = res.data
    
  } catch (err: any) {
    error.value = err.message || 'Gagal mengambil data order';
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  await fetchOrder();
});

const handleSubmit = async () => {
  try {
    const formData = new FormData()
    formData.append('_method', 'PUT')

    formData.append('order_date', orderData.value.order_date)
    formData.append('total_net', orderData.value.total_net)
    formData.append('status', String(orderData.value.status))

    // Tambahkan data customer
    formData.append('customer[id]', String(orderData.value.customer.id))
    formData.append('customer[name]', orderData.value.customer.name)
    formData.append('customer[phone]', orderData.value.customer.phone)
    formData.append('customer[user][id]', String(orderData.value.customer.user.id))
    formData.append('customer[user][email]', orderData.value.customer.user.email)
    if (orderData.value.customer.user.password) {
      formData.append('customer[user][password]', orderData.value.customer.user.password)
    }

    // Tambahkan data table
    formData.append('table[id]', String(orderData.value.table.id))
    formData.append('table[code]', orderData.value.table.code)
    formData.append('table[name]', orderData.value.table.name)
    formData.append('table[status]', String(orderData.value.table.status))

    // Tambahkan semua orderItem
    orderData.value.orderItem.forEach((item, index) => {
      formData.append(`order_item[${index}][id]`, String(item.id ?? ''))
      formData.append(`order_item[${index}][code]`, item.code)
      formData.append(`order_item[${index}][qty]`, item.qty)
      formData.append(`order_item[${index}][price]`, item.price)
      formData.append(`order_item[${index}][status]`, String(item.status))
    })

    const res = await $api(`order/${orderData.value.id}`, {
      method: 'POST',
      body: formData,
    })

    snackbarMessage.value = 'Data berhasil diperbarui!'
    snackbarColor.value = 'success'
    isFlatSnackbarVisible.value = true

    router.push({
      name: 'dashboards-order-list',
      query: {
        success: 'Data berhasil diperbarui!',
      },
    })
  } catch (err: any) {
    const errors = err?.data?.errors

    if (err?.status === 422 && errors) {
      const messages = Object.values(errors).flat()
      snackbarMessage.value = 'Validasi gagal: ' + messages.join(', ')
    } else {
      snackbarMessage.value = 'Gagal mengirim data: ' + (err?.message || 'Unknown error')
    }

    snackbarColor.value = 'error'
    isFlatSnackbarVisible.value = true
  }
}

const onSubmit = () => {
  handleSubmit()
}

</script>

<template>
  <VRow>
    <VCol cols="12" md="12">
      <OrderEditable
        :data="orderData"  
        @update:data="orderData = $event"
        @submit="onSubmit"
      />

    </VCol>
  </VRow>
</template>
