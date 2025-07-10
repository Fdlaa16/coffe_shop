<script lang="ts" setup>
import DrinkEditable from '@/views/dashboards/drink/DrinkEditable.vue';
import type { DrinkData } from '@/views/dashboards/drink/types';
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const drinkId = route.params.id as string;
const error = ref<string | null>(null)
const loading = ref(false)
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
});

const fetchDrink = async () => {
  loading.value = true;
  try {
    const res = await $api(`drink/${drinkId}/edit`);

    drinkData.value = res.data
    
  } catch (err: any) {
    error.value = err.message || 'Gagal mengambil data drink';
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  await fetchDrink();
});

const handleSubmit = async () => {
  try {        
    const formData = new FormData();
    formData.append('_method', 'PUT'); 

    formData.append('name', drinkData.value.name);
    formData.append('qty', drinkData.value.qty);
    formData.append('price', drinkData.value.price);

    if (drinkData.value.drink_photo instanceof File)
      formData.append('drink_photo', drinkData.value.drink_photo);

    const res = await $api(`drink/${drinkId}`, {
      method: 'POST',
      body: formData,
    });

    snackbarMessage.value = 'Data berhasil diperbarui!';
    snackbarColor.value = 'success';
    isFlatSnackbarVisible.value = true;

    router.push({
      name: 'dashboards-drink-list',
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
      <DrinkEditable
        :data="drinkData"  
        @update:data="drinkData = $event"
        @submit="onSubmit"
      />

    </VCol>
  </VRow>
</template>
