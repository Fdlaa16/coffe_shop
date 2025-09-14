<script lang="ts" setup>
import InvoiceEditable from '@/views/dashboards/invoice/InvoiceEditable.vue';
import type { InvoiceData } from '@/views/dashboards/invoice/types';
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter() 
const isFlatSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref<'success' | 'error'>('success')

const invoiceData = ref<InvoiceData>({
  id: 0,
  name: '',
  user: {
    email: ''
  },
  phone: '', 
})

const handleSubmit = async () => {
  const formData = new FormData();
  formData.append('name', invoiceData.value.name);
  formData.append('email', invoiceData.value.user.email);
  formData.append('phone', invoiceData.value.phone);

  try {
    const response = await $api('customer/store', {
      method: 'POST',
      body: formData,
    });

    snackbarMessage.value = 'Data berhasil dibuat!';
    snackbarColor.value = 'success';
    isFlatSnackbarVisible.value = true;

    router.push({
      name: 'dashboards-customer-list',
      query: {
        success: 'Data berhasil dibuat!',
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
    <VCol
      cols="12"
      md="12"
    >
    <InvoiceEditable
      :data="invoiceData"
      @update:data="invoiceData = $event"
      @submit="onSubmit"
    />
    </VCol>
  </VRow>

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
