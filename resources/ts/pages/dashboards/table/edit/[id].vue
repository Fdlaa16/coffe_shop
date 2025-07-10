<script lang="ts" setup>
import TableEditable from '@/views/dashboards/table/TableEditable.vue';
import type { TableData } from '@/views/dashboards/table/types';
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const tableId = route.params.id as string;
const error = ref<string | null>(null)
const loading = ref(false)
const isFlatSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref<'success' | 'error'>('success')

const tableData = ref<TableData>({
  id: 0,
  code: '',
  name: '',
});

const fetchTable = async () => {
  loading.value = true;
  try {
    const res = await $api(`table/${tableId}/edit`);

    tableData.value = res.data
    
  } catch (err: any) {
    error.value = err.message || 'Gagal mengambil data table';
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  await fetchTable();
});

const handleSubmit = async () => {
  try {        
    const formData = new FormData();
    formData.append('_method', 'PUT'); 

    formData.append('name', tableData.value.name);

    const res = await $api(`table/${tableId}`, {
      method: 'POST',
      body: formData,
    });

    snackbarMessage.value = 'Data berhasil diperbarui!';
    snackbarColor.value = 'success';
    isFlatSnackbarVisible.value = true;

    router.push({
      name: 'dashboards-table-list',
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
      <TableEditable
        :data="tableData"  
        @update:data="tableData = $event"
        @submit="onSubmit"
      />

    </VCol>
  </VRow>
</template>
