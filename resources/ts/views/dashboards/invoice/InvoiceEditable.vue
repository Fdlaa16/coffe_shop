<script lang="ts" setup>
import type { InvoiceData } from '@/views/dashboards/invoice/types';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
  data: InvoiceData | null;
  loading?: boolean;
  error?: string | null;
}>()

const emit = defineEmits<{ 
  (e: 'submit', payload: InvoiceData): void;
  (e: 'update:data', payload: InvoiceData): void;
}>()

const submitLoading = ref(false);
const actionLoading = ref(false);
const isFlatSnackbarVisible = ref(false);
const snackbarMessage = ref('');
const snackbarColor = ref<'success' | 'error'>('success');
const exportLoading = ref(false)
const isSnackbarVisible = ref(false)

const statusOptions = [
  { value: 'draft', text: 'Draft', color: 'grey', icon: 'tabler-file-draft' },
  { value: 'sent', text: 'Terkirim', color: 'info', icon: 'tabler-send' },
  { value: 'paid', text: 'Lunas', color: 'success', icon: 'tabler-check' },
  { value: 'overdue', text: 'Terlambat', color: 'error', icon: 'tabler-alert-triangle' },
  { value: 'cancelled', text: 'Dibatalkan', color: 'error', icon: 'tabler-x' },
  { value: 'pending', text: 'Menunggu', color: 'warning', icon: 'tabler-clock' },
  { value: 'process', text: 'Diproses', color: 'info', icon: 'tabler-refresh' },
  { value: 'reject', text: 'Ditolak', color: 'error', icon: 'tabler-x' },
  { value: 'finished', text: 'Selesai', color: 'success', icon: 'tabler-check' },
];

const localData = ref<InvoiceData>({
  id: 0,
  invoice_number: '',
  invoice_date: '',
  expired_date: '',
  order_id: 0,
  customer_id: 0,
  table_id: 0,
  subtotal: 0,
  tax: 0,
  total_net: 0,
  status: 'draft',
  invoice_items: [],
});

watch(
  () => props.data,
  (newData) => {
    if (newData) {
      localData.value = JSON.parse(JSON.stringify(newData)); 
    }
  },
  { immediate: true, deep: true }
);

const invoiceData = computed(() => props.data);
const orderData = computed(() => props.data); 

const displayOrderNumber = computed(() => {
  return invoiceData.value?.invoice_number || '-';
});

const displaySubTotal = computed(() => {
  return formatCurrency(invoiceData.value?.subtotal || 0);
});

const displayTax = computed(() => {
  return formatCurrency(invoiceData.value?.tax || 0);
});

const displayTotalNet = computed(() => {
  return formatCurrency(invoiceData.value?.total_net || 0);
});

const currentStatus = computed(() => {
  return getStatusConfig(invoiceData.value?.status || 'draft');
});

const customerName = computed(() => {
  return invoiceData.value?.customer?.name || '-';
});

const customerPhone = computed(() => {
  return invoiceData.value?.customer?.phone || '-';
});

const customerEmail = computed(() => {
  return invoiceData.value?.customer?.user?.email || '-';
});

const tableCode = computed(() => {
  return invoiceData.value?.table?.code || 'N/A';
});

const orderItems = computed(() => {
  return invoiceData.value?.invoice_items || [];
});

const formatCurrency = (amount: number | string | null) => {
  const value = Number(amount ?? 0);
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(isNaN(value) ? 0 : value);
};

const formatDate = (dateString: string) => {
  if (!dateString) return '-';
  const d = new Date(dateString);
  if (isNaN(d.getTime())) return dateString;
  return d.toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

const getStatusConfig = (status: string) => {
  return statusOptions.find(s => s.value === status) ?? statusOptions[0];
};

// Update functions
const updateData = () => {
  emit('update:data', localData.value);
};

const handleDownloadReceipt = async () => {
  exportLoading.value = true
  
  try {
    const response = await $api(`invoice/${localData.value.id}/download-receipt`, {
      method: 'GET',
      responseType: 'blob',
    })

    const blob = new Blob([response], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    
    const timestamp = new Date().toISOString().slice(0, 19).replace(/[:.]/g, '-')
    link.download = `receipt_${timestamp}.pdf`
    
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)

    snackbarMessage.value = 'Receipt berhasil diunduh'
    snackbarColor.value = 'success'
    isSnackbarVisible.value = true

  } catch (err: any) {
    console.error('Export error:', err)
    snackbarMessage.value = err?.response?.data?.message || 'Gagal mengunduh receipt'
    snackbarColor.value = 'error'
    isSnackbarVisible.value = true
  } finally {
    exportLoading.value = false
  }
}

const handleSubmit = () => {
  submitLoading.value = true;
  
  // Emit submit event
  emit('submit', localData.value);
  
  // Reset loading after a delay (will be handled by parent)
  setTimeout(() => {
    submitLoading.value = false;
  }, 1000);
};
</script>

<template>
  <VRow>
    <VCol cols="12">
      <!-- Loading State -->
      <div v-if="loading" class="d-flex justify-center align-center" style="height: 400px;">
        <div class="text-center">
          <VProgressCircular indeterminate color="primary" size="60" width="6" />
          <p class="mt-4 text-h6">Memuat data invoice...</p>
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
      <div v-else-if="invoiceData">
        <!-- Header Card with real-time status -->
        <VCard class="mb-6" elevation="2">
          <VCardText>
            <div class="d-flex justify-space-between align-center flex-wrap">
              <div>
                <h4 class="text-h4 mb-2">{{ displayOrderNumber }}</h4>
                <span class="text-subtitle-1 text-medium-emphasis mb-0">
                  <VIcon icon="tabler-calendar"></VIcon>
                  {{ invoiceData.invoice_date ? formatDate(invoiceData.invoice_date) : '-' }}
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
                <div class="mb-4" v-if="invoiceData.payment_method">
                  <p class="text-subtitle-2 text-medium-emphasis mb-1">Metode Pembayaran</p>
                  <VChip color="info" variant="tonal">{{ invoiceData.payment_method }}</VChip>
                </div>
                <div v-if="invoiceData.notes">
                  <p class="text-subtitle-2 text-medium-emphasis mb-1">Catatan</p>
                  <p class="text-body-1">{{ invoiceData.notes }}</p>
                </div>
              </VCardText>
            </VCard>
          </VCol>

          <!-- Invoice Items -->
          <VCol cols="12" lg="8">
            <VCard elevation="2" class="order-items-card">
              <VCardTitle class="bg-success text-white">
                <VIcon icon="tabler-list" />
                Item Invoice ({{ orderItems.length }})
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
                      {{ formatCurrency(item.unit_price) }} × {{ item.qty }}
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
                          {{ formatCurrency(item.total_price || item.total) }}
                        </p>
                      </div>
                    </template>
                  </VListItem>
                </VList>

                <div v-else class="pa-6 text-center">
                  <VIcon size="48" color="grey-lighten-1" class="mb-4">tabler-package-off</VIcon>
                  <p class="text-body-1 text-medium-emphasis">Tidak ada item dalam invoice</p>
                </div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>

        <!-- Action Buttons Section -->
        <VCard class="mt-6" elevation="2">
          <VCardTitle class="bg-warning text-white">
            <VIcon icon="tabler-pencil-check"></VIcon>
            Perbarui Status Invoice
          </VCardTitle>
          <VCardText class="pa-6">
            <div>
              <VBtn 
                color="primary" 
                block 
                size="large" 
                :disabled="submitLoading" 
                :loading="submitLoading"
                @click="handleDownloadReceipt()"
              >
                <VIcon icon="tabler-download" class="mr-2"></VIcon> 
                Unduh Struk
              </VBtn>
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

.opacity-70 {
  opacity: 0.7;
}

.v-btn[loading] {
  pointer-events: none;
}

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
