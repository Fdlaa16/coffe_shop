<script lang="ts" setup>
import FoodEditable from '@/views/dashboards/food/FoodEditable.vue';
import type { FoodData } from '@/views/dashboards/food/types';
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter() 
const selectedSports = ref<Sport[]>([])
const isFlatSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref<'success' | 'error'>('success')

const foodData = ref<FoodData>({
  id: 0,
  code: '',
  name: '',
  qty: '',
  price: '',
  food_photo: null, 
})

const handleSubmit = async () => {
  const formData = new FormData();
  formData.append('name', foodData.value.name);
  formData.append('qty', foodData.value.qty);
  formData.append('price', foodData.value.price);

  if (foodData.value.food_photo instanceof File)
    formData.append('food_photo', foodData.value.food_photo);

  try {
    const response = await $api('food/store', {
      method: 'POST',
      body: formData,
    });

    snackbarMessage.value = 'Data berhasil dibuat!';
    snackbarColor.value = 'success';
    isFlatSnackbarVisible.value = true;

    router.push({
      name: 'dashboards-food-list',
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
    <FoodEditable
      :data="foodData"
      @update:data="foodData = $event"
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
