<script setup lang="ts">
import type { MenuData } from './types';

const error = ref<string | null>(null)

const rules = [
  (file: File | null) => {
    if (!file) return true
    return file.size < 1000000 || 'Ukuran gambar maksimal 1 MB!'
  },
]

const props = defineProps<{ data: MenuData }>()
const emit = defineEmits(['submit', 'update:data'])

const localData = ref<MenuData>({
  ...props.data,
})

console.log('props.data', props.data.menu_photo);

const getImageUrl = (path: string) => {  
  return import.meta.env.VITE_APP_URL + path
}

const menuPhotoPreview = ref<string | null>(
  props.data.menu_photo?.url ? getImageUrl(props.data.menu_photo.url) : null
)

const types = [
  { title: 'Pilih Tipe', value: '' },
  { title: 'Makanan', value: 'food' },
  { title: 'Minuman', value: 'drink' },
];

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

watch(() => localData.value.menu_photo, (newProfileClub: any) => {
  if (newProfileClub instanceof File) {
    menuPhotoPreview.value = URL.createObjectURL(newProfileClub)
  } else if (newProfileClub?.url) {
    menuPhotoPreview.value = getImageUrl(newProfileClub.url)
  } else {
    menuPhotoPreview.value = null
  }
})

onBeforeUnmount(() => {
  if (menuPhotoPreview.value?.startsWith('blob:')) {
    URL.revokeObjectURL(menuPhotoPreview.value)
  }
})
</script>

<template>
  <form @submit.prevent="$emit('submit')">
    <div class="d-flex flex-column gap-6 mb-6">
      <VCard :title="props.data.id ? 'Ubah Menu' : 'BUat Menu'">
        <VCardText>
          <VWindow>
            <!-- Tab Biodata -->
            <VWindowItem >
              <VRow>
                <VCol cols="12" class="text-no-wrap">
                  <h6 class="text-h6 mb-2">Foto Menu</h6>
                  <img
                    v-if="menuPhotoPreview"
                    :src="menuPhotoPreview"
                    alt="Preview Menu Photo"
                    style="width: 30%; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); margin-bottom: 1rem;"
                  />

                  <VFileInput
                    v-model="localData.menu_photo"
                    label="Ganti Foto"
                    accept="image/png, image/jpeg, image/bmp"
                    density="comfortable"
                  />
                  
                  <VRow class="justify-center align-center">
                    <VCol cols="3">
                        <AppTextField
                            v-model="localData.name"
                            label="Nama"
                            placeholder="Contoh: Rendang"
                        />
                    </VCol>

                    <VCol cols="3">
                        <AppTextField
                            v-model="localData.qty"
                            label="Qty"
                            placeholder="Contoh: 10"
                        />
                    </VCol>

                    <VCol cols="3">
                        <AppSelect
                        label="Posisi"
                        v-model="localData.type"
                        :items="types"
                        clearable
                        clear-icon="tabler-x"
                        single-line
                      />
                    </VCol>

                    <VCol cols="3">
                        <AppTextField
                            v-model="localData.price"
                            label="Harga"
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
