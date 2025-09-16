<script setup lang="ts">
import axios from 'axios'
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

definePage({
  meta: {
    layout: 'blank',
    public: true,
  },
})

const route = useRoute()
const merchantOrderId = route.query.merchantOrderId as string
const resultCode = route.query.resultCode as string
const reference = route.query.reference as string

// state buat status API
const loading = ref(true)
const apiError = ref<string | null>(null)

onMounted(async () => {
  try {
    await axios.post('/api/payment/success', {
      order_id: merchantOrderId,
      result_code: resultCode,
      reference,
    })
  } catch (err: any) {
    apiError.value = err.message || 'Failed to verify payment'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-50">
    <div class="bg-white shadow-lg rounded-xl p-8 text-center max-w-md w-full">
      <h1
        class="text-3xl font-bold mb-4"
        :class="resultCode === '00' ? 'text-green-600' : 'text-red-600'"
      >
        Payment {{ resultCode === '00' ? 'Success' : 'Failed' }}
      </h1>

      <div class="space-y-1 text-gray-700 mb-6">
        <p><span class="font-medium">Order ID:</span> {{ merchantOrderId }}</p>
        <p><span class="font-medium">Reference:</span> {{ reference }}</p>
      </div>

      <div v-if="loading" class="text-gray-500">Verifying payment...</div>
      <div v-else-if="apiError" class="text-red-500">{{ apiError }}</div>

      <VBtn
        to="/authentication/login"
        color="primary"
        class="mt-4"
      >
        Back to Home
      </VBtn>
    </div>
  </div>
</template>
