<script lang="ts" setup>
import InvoiceEditable from '@/views/dashboards/invoice/InvoiceEditable.vue';
import type { InvoiceData } from '@/views/dashboards/invoice/types';
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const invoiceId = route.params.id as string;
const error = ref<string | null>(null)
const loading = ref(false)
const isFlatSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref<'success' | 'error'>('success')

const invoiceData = ref<InvoiceData>({
  id: 0,
  invoice_number: '',
  invoice_date: '',
  expired_date: '',
  order_id: 0,
  customer_id: 0,
  total_net: 0,
  status: 'draft',
  invoice_items: [], 
});

const fetchInvoice = async () => {
  loading.value = true;
  try {
    const res = await $api(`invoice/${invoiceId}/edit`);

    invoiceData.value = res.data
    
  } catch (err: any) {
    error.value = err.message || 'Gagal mengambil data invoice';
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  await fetchInvoice();
});

const handleSubmit = async () => {
  try {
    const res = await $api(`invoice/${invoiceId}`, {
      method: 'PUT',
      body: {},
    });

    snackbarMessage.value = 'Data berhasil diperbarui!';
    snackbarColor.value = 'success';
    isFlatSnackbarVisible.value = true;

    router.push({
      name: 'dashboards-invoice-list',
      query: { success: 'Data berhasil diperbarui!' },
    });
  } catch (err: any) {
    const errors = err?.data?.errors;

    if (err?.status === 422 && errors) {
      const messages = Object.values(errors).flat();
      snackbarMessage.value = 'Validasi gagal: ' + messages.join(', ');
    } else {
      snackbarMessage.value = 'Gagal mengirim data: ' + (err?.message || 'Unknown error');
    }

    snackbarColor.value = 'error';
    isFlatSnackbarVisible.value = true;
  }
};


const onSubmit = () => {
  handleSubmit()
}

</script>

<template>
  <VRow>
    <VCol cols="12" md="12">
      <InvoiceEditable
        :data="invoiceData"  
        @update:data="invoiceData = $event"
        @submit="onSubmit"
      />

    </VCol>
  </VRow>
</template>
