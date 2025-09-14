<script lang="ts" setup>
import MenuEditable from '@/views/dashboards/menu/MenuEditable.vue';
import type { MenuData } from '@/views/dashboards/menu/types';
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter() 
const selectedSports = ref<Sport[]>([])
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
})

const handleSubmit = async () => {
  const formData = new FormData();
  formData.append('name', menuData.value.name);
  formData.append('qty', menuData.value.qty);
  formData.append('type', menuData.value.type);
  formData.append('price', menuData.value.price);

  if (menuData.value.menu_photo instanceof File)
    formData.append('menu_photo', menuData.value.menu_photo);

  try {
    const response = await $api('menu/store', {
      method: 'POST',
      body: formData,
    });

    snackbarMessage.value = 'Data berhasil dibuat!';
    snackbarColor.value = 'success';
    isFlatSnackbarVisible.value = true;

    router.push({
      name: 'dashboards-menu-list',
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
    <MenuEditable
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
