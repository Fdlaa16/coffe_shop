<script lang="ts" setup>
import DrinkEditable from '@/views/dashboards/drink/DrinkEditable.vue';
import type { DrinkData } from '@/views/dashboards/drink/types';
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter() 
const selectedSports = ref<Sport[]>([])
const isFlatSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref<'success' | 'error'>('success')

const drinkData = ref<DrinkData>({
  id: 0,
  code: '',
  name: '',
  qty: '',
  price: '',
  drink_photo: null, 
})

const handleSubmit = async () => {
  const formData = new FormData();
  formData.append('name', drinkData.value.name);
  formData.append('qty', drinkData.value.qty);
  formData.append('price', drinkData.value.price);

  if (drinkData.value.drink_photo instanceof File)
    formData.append('drink_photo', drinkData.value.drink_photo);

  try {
    const response = await $api('drink/store', {
      method: 'POST',
      body: formData,
    });

    snackbarMessage.value = 'Data berhasil dibuat!';
    snackbarColor.value = 'success';
    isFlatSnackbarVisible.value = true;

    router.push({
      name: 'dashboards-drink-list',
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
    <DrinkEditable
      :data="drinkData"
      @update:data="drinkData = $event"
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
