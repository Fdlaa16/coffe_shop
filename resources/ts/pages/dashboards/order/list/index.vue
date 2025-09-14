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

const orders = ref<any[]>([])
const clubs = ref<{ title: string; value: string | number }[]>([])
const sports = ref<{ title: string; value: string | number }[]>([])

const loading = ref(false)
const error = ref<string | null>(null)
const currentPage = ref(1)

const itemsPerPage = 5 
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref<'success' | 'warning' | 'error'>('success')
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
  return Math.ceil(orders.value.length / itemsPerPage)
})

const widgetData = ref([
  { title: 'All', value: 0, icon: 'tabler-receipt-2' },
  { title: 'Active', value: 0, icon: 'tabler-receipt-2' },
  { title: 'Non Active', value: 0, icon: 'tabler-receipt-2' },
])

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Kode', key: 'code' },
  { title: 'Meja', key: 'table.id' },
  { title: 'Tanggal Pesanan', key: 'order_date' },
  { title: 'Status', key: 'status' },
  { title: 'Aksi', key: 'action', sortable: false },
]

const paginatedOrders = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return orders.value.slice(start, start + itemsPerPage)
})

async function fetchOrder() {
  loading.value = true
  error.value = null

  try {
    const response = await $api('order', {
      method: 'GET',
      params: {
        search: searchQuery.value,
        status: selectedStatus.value,
        sort: selectedSort.value,
      },
    })

    orders.value = response.data 
    const totals = response.totals

    widgetData.value = [
      { title: 'All', value: totals.all, icon: 'tabler-receipt-2', iconColor: 'primary', change: 0, desc: 'Total semua pesanan' },
      { title: 'Active', value: totals.active, icon: 'tabler-receipt-2', iconColor: 'success', change: 0, desc: 'Pesanan aktif' },
      { title: 'Non Active', value: totals.in_active, icon: 'tabler-receipt-2', iconColor: 'error', change: 0, desc: 'Pesanan tidak aktif' },
    ]

  } catch (err: any) {
    error.value = err.message || 'Gagal memuat data'
  } finally {
    loading.value = false
  }
}

function editOrder(order: any) {
  router.push({ name: 'dashboards-order-edit-id', params: { id: order.id } })
}

async function processOrder(order: any) {
  const confirm = await Swal.fire({
    title: 'Memproses pesanan?',
    text: `Pesanan ${order.name} akan diproses?.`,
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
      loading.value = true

      await $api(`order/${order.id}/process`, {
        method: 'PUT',
      })

      await fetchOrder()

      snackbarMessage.value = 'Pesanan sedang diproses'
      snackbarColor.value = 'warning'
      isSnackbarVisible.value = true
    } catch (err: any) {
      snackbarMessage.value = err?.response?.data?.message || 'Gagal mengaktifkan proses pesanan'
      snackbarColor.value = 'error'
      isSnackbarVisible.value = true
    } finally {
      loading.value = false
    }
  }
}

async function finishedOrder(order: any) {
  const confirm = await Swal.fire({
    title: 'Pesanan Selesai Diproses?',
    text: `Apakah pesanan sudah selesai diproses?.`,
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
      loading.value = true

      await $api(`order/${order.id}/finished`, {
        method: 'PUT',
      })

      await fetchOrder()

      snackbarMessage.value = 'Pesanan Selesai Diproses'
      snackbarColor.value = 'success'
      isSnackbarVisible.value = true
    } catch (err: any) {
      snackbarMessage.value = err?.response?.data?.message || 'Gagal mengaktifkan selesai proses menu pesanan'
      snackbarColor.value = 'error'
      isSnackbarVisible.value = true
    } finally {
      loading.value = false
    }
  }
}

async function rejectOrder(order: any) {
  const result = await Swal.fire({
    title: 'Tolak pesanan?',
    text: `Pesanan ${order.name} akan ditolak?.`,
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
    $api(`order/${order.id}/reject`, {
      method: 'PUT',
    })
      .then(() => {
        fetchOrder()
        snackbarMessage.value = 'Pesanan ditolak'
        snackbarColor.value = 'success'
        isSnackbarVisible.value = true
      })
      .catch((err: any) => {
        snackbarMessage.value = err?.response?.data?.message || 'Gagal menolak pesanan'
        snackbarColor.value = 'error'
        isSnackbarVisible.value = true
      })
  }
}

async function deleteOrder(order: any) {
  const confirm = await Swal.fire({
    title: 'Apakah kamu yakin?',
    text: `Data pesanan dengan nama ${order.name} akan dihapus.`,
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

      await $api(`order/${order.id}`, {
        method: 'DELETE',
      })

      await fetchOrder()

      snackbarMessage.value = 'Pesanan berhasil dihapus'
      snackbarColor.value = 'success'
      isSnackbarVisible.value = true
    } catch (err: any) {
      snackbarMessage.value = err?.response?.data?.message || 'Gagal menghapus pesanan'
      snackbarColor.value = 'error'
      isSnackbarVisible.value = true
    } finally {
      loading.value = false
    }
  }
}

async function activateOrder(order: any) {
  const confirm = await Swal.fire({
    title: 'Aktifkan pesanan?',
    text: `Pesanan ${order.name} akan diaktifkan kembali.`,
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

      await $api(`order/${order.id}/active`, {
        method: 'PUT',
      })

      await fetchOrder()

      snackbarMessage.value = 'Pesanan berhasil diaktifkan kembali'
      snackbarColor.value = 'success'
      isSnackbarVisible.value = true
    } catch (err: any) {
      snackbarMessage.value = err?.response?.data?.message || 'Gagal mengaktifkan pesanan'
      snackbarColor.value = 'error'
      isSnackbarVisible.value = true
    } finally {
      loading.value = false
    }
  }
}

function getQueryParam(param: LocationQueryValue | LocationQueryValue[] | undefined): string {
  return Array.isArray(param) ? param[0] || '' : param || ''
}

const exportOrders = async () => {
  exportLoading.value = true
  
  try {
    const response = await $api(`order/export`, {
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
    link.download = `Orders_Export_${timestamp}.xlsx`
    
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

  fetchOrder()
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

  fetchOrder()
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
          <VCardTitle>Pesanan</VCardTitle>
        </VCardItem>

        <VCardText>
          <VRow>
            <VCol
              cols="12"
              sm="3"
            >
             <AppTextField
                v-model="searchQuery"
                placeholder="Search order"
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
                @click="exportOrders()"
              >
                Ekspor
              </VBtn>
            </div>
          </VRow>
        </VCardText>

        <VDivider />

        <VDataTable
          :headers="headers"
          :items="paginatedOrders"
          :loading="loading"
          class="text-no-wrap"
          :items-per-page="itemsPerPage"
          hide-default-footer
        >
          <template #item.id="{ item }">
            <div class="text-body-1">{{ item.id }}</div>
          </template>

          <template #item.invoice_number="{ item }">
            <div class="text-body-1">{{ item.invoice_number }}</div>
          </template>

          <template #item.table_id="{ item }">
            <div class="text-body-1">{{ item.table_id }}</div>
          </template>

          <template #item.status="{ item }">
            <div class="text-body-1">{{ item.status }}</div>
          </template>

          <template #item.action="{ item }">
            <div class="d-flex gap-x-2">
              <VBtn
                v-if="!item.deleted_at"
                icon
                size="small"
                color="primary"
                @click="editOrder(item)"
                title="Ubah"
              >
                <VIcon icon="tabler-pencil" />
              </VBtn>

              <VBtn
                v-if="!item.deleted_at && item.status == 'pending'"
                icon
                size="small"
                color="warning"
                @click="processOrder(item)"
                title="Proses"
              >
                <VIcon icon="tabler-progress-check" />
              </VBtn>

              <VBtn
                v-if="!item.deleted_at && item.status == 'process'"
                icon
                size="small"
                color="success"
                @click="finishedOrder(item)"
                title="Selesai"
              >
                <VIcon icon="tabler-thumb-up" />
              </VBtn>

              <VBtn  
                v-if="!item.deleted_at && item.status == 'pending'"
                icon
                size="small"
                color="error"
                @click="rejectOrder(item)"
                title="Tolak"
              >
                <VIcon icon="tabler-x" />
              </VBtn>

              <VBtn  
                v-if="!item.deleted_at && item.status != 'process'"
                icon
                size="small"
                color="error"
                @click="deleteOrder(item)"
                title="Hapus"
              >
                <VIcon icon="tabler-trash" />
              </VBtn>
              
              <VBtn
                v-if="item.deleted_at"
                icon
                size="small"
                color="success"
                @click="activateOrder(item)"
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
