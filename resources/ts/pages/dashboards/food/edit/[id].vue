// Frontend Edit Script
<script lang="ts" setup>
import FoodEditable from '@/views/dashboards/food/FoodEditable.vue';
import type { FoodData } from '@/views/dashboards/food/types';
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const foodId = route.params.id as string;
const error = ref<string | null>(null)
const loading = ref(false)
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
});

const fetchFood = async () => {
  loading.value = true;
  try {
    const res = await $api(`food/${foodId}/edit`);

    foodData.value = res.data
    
  } catch (err: any) {
    error.value = err.message || 'Gagal mengambil data food';
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  await fetchFood();
});

const handleSubmit = async () => {
  try {        
    const formData = new FormData();
    formData.append('_method', 'PUT'); 

    formData.append('name', foodData.value.name);
    formData.append('qty', foodData.value.qty);
    formData.append('price', foodData.value.price);

    if (foodData.value.food_photo instanceof File)
      formData.append('food_photo', foodData.value.food_photo);

    const res = await $api(`food/${foodId}`, {
      method: 'POST',
      body: formData,
    });

    snackbarMessage.value = 'Data berhasil diperbarui!';
    snackbarColor.value = 'success';
    isFlatSnackbarVisible.value = true;

    router.push({
      name: 'dashboards-food-list',
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
      
      <FoodEditable
        v-else
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
