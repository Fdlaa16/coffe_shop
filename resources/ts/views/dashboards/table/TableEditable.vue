<script setup lang="ts">
import type { TableData } from './types';

const error = ref<string | null>(null)

const rules = [
  (file: File | null) => {
    if (!file) return true
    return file.size < 1000000 || 'Ukuran gambar maksimal 1 MB!'
  },
]

const props = defineProps<{ data: TableData }>()
const emit = defineEmits(['submit', 'update:data'])

const localData = ref<TableData>({
  ...props.data,
})

console.log('props.data', props.data.food_photo);

const getImageUrl = (path: string) => {  
  return import.meta.env.VITE_APP_URL + path
}

const foodPhotoPreview = ref<string | null>(
  props.data.food_photo?.url ? getImageUrl(props.data.food_photo.url) : null
)

watch(
  () => props.data.id,
  (newId, oldId) => {
    if (newId !== oldId) {
      localData.value = JSON.parse(JSON.stringify(props.data))
    }
  },
  { immediate: true }
)

watch(localData, (newVal, oldVal) => {
  if (JSON.stringify(newVal) !== JSON.stringify(oldVal)) {
    emit('update:data', newVal)
  }
}, { deep: true })

const submitForm = () => {
  emit('update:data', localData.value) 
  emit('submit')
}

watch(() => localData.value.food_photo, (newProfileClub: any) => {
  if (newProfileClub instanceof File) {
    foodPhotoPreview.value = URL.createObjectURL(newProfileClub)
  } else if (newProfileClub?.url) {
    foodPhotoPreview.value = getImageUrl(newProfileClub.url)
  } else {
    foodPhotoPreview.value = null
  }
})

onBeforeUnmount(() => {
  if (foodPhotoPreview.value?.startsWith('blob:')) {
    URL.revokeObjectURL(foodPhotoPreview.value)
  }
})
</script>

<template>
  <form @submit.prevent="$emit('submit')">
    <div class="d-flex flex-column gap-6 mb-6">
      <VCard :title="props.data.id ? 'Ubah Meja' : 'Buat Meja'">
        <VCardText class="mt-4">
          <p>Klik tombol di bawah untuk membuat meja baru. Kode meja akan otomatis dibuat.</p>
        </VCardText>
        <VCol cols="12" class="d-flex justify-end">
          <VBtn
            color="primary"
            @click="submitForm"
          >
            Simpan
          </VBtn>
        </VCol>
      </VCard>
    </div>
  </form>
</template>
