<script setup lang="ts">
import Swal from 'sweetalert2'
import { computed, onMounted, ref } from 'vue'
import type { LocationQueryValue } from 'vue-router'
import { useRouter } from 'vue-router'

const router = useRouter()
const route = useRoute()

const searchQuery = ref('')
const selectedClub = ref('')
const selectedSport = ref('')
const selectedStatus = ref('')
const selectedSort = ref('')

const invoices = ref<any[]>([])
const clubs = ref<{ title: string; value: string | number }[]>([])
const sports = ref<{ title: string; value: string | number }[]>([])

const loading = ref(false)
const error = ref<string | null>(null)
const currentPage = ref(1)

const itemsPerPage = 5 
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref<'success' | 'error'>('success')
const exportLoading = ref(false)

onMounted(() => {
  if (route.query.success) {
    snackbarMessage.value = String(route.query.success)
    snackbarColor.value = 'success'
    isSnackbarVisible.value = true

    router.replace({ query: {} })
  }
})

const totalPages = computed(() => {
  return Math.ceil(invoices.value.length / itemsPerPage)
})

const widgetData = ref([
  { title: 'All', value: 0, icon: 'tabler-border-all' },
  { title: 'Active', value: 0, icon: 'tabler-border-all' },
  { title: 'Non Active', value: 0, icon: 'tabler-border-all' },
])

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Nomor Invoice', key: 'invoice_number' },
  { title: 'Tanggal Invoice', key: 'invoice_date' },
  { title: 'Status', key: 'status' },
  { title: 'Aksi', key: 'action', invoice: false },
]

const paginatedInvoices = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return invoices.value.slice(start, start + itemsPerPage)
})

async function fetchInvoice() {
  loading.value = true
  error.value = null

  try {
    const response = await $api('invoice', {
      method: 'GET',
      params: {
        search: searchQuery.value,
        status: selectedStatus.value,
        sort: selectedSort.value,
      },
    })

    invoices.value = response.data 
    const totals = response.totals

    widgetData.value = [
      { title: 'All', value: totals.all, icon: 'tabler-border-all', iconColor: 'primary', change: 0, desc: 'Total semua club' },
      { title: 'Active', value: totals.active, icon: 'tabler-border-all', iconColor: 'success', change: 0, desc: 'Club aktif' },
      { title: 'Non Active', value: totals.in_active, icon: 'tabler-border-all', iconColor: 'error', change: 0, desc: 'Club tidak aktif' },
    ]

  } catch (err: any) {
    error.value = err.message || 'Gagal memuat data'
  } finally {
    loading.value = false
  }
}

function editInvoice(invoice: any) {
  router.push({ name: 'dashboards-invoice-edit-id', params: { id: invoice.id } })
}

const downloadReceipt = (item) => {
  const link = document.createElement('a')
  link.href = item.qr_code
  link.download = `qr-invoice-${item.id}.png`
  link.click()
}

async function deleteInvoice(invoice: any) {
  const confirm = await Swal.fire({
    title: 'Apakah kamu yakin?',
    text: `Data invoice dengan nama ${invoice.name} akan dihapus.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, hapus!',
    cancelButtonText: 'Batal',
    customClass: {
      confirmButton: 'swal2-confirm-btn',
      cancelButton: 'swal2-cancel-btn',
    },
  })

  if (confirm.isConfirmed) {
    try {
      loading.value = true

      await $api(`invoice/${invoice.id}`, {
        method: 'DELETE',
      })

      await fetchInvoice()

      snackbarMessage.value = 'Invoice berhasil dihapus'
      snackbarColor.value = 'success'
      isSnackbarVisible.value = true
    } catch (err: any) {
      snackbarMessage.value = err?.response?.data?.message || 'Gagal menghapus invoice'
      snackbarColor.value = 'error'
      isSnackbarVisible.value = true
    } finally {
      loading.value = false
    }
  }
}

async function activateInvoice(invoice: any) {
  const confirm = await Swal.fire({
    title: 'Aktifkan Invoice?',
    text: `Invoice ${invoice.name} akan diaktifkan kembali.`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, aktifkan!',
    cancelButtonText: 'Batal',
    customClass: {
      confirmButton: 'swal2-confirm-btn',
      cancelButton: 'swal2-cancel-btn',
    },
  })

  if (confirm.isConfirmed) {
    try {
      loading.value = true

      await $api(`invoice/${invoice.id}/active`, {
        method: 'PUT',
      })

      await fetchInvoice()

      snackbarMessage.value = 'Invoice berhasil diaktifkan kembali'
      snackbarColor.value = 'success'
      isSnackbarVisible.value = true
    } catch (err: any) {
      snackbarMessage.value = err?.response?.data?.message || 'Gagal mengaktifkan invoice'
      snackbarColor.value = 'error'
      isSnackbarVisible.value = true
    } finally {
      loading.value = false
    }
  }
}

async function approveInvoice(invoice: any) {
  const result = await Swal.fire({
    title: 'Terima invoice?',
    text: `Invoice ${invoice.name} akan diterima?.`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, terima!',
    cancelButtonText: 'Batal',
    customClass: {
      confirmButton: 'swal2-confirm-btn',
      cancelButton: 'swal2-cancel-btn',
    },
  })

  if (result.isConfirmed) {
    $api(`invoice/${invoice.id}/approve`, {
      method: 'PUT',
    })
      .then(() => {
        fetchInvoice()
        snackbarMessage.value = 'Invoice diterima'
        snackbarColor.value = 'success'
        isSnackbarVisible.value = true
      })
      .catch((err: any) => {
        snackbarMessage.value = err?.response?.data?.message || 'Gagal menerima invoice'
        snackbarColor.value = 'error'
        isSnackbarVisible.value = true
      })
  }
}

async function rejectInvoice(invoice: any) {
  const result = await Swal.fire({
    title: 'Tolak invoice?',
    text: `Invoice ${invoice.name} akan ditolak?.`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, tolak!',
    cancelButtonText: 'Batal',
    customClass: {
      confirmButton: 'swal2-confirm-btn',
      cancelButton: 'swal2-cancel-btn',
    },
  })

  if (result.isConfirmed) {
    $api(`invoice/${invoice.id}/reject`, {
      method: 'PUT',
    })
      .then(() => {
        fetchInvoice()
        snackbarMessage.value = 'Invoice ditolak'
        snackbarColor.value = 'success'
        isSnackbarVisible.value = true
      })
      .catch((err: any) => {
        snackbarMessage.value = err?.response?.data?.message || 'Gagal menolak invoice'
        snackbarColor.value = 'error'
        isSnackbarVisible.value = true
      })
  }
}

const handleDownloadReceipt = async (customer: any) => {
  exportLoading.value = true
  
  try {
    const response = await $api(`invoice/${customer}/download-receipt`, {
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

function getQueryParam(param: LocationQueryValue | LocationQueryValue[] | undefined): string {
  return Array.isArray(param) ? param[0] || '' : param || ''
}

const exportInvoices = async () => {
  exportLoading.value = true
  
  try {
    const response = await $api(`invoice/export`, {
      method: 'POST',
      params: {
        format: 'xlsx',
        search: searchQuery.value,
        status: selectedStatus.value,
        sort: selectedSort.value,
      },
      responseType: 'blob',
    })

    const blob = new Blob([response], { 
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
    })
    
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    
    const timestamp = new Date().toISOString().slice(0, 19).replace(/[:.]/g, '-')
    link.download = `Invoices_Export_${timestamp}.xlsx`
    
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    window.URL.revokeObjectURL(url)

    snackbarMessage.value = 'Data berhasil diekspor'
    snackbarColor.value = 'success'
    isSnackbarVisible.value = true

  } catch (err: any) {
    console.error('Export error:', err)
    snackbarMessage.value = err?.response?.data?.message || 'Gagal mengekspor data'
    snackbarColor.value = 'error'
    isSnackbarVisible.value = true
  } finally {
    exportLoading.value = false
  }
}

onMounted(() => {
  searchQuery.value = getQueryParam(route.query.search)
  selectedStatus.value = getQueryParam(route.query.status)
  selectedSort.value = getQueryParam(route.query.sort)

  fetchInvoice()
})

watch([searchQuery, selectedStatus, selectedSort], () => {
  router.replace({
    query: {
      ...route.query,
      search: searchQuery.value || undefined,
      status: selectedStatus.value || undefined,
      sort: selectedSort.value || undefined,
    },
  })

  fetchInvoice()
})
</script>

<template>
  <VRow>
    <VCol cols="12">
      <div class="d-flex mb-6">
        <VRow>
          <template
            v-for="(data, id) in widgetData"
            :key="id"
          >
            <VCol
              cols="12"
              md="4"
              sm="6"
            >
              <VCard>
                <VCardText>
                  <div class="d-flex justify-space-between">
                    <div class="d-flex flex-column gap-y-1">
                      <div class="text-body-1 text-high-emphasis">
                        {{ data.title }}
                      </div>
                      <div class="d-flex gap-x-2 align-center">
                        <h4 class="text-h4">
                          {{ data.value }}
                        </h4>
                      </div>
                      <div class="text-sm">
                        {{ data.desc }}
                      </div>
                    </div>
                    <VAvatar
                      :color="data.iconColor"
                      variant="tonal"
                      rounded
                      size="42"
                    >
                      <VIcon
                        :icon="data.icon"
                        size="26"
                      />
                    </VAvatar>
                  </div>
                </VCardText>
              </VCard>
            </VCol>
          </template>
        </VRow>
      </div>

      <VCard class="mb-6">
        <VCardItem class="pb-4">
          <VCardTitle>Invoices</VCardTitle>
        </VCardItem>

        <VCardText>
          <VRow>
            <VCol
              cols="12"
              sm="3"
            >
             <AppTextField
                v-model="searchQuery"
                placeholder="Search invoice"
              />
            </VCol>

            <VCol
              cols="12"
              sm="3"
            >
              <AppSelect
                v-model="selectedStatus"
                placeholder="Status"
                clearable
                clear-icon="tabler-x"
                single-line
                :items="[
                  { title: 'Pilih Status', value: '' },
                  { title: 'Semua', value: 'all' },
                  { title: 'Aktif', value: 'active' },
                  { title: 'Tidak Aktif', value: 'in_active' }
                ]"
              />
            </VCol>
            
            <VCol
              cols="12"
              sm="3"
            >
              <AppSelect
                v-model="selectedSort"
                placeholder="Z-A"
                clearable
                clear-icon="tabler-x"
                single-line
                :items="[
                  { title: 'Pilih Sortir', value: '' },
                  { title: 'A-Z', value: 'asc' },
                  { title: 'Z-A', value: 'desc' },
                ]"
              />
            </VCol>

            <VSpacer />

          <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
            <VBtn
              color="warning"
              prepend-icon="tabler-upload"
              @click="exportInvoices()"
            >
              Ekspor
            </VBtn>
          </div>
          </VRow>
        </VCardText>

        <VDivider />

        <VDataTable
          :headers="headers"
          :items="paginatedInvoices"
          :loading="loading"
          class="text-no-wrap"
          :items-per-page="itemsPerPage"
          hide-default-footer
        >
          <template #item.id="{ item }">
            <div class="text-body-1">{{ item.id }}</div>
          </template>

          <template v-slot:item.qr_code="{ item }">
            <img :src="item.qr_code" alt="QR Code" class="w-10 h-10" />
          </template>

          <template #item.code="{ item }">
            <div class="text-body-1">{{ item.code }}</div>
          </template>

          <template #item.name="{ item }">
            <div class="text-body-1">{{ item.name }}</div>
          </template>

          <template #item.action="{ item }">
            <div class="d-flex gap-x-2">
              <VBtn
                v-if="!item.deleted_at"
                icon
                size="small"
                color="primary"
                @click="editInvoice(item)"
                title="Lihat"
              >
                <VIcon icon="tabler-eye" />
              </VBtn>

              <VBtn
                icon
                size="small"
                color="primary"
                @click="handleDownloadReceipt(item.id)"
                title="Unduh Struk"
              >
                <VIcon icon="tabler-download" />
              </VBtn>

              <VBtn  
                v-if="!item.deleted_at"
                icon
                size="small"
                color="error"
                @click="deleteInvoice(item)"
                title="Hapus"
              >
                <VIcon icon="tabler-trash" />
              </VBtn>
              
              <VBtn
                v-if="item.deleted_at"
                icon
                size="small"
                color="success"
                @click="activateInvoice(item)"
                title="Aktifkan"
              >
                <VIcon icon="tabler-check" />
              </VBtn>
            </div>
          </template>
        </VDataTable>

        <!-- Pagination -->
        <div class="d-flex justify-end mt-4 mb-3 mr-2">
          <VPagination
            v-model="currentPage"
            :length="totalPages"
            total-visible="5"
            color="primary"
          />
        </div>
      </VCard>
    </VCol>
  </VRow>

  <VSnackbar
    v-model="isSnackbarVisible"
    :color="snackbarColor"
    location="bottom start"
    variant="flat"
    timeout="3000"
  >
    {{ snackbarMessage }}
  </VSnackbar>

</template>

<style lang="scss">
.swal2-confirm-btn {
  background-color: #7B68EE !important;
  color: #ffffff !important;
  border: none;
  padding: 0.625rem 1.25rem;
  border-radius: 0.375rem;
}

.swal2-cancel-btn {
  background-color: #6c757d !important;
  color: #ffffff !important;
  border: none;
  padding: 0.625rem 1.25rem;
  border-radius: 0.375rem;
}
</style>
