<script lang="ts" setup>
import CustomerEditable from '@/views/dashboards/customer/CustomerEditable.vue';
import type { CustomerData } from '@/views/dashboards/customer/types';
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const customerId = route.params.id as string;
const error = ref<string | null>(null)
const loading = ref(false)
const isFlatSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref<'success' | 'error'>('success')

const customerData = ref<CustomerData>({
  id: 0,
  name: '',
  user: {
    email: ''
  },
  phone: '', 
});

const fetchCustomer = async () => {
  loading.value = true;
  try {
    const res = await $api(`customer/${customerId}/edit`);

    customerData.value = res.data
    
  } catch (err: any) {
    error.value = err.message || 'Gagal mengambil data customer';
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  await fetchCustomer();
});

const handleSubmit = async () => {
  try {        
    const formData = new FormData();
    formData.append('_method', 'PUT'); 

    formData.append('name', customerData.value.name);
    formData.append('email', customerData.value.user.email);
    formData.append('phone', customerData.value.phone);

    const res = await $api(`customer/${customerId}`, {
      method: 'POST',
      body: formData,
    });

    snackbarMessage.value = 'Data berhasil diperbarui!';
    snackbarColor.value = 'success';
    isFlatSnackbarVisible.value = true;

    router.push({
      name: 'dashboards-customer-list',
      query: {
        success: 'Data berhasil diperbarui!',
      },
    });
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
};

const onSubmit = () => {
  handleSubmit()
}

</script>

<template>
  <VRow>
    <VCol cols="12" md="12">
      <CustomerEditable
        :data="customerData"  
        @update:data="customerData = $event"
        @submit="onSubmit"
      />

    </VCol>
  </VRow>
</template>
