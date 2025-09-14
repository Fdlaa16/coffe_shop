<script lang="ts" setup>
import type { OrderData, OrderItem } from '@/views/dashboards/order/types';
import Swal from 'sweetalert2';
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const props = defineProps<{
  data: OrderData | null;
  loading: boolean;
}>()

const emit = defineEmits<{ 
  (e: 'submit', payload: OrderData): void;
  (e: 'statusUpdated', payload: { id: string; status: string; data?: OrderData }): void;
}>()

const route = useRoute()
const router = useRouter()

const error = ref<string | null>(null)
const isFlatSnackbarVisible = ref(false)
const actionLoading = ref(false) // Separate loading untuk actions
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref<'error' | 'warning' | 'success'>('success')

onMounted(() => {
  if (route.query.success) {
    snackbarMessage.value = String(route.query.success)
    snackbarColor.value = 'success'
    isSnackbarVisible.value = true
    router.replace({ query: {} })
  }
})

const orderData = ref<OrderData | null>(null)
watch(
  () => props.data,
  (newData) => {
    if (newData) {
      orderData.value = { ...newData }
    } else {
      orderData.value = null
    }
  },
  { immediate: true }
)

const orderItems = computed<OrderItem[]>(() => {
  if (!orderData.value) return []
  if (orderData.value.orderItems && orderData.value.orderItems.length > 0) {
    return orderData.value.orderItems
  }
  return orderData.value.order_items ?? []
})

const formatCurrency = (amount?: number | string | null) => {
  const value = Number(amount ?? 0)
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(isNaN(value) ? 0 : value)
}

const formatDate = (dateString?: string) => {
  if (!dateString) return '-'
  const d = new Date(dateString)
  if (isNaN(d.getTime())) return dateString 
  return d.toLocaleString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const displayOrderNumber = computed(() => {
  if (!orderData.value) return ''
  return (orderData.value as any).code ?? orderData.value.order_number ?? `Order #${orderData.value.id}`
})

const displayTotalNet = computed(() => {
  return orderData.value ? formatCurrency((orderData.value as any).total_net) : formatCurrency(0)
})

const displayTax = computed(() => {
  return orderData.value ? formatCurrency((orderData.value as any).tax) : formatCurrency(0)
})

const displaySubTotal = computed(() => {
  return orderData.value ? formatCurrency((orderData.value as any).subtotal) : formatCurrency(0)
})

const statusOptions = [
  { value: 'reject', text: 'Dibatalkan', color: 'error', icon: 'tabler-x' },
  { value: 'pending', text: 'Menunggu Konfirmasi', color: 'warning', icon: 'tabler-alarm' },
  { value: 'process', text: 'Diproses', color: 'primary', icon: 'tabler-refresh' },
  { value: 'finished', text: 'Selesai', color: 'success', icon: 'tabler-check' },
] as const

const currentStatus = computed(() => {
  const st = orderData.value?.status
  return statusOptions.find(s => s.value === st) ?? { value: 'unknown', text: 'Tidak diketahui', color: 'grey', icon: 'tabler-help' }
})

const customerName = computed(() => orderData.value?.customer?.name ?? 'Guest')
const customerPhone = computed(() => orderData.value?.customer?.phone ?? '-')
const customerEmail = computed(() => orderData.value?.customer?.user?.email ?? '-')
const tableCode = computed(() => orderData.value?.table?.code ?? '-')

// Fungsi helper untuk update status lokal
const updateLocalStatus = (newStatus: string) => {
  if (orderData.value) {
    orderData.value = { ...orderData.value, status: newStatus }
  }
}

// Fungsi helper untuk show success message
const showSuccessMessage = (message: string) => {
  snackbarMessage.value = message
  snackbarColor.value = 'success'
  isFlatSnackbarVisible.value = true
}

// Fungsi helper untuk show error message
const showErrorMessage = (message: string) => {
  snackbarMessage.value = message
  snackbarColor.value = 'error'
  isFlatSnackbarVisible.value = true
}

// Optimized process order function
async function processOrder(order: any) {
  const confirm = await Swal.fire({
    title: 'Memproses pesanan?',
    text: `Pesanan akan diproses.`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, proses!',
    cancelButtonText: 'Batal',
    customClass: {
      confirmButton: 'swal2-confirm-btn',
      cancelButton: 'swal2-cancel-btn',
    },
  })

  if (confirm.isConfirmed) {
    try {
      actionLoading.value = true
      
      // Update UI optimistically (immediate feedback)
      updateLocalStatus('process')
      showSuccessMessage('Pesanan sedang diproses...')

      // Send API request
      const response = await $api(`order/${order.id}/process`, {
        method: 'PUT',
      })

      // Update with real data if available
      if (response.data) {
        orderData.value = { ...orderData.value, ...response.data }
      }
      
      // Emit event to parent for further actions
      emit('statusUpdated', { 
        id: order.id, 
        status: 'process',
        data: orderData.value 
      })

      showSuccessMessage('Pesanan berhasil diproses!')

    } catch (err: any) {
      // Revert optimistic update on error
      updateLocalStatus(order.status)
      showErrorMessage(err?.response?.data?.message || 'Gagal memproses pesanan')
    } finally {
      actionLoading.value = false
    }
  }
}

// Optimized finish order function
async function finishedOrder(order: any) {
  const confirm = await Swal.fire({
    title: 'Pesanan Selesai Diproses?',
    text: `Apakah pesanan sudah selesai diproses?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, selesai!',
    cancelButtonText: 'Batal',
    customClass: {
      confirmButton: 'swal2-confirm-btn',
      cancelButton: 'swal2-cancel-btn',
    },
  })

  if (confirm.isConfirmed) {
    try {
      actionLoading.value = true
      
      // Optimistic update
      updateLocalStatus('finished')
      showSuccessMessage('Menyelesaikan pesanan...')

      const response = await $api(`order/${order.id}/finished`, {
        method: 'PUT',
      })

      if (response.data) {
        orderData.value = { ...orderData.value, ...response.data }
      }

      emit('statusUpdated', { 
        id: order.id, 
        status: 'finished',
        data: orderData.value 
      })

      showSuccessMessage('Pesanan berhasil diselesaikan!')

    } catch (err: any) {
      // Revert on error
      updateLocalStatus(order.status)
      showErrorMessage(err?.response?.data?.message || 'Gagal menyelesaikan pesanan')
    } finally {
      actionLoading.value = false
    }
  }
}

// Optimized reject order function
async function rejectOrder(order: any) {
  const result = await Swal.fire({
    title: 'Tolak pesanan?',
    text: `Pesanan akan ditolak.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, tolak!',
    cancelButtonText: 'Batal',
    customClass: {
      confirmButton: 'swal2-confirm-btn',
      cancelButton: 'swal2-cancel-btn',
    },
  })

  if (result.isConfirmed) {
    try {
      actionLoading.value = true
      
      // Optimistic update
      updateLocalStatus('reject')
      showSuccessMessage('Menolak pesanan...')

      const response = await $api(`order/${order.id}/reject`, {
        method: 'PUT',
      })

      if (response.data) {
        orderData.value = { ...orderData.value, ...response.data }
      }

      emit('statusUpdated', { 
        id: order.id, 
        status: 'reject',
        data: orderData.value 
      })

      showSuccessMessage('Pesanan berhasil ditolak')

    } catch (err: any) {
      // Revert on error
      updateLocalStatus(order.status)
      showErrorMessage(err?.response?.data?.message || 'Gagal menolak pesanan')
    } finally {
      actionLoading.value = false
    }
  }
}

const handleAction = (action: string) => {
  if (!orderData.value) return
  const order = orderData.value

  if (action === 'reject') return rejectOrder(order)
  if (action === 'process') return processOrder(order)
  if (action === 'finish') return finishedOrder(order)
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <!-- Loading State -->
      <div v-if="loading" class="d-flex justify-center align-center" style="height: 400px;">
        <div class="text-center">
          <VProgressCircular indeterminate color="primary" size="60" width="6" />
          <p class="mt-4 text-h6">Memuat data pesanan...</p>
        </div>
      </div>

      <!-- Error State -->
      <VAlert
        v-else-if="error"
        type="error"
        variant="tonal"
        class="mb-4"
        prominent
      >
        <template #prepend>
          <VIcon>mdi-alert-circle</VIcon>
        </template>
        {{ error }}
      </VAlert>

      <!-- Main Content -->
      <div v-else-if="orderData">
        <!-- Header Card with real-time status -->
        <VCard class="mb-6" elevation="2">
          <VCardText>
            <div class="d-flex justify-space-between align-center flex-wrap">
              <div>
                <h4 class="text-h4 mb-2">{{ displayOrderNumber }}</h4>
                <span class="text-subtitle-1 text-medium-emphasis mb-0">
                  <VIcon icon="tabler-calendar"></VIcon>
                  {{ orderData.order_date ? formatDate(orderData.order_date) : '-' }}
                </span>
              </div>
              <div class="text-right">
                <!-- Status dengan loading indicator -->
                <div class="d-flex align-center justify-end mb-2">
                  <VProgressCircular 
                    v-if="actionLoading" 
                    indeterminate 
                    size="16" 
                    width="2" 
                    color="primary" 
                    class="mr-2"
                  />
                  <VChip
                    :color="currentStatus.color"
                    variant="elevated"
                    size="large"
                    :class="{ 'opacity-70': actionLoading }"
                  >
                    <VIcon start>{{ currentStatus.icon }}</VIcon>
                    {{ currentStatus.text }}
                  </VChip>
                </div>
                <div class="text-right mt-5">
                  <!-- Subtotal -->
                  <p class="text-subtitle-2">
                    Subtotal: {{ displaySubTotal }}
                  </p>

                  <!-- Pajak -->
                  <p class="text-subtitle-2 text-medium-emphasis">
                    Pajak (10%): {{ displayTax }}
                  </p>

                  <!-- Garis pemisah biar lebih tegas -->
                  <VDivider class="my-2" />

                  <!-- Total Net -->
                  <p class="text-h5 font-weight-bold text-primary">
                    {{ displayTotalNet }}
                  </p>
                </div>
              </div>
            </div>
          </VCardText>
        </VCard>

        <VRow>
          <!-- Customer Information -->
          <VCol cols="12" lg="4">
            <VCard elevation="2" class="h-100">
              <VCardTitle class="bg-primary text-white">
                <VIcon icon="tabler-user" />
                Informasi Pelanggan
              </VCardTitle>
              <VCardText class="pa-6">
                <div class="mb-4">
                  <p class="text-subtitle-2 text-medium-emphasis mb-1">Nama</p>
                  <p class="text-h6">{{ customerName }}</p>
                </div>
                <div class="mb-4">
                  <p class="text-subtitle-2 text-medium-emphasis mb-1">Nomor Telepon</p>
                  <p class="text-body-1">{{ customerPhone }}</p>
                </div>
                <div class="mb-4">
                  <p class="text-subtitle-2 text-medium-emphasis mb-1">Email</p>
                  <p class="text-body-1">{{ customerEmail }}</p>
                </div>
                <div class="mb-4">
                  <p class="text-subtitle-2 text-medium-emphasis mb-1">Meja</p>
                  <VChip color="info" variant="tonal">{{ tableCode }}</VChip>
                </div>
                <div class="mb-4" v-if="orderData.payment_method">
                  <p class="text-subtitle-2 text-medium-emphasis mb-1">Metode Pembayaran</p>
                  <VChip color="info" variant="tonal">{{ orderData.payment_method }}</VChip>
                </div>
                <div v-if="orderData.notes">
                  <p class="text-subtitle-2 text-medium-emphasis mb-1">Catatan</p>
                  <p class="text-body-1">{{ orderData.notes }}</p>
                </div>
              </VCardText>
            </VCard>
          </VCol>

          <!-- Order Items -->
          <VCol cols="12" lg="8">
            <VCard elevation="2" class="order-items-card">
              <VCardTitle class="bg-success text-white">
                <VIcon icon="tabler-list" />
                Item Pesanan ({{ orderItems.length }})
              </VCardTitle>

              <VCardText class="pa-0 scrollable-items">
                <VList v-if="orderItems.length > 0">
                  <VListItem
                    v-for="(item, index) in orderItems"
                    :key="item.id"
                    class="pa-4 item-hover"
                    :class="{ 'border-b': index < orderItems.length - 1 }"
                  >
                    <template #prepend>
                      <VIcon v-if="item.category === 'food'" size="30" icon="tabler-burger" />
                      <VIcon v-else-if="item.category === 'coffee'" size="30" icon="tabler-coffee" />
                      <VIcon v-else-if="item.category === 'drink'" size="30" icon="tabler-cup" />
                      <VIcon v-else size="30" icon="tabler-package" />
                    </template>

                    <VListItemTitle class="text-h6 mb-1">
                      {{ item.menu_name }}
                      <span v-if="item.size && item.size !== 'Regular'" class="text-caption text-medium-emphasis">
                        ({{ item.size }})
                      </span>
                    </VListItemTitle>

                    <VListItemSubtitle class="mb-2">
                      {{ formatCurrency(item.unit_price as any) }} × {{ item.qty }}
                      <span v-if="item.sugar_level && item.sugar_level !== 'Normal'" class="text-caption d-block">
                        Gula: {{ item.sugar_level }}
                      </span>
                      <span v-if="item.notes" class="text-caption d-block text-info">
                        Catatan: {{ item.notes }}
                      </span>
                    </VListItemSubtitle>

                    <template #append>
                      <div class="text-right">
                        <p class="text-h6 font-weight-bold mb-2">
                          {{ formatCurrency(item.total_price as any) }}
                        </p>
                      </div>
                    </template>
                  </VListItem>
                </VList>

                <div v-else class="pa-6 text-center">
                  <VIcon size="48" color="grey-lighten-1" class="mb-4">tabler-package-off</VIcon>
                  <p class="text-body-1 text-medium-emphasis">Tidak ada item dalam pesanan</p>
                </div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>

        <!-- Action Buttons Section - with better loading states -->
        <VCard class="mt-6" elevation="2">
          <VCardTitle class="bg-warning text-white">
            <VIcon icon="tabler-pencil-check"></VIcon>
            Perbarui Status Pesanan
          </VCardTitle>
          <VCardText class="pa-6">
            <div v-if="orderData.status === 'pending'">
              <VRow>
                <VCol cols="12" sm="6">
                  <VBtn 
                    color="error" 
                    block 
                    size="large" 
                    :disabled="actionLoading" 
                    :loading="actionLoading"
                    @click="handleAction('reject')"
                  >
                    <VIcon icon="tabler-x" class="mr-2"></VIcon> 
                    Tolak Pesanan
                  </VBtn>
                </VCol>
                <VCol cols="12" sm="6">
                  <VBtn 
                    color="primary" 
                    block 
                    size="large" 
                    :disabled="actionLoading" 
                    :loading="actionLoading"
                    @click="handleAction('process')"
                  >
                    <VIcon icon="tabler-refresh" class="mr-2"></VIcon> 
                    Proses Pesanan
                  </VBtn>
                </VCol>
              </VRow>
            </div>

            <div v-else-if="orderData.status === 'process'">
              <VBtn 
                color="success" 
                block 
                size="large" 
                :disabled="actionLoading" 
                :loading="actionLoading"
                @click="handleAction('finish')"
              >
                <VIcon icon="tabler-check" class="mr-2"></VIcon> 
                Tandai Selesai
              </VBtn>
            </div>

            <div v-else-if="orderData.status === 'reject'">
              <VAlert type="error" variant="tonal" class="text-center">
                Pesanan ditolak
              </VAlert>
            </div>

            <div v-else-if="orderData.status === 'finished'">
              <VAlert type="success" variant="tonal" class="text-center">
                Pesanan selesai
              </VAlert>
            </div>
          </VCardText>
        </VCard>
      </div>
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

<style scoped>
/* Previous styles remain the same */
.border-b {
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.v-card-title {
  font-weight: 600;
  letter-spacing: 0.5px;
}

.v-list-item {
  transition: background-color 0.2s ease;
}

.v-list-item:hover {
  background-color: rgba(var(--v-theme-primary), 0.04);
}

.v-btn {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.v-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Loading state opacity */
.opacity-70 {
  opacity: 0.7;
}

/* Enhanced button loading states */
.v-btn[loading] {
  pointer-events: none;
}

/* Improved scrollable area */
.scrollable-items {
  max-height: 500px;
  overflow-y: auto;
  position: relative;
}

.scrollable-items::-webkit-scrollbar {
  width: 6px;
}

.scrollable-items::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.05);
  border-radius: 3px;
}

.scrollable-items::-webkit-scrollbar-thumb {
  background: rgba(var(--v-theme-primary), 0.3);
  border-radius: 3px;
}

.scrollable-items::-webkit-scrollbar-thumb:hover {
  background: rgba(var(--v-theme-primary), 0.5);
}

.item-hover {
  transition: all 0.3s ease;
}

.item-hover:hover {
  background-color: rgba(var(--v-theme-primary), 0.04);
  transform: translateX(4px);
  box-shadow: 4px 0 12px rgba(var(--v-theme-primary), 0.1);
}

@media (max-width: 768px) {
  .scrollable-items {
    max-height: 400px;
  }
  
  .item-hover:hover {
    transform: none;
  }
}
</style>
