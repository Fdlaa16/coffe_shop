<script setup lang="ts">
import type { DrinkData } from './types';

const error = ref<string | null>(null)

const rules = [
  (file: File | null) => {
    if (!file) return true
    return file.size < 1000000 || 'Ukuran gambar maksimal 1 MB!'
  },
]

const props = defineProps<{ data: DrinkData }>()
const emit = defineEmits(['submit', 'update:data'])

const localData = ref<DrinkData>({
  ...props.data,
})

const getImageUrl = (path: string) => {  
  return import.meta.env.VITE_APP_URL + path
}

const drinkPhotoPreview = ref<string | null>(
  props.data.drink_photo?.url ? getImageUrl(props.data.drink_photo.url) : null
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

watch(() => localData.value.drink_photo, (newProfileClub: any) => {
  if (newProfileClub instanceof File) {
    drinkPhotoPreview.value = URL.createObjectURL(newProfileClub)
  } else if (newProfileClub?.url) {
    drinkPhotoPreview.value = getImageUrl(newProfileClub.url)
  } else {
    drinkPhotoPreview.value = null
  }
})

onBeforeUnmount(() => {
  if (drinkPhotoPreview.value?.startsWith('blob:')) {
    URL.revokeObjectURL(drinkPhotoPreview.value)
  }
})
</script>

<template>
  <form @submit.prevent="$emit('submit')">
    <div class="d-flex flex-column gap-6 mb-6">
      <VCard :title="props.data.id ? 'Edit Drink' : 'Create Drink'">
        <VCardText>
          <VWindow>
            <!-- Tab Biodata -->
            <VWindowItem >
              <VRow>
                <VCol cols="12" class="text-no-wrap">
                  <h6 class="text-h6 mb-2">Drink Photo</h6>
                  <img
                    v-if="drinkPhotoPreview"
                    :src="drinkPhotoPreview"
                    alt="Preview Drink Photo"
                    style="width: 30%; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); margin-bottom: 1rem;"
                  />

                  <VFileInput
                    v-model="localData.drink_photo"
                    label="Ganti Foto"
                    accept="image/png, image/jpeg, image/bmp"
                    density="comfortable"
                  />
                  
                  <VRow class="justify-center align-center">
                    <VCol cols="4">
                        <AppTextField
                            v-model="localData.name"
                            label="name"
                            placeholder="Contoh: Rendang"
                        />
                    </VCol>

                    <VCol cols="4">
                        <AppTextField
                            v-model="localData.qty"
                            label="qty"
                            placeholder="Contoh: 10"
                        />
                    </VCol>

                    <VCol cols="4">
                        <AppTextField
                            v-model="localData.price"
                            label="price"
                            placeholder="Contoh: 10000"
                        />
                    </VCol> 
                  </VRow>
                </VCol>
              </VRow>
            </VWindowItem>
          </VWindow>
        </VCardText>

        <!-- Tombol Submit -->
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
