// Frontend Edit Script
<script lang="ts" setup>
import MenuEditable from '@/views/dashboards/menu/MenuEditable.vue';
import type { MenuData } from '@/views/dashboards/menu/types';
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const menuId = route.params.id as string;
const error = ref<string | null>(null)
const loading = ref(false)
const isFlatSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref<'success' | 'error'>('success')

const menuData = ref<MenuData>({
  id: 0,
  code: '',
  name: '',
  qty: '',
  type: '',
  price: '',
  menu_photo: null,
});

const fetchMenu = async () => {
  loading.value = true;
  try {
    const res = await $api(`menu/${menuId}/edit`);

    menuData.value = res.data
    
  } catch (err: any) {
    error.value = err.message || 'Gagal mengambil data menu';
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  await fetchMenu();
});

const handleSubmit = async () => {
  try {        
    const formData = new FormData();
    formData.append('_method', 'PUT'); 

    formData.append('name', menuData.value.name);
    formData.append('qty', menuData.value.qty);
    formData.append('type', menuData.value.type);
    formData.append('price', menuData.value.price);

    if (menuData.value.menu_photo instanceof File)
      formData.append('menu_photo', menuData.value.menu_photo);

    const res = await $api(`menu/${menuId}`, {
      method: 'POST',
      body: formData,
    });

    snackbarMessage.value = 'Data berhasil diperbarui!';
    snackbarColor.value = 'success';
    isFlatSnackbarVisible.value = true;

    router.push({
      name: 'dashboards-menu-list',
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
      <div v-if="loading" class="d-flex justify-center align-center" style="height: 200px;">
        <VProgressCircular indeterminate color="primary" />
      </div>
      
      <VAlert
        v-else-if="error"
        type="error"
        variant="tonal"
        class="mb-4"
      >
        {{ error }}
      </VAlert>
      
      <MenuEditable
        v-else
        :data="menuData"  
        @update:data="menuData = $event"
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
