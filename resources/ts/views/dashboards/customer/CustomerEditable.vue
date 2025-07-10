<script setup lang="ts">
import type { CustomerData } from './types';

const error = ref<string | null>(null)

const rules = [
  (file: File | null) => {
    if (!file) return true
    return file.size < 1000000 || 'Ukuran gambar maksimal 1 MB!'
  },
]

const props = defineProps<{ data: CustomerData }>()
const emit = defineEmits(['submit', 'update:data'])

const localData = ref<CustomerData>({
  ...props.data,
})

const getImageUrl = (path: string) => {  
  return import.meta.env.VITE_APP_URL + path
}

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
</script>

<template>
  <form @submit.prevent="$emit('submit')">
    <div class="d-flex flex-column gap-6 mb-6">
      <VCard :title="props.data.id ? 'Edit Customer' : 'Create Customer'">
        <VCardText>
          <VWindow>
            <!-- Tab Biodata -->
            <VWindowItem >
              <VRow>
                <VCol cols="12" class="text-no-wrap">
                  <VRow class="justify-center align-center">
                    <VCol cols="12">
                      <AppTextField
                      v-model="localData.user.email"
                      label="Email"
                      placeholder="Contoh: admin@gmail.com"
                      />
                    </VCol>
                  </VRow>
                  
                  <VRow class="justify-center align-center">
                    <VCol cols="6">
                        <AppTextField
                            v-model="localData.name"
                            label="Name"
                            placeholder="Contoh: Budi"
                        />
                    </VCol>

                    <VCol cols="6">
                        <AppTextField
                            v-model="localData.phone"
                            label="price"
                            placeholder="Contoh: 081234567890"
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
