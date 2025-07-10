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

const drinks = ref<any[]>([])
const clubs = ref<{ title: string; value: string | number }[]>([])
const sports = ref<{ title: string; value: string | number }[]>([])

const loading = ref(false)
const error = ref<string | null>(null)
const currentPage = ref(1)

const itemsPerPage = 5 
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref<'success' | 'error'>('success')

onMounted(() => {
  if (route.query.success) {
    snackbarMessage.value = String(route.query.success)
    snackbarColor.value = 'success'
    isSnackbarVisible.value = true

    router.replace({ query: {} })
  }
})

const totalPages = computed(() => {
  return Math.ceil(drinks.value.length / itemsPerPage)
})

const widgetData = ref([
  { title: 'All', value: 0, icon: 'tabler-cup' },
  { title: 'Active', value: 0, icon: 'tabler-cup' },
  { title: 'Non Active', value: 0, icon: 'tabler-cup' },
])

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Code', key: 'code' },
  { title: 'Name', key: 'name' },
  { title: 'Qty', key: 'qty' },
  { title: 'Price', key: 'price' },
  { title: 'Action', key: 'action', sortable: false },
]

const paginatedDrinks = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return drinks.value.slice(start, start + itemsPerPage)
})

async function fetchDrink() {
  loading.value = true
  error.value = null

  try {
    const response = await $api('drink', {
      method: 'GET',
      params: {
        search: searchQuery.value,
        status: selectedStatus.value,
        sort: selectedSort.value,
      },
    })

    drinks.value = response.data 
    const totals = response.totals

    widgetData.value = [
      { title: 'All', value: totals.all, icon: 'tabler-cup', iconColor: 'primary', change: 0, desc: 'Total semua club' },
      { title: 'Active', value: totals.active, icon: 'tabler-cup', iconColor: 'success', change: 0, desc: 'Club aktif' },
      { title: 'Non Active', value: totals.in_active, icon: 'tabler-cup', iconColor: 'error', change: 0, desc: 'Club tidak aktif' },
    ]

  } catch (err: any) {
    error.value = err.message || 'Gagal memuat data'
  } finally {
    loading.value = false
  }
}

function editDrink(drink: any) {
  router.push({ name: 'dashboards-drink-edit-id', params: { id: drink.id } })
}

async function deleteDrink(drink: any) {
  const confirm = await Swal.fire({
    title: 'Apakah kamu yakin?',
    text: `Data drink dengan nama ${drink.name} akan dihapus.`,
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

      await $api(`drink/${drink.id}`, {
        method: 'DELETE',
      })

      await fetchDrink()

      snackbarMessage.value = 'Drink berhasil dihapus'
      snackbarColor.value = 'success'
      isSnackbarVisible.value = true
    } catch (err: any) {
      snackbarMessage.value = err?.response?.data?.message || 'Gagal menghapus drink'
      snackbarColor.value = 'error'
      isSnackbarVisible.value = true
    } finally {
      loading.value = false
    }
  }
}

async function activateDrink(drink: any) {
  const confirm = await Swal.fire({
    title: 'Aktifkan drink?',
    text: `Drink ${drink.name} akan diaktifkan kembali.`,
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

      await $api(`drink/${drink.id}/active`, {
        method: 'PUT',
      })

      await fetchDrink()

      snackbarMessage.value = 'Drink berhasil diaktifkan kembali'
      snackbarColor.value = 'success'
      isSnackbarVisible.value = true
    } catch (err: any) {
      snackbarMessage.value = err?.response?.data?.message || 'Gagal mengaktifkan drink'
      snackbarColor.value = 'error'
      isSnackbarVisible.value = true
    } finally {
      loading.value = false
    }
  }
}

async function approveDrink(drink: any) {
  const result = await Swal.fire({
    title: 'Terima drink?',
    text: `Drink ${drink.name} akan diterima?.`,
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
    $api(`drink/${drink.id}/approve`, {
      method: 'PUT',
    })
      .then(() => {
        fetchDrink()
        snackbarMessage.value = 'Drink diterima'
        snackbarColor.value = 'success'
        isSnackbarVisible.value = true
      })
      .catch((err: any) => {
        snackbarMessage.value = err?.response?.data?.message || 'Gagal menerima drink'
        snackbarColor.value = 'error'
        isSnackbarVisible.value = true
      })
  }
}

async function rejectDrink(drink: any) {
  const result = await Swal.fire({
    title: 'Tolak drink?',
    text: `Drink ${drink.name} akan ditolak?.`,
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
    $api(`drink/${drink.id}/reject`, {
      method: 'PUT',
    })
      .then(() => {
        fetchDrink()
        snackbarMessage.value = 'Drink ditolak'
        snackbarColor.value = 'success'
        isSnackbarVisible.value = true
      })
      .catch((err: any) => {
        snackbarMessage.value = err?.response?.data?.message || 'Gagal menolak drink'
        snackbarColor.value = 'error'
        isSnackbarVisible.value = true
      })
  }
}


function getQueryParam(param: LocationQueryValue | LocationQueryValue[] | undefined): string {
  return Array.isArray(param) ? param[0] || '' : param || ''
}

onMounted(() => {
  searchQuery.value = getQueryParam(route.query.search)
  selectedStatus.value = getQueryParam(route.query.status)
  selectedSort.value = getQueryParam(route.query.sort)

  fetchDrink()
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

  fetchDrink()
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
          <VCardTitle>Drinks</VCardTitle>
        </VCardItem>

        <VCardText>
          <VRow>
            <VCol
              cols="12"
              sm="3"
            >
             <AppTextField
                v-model="searchQuery"
                placeholder="Search drink"
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
                  { title: 'Pilih Sort', value: '' },
                  { title: 'A-Z', value: 'asc' },
                  { title: 'Z-A', value: 'desc' },
                ]"
              />
            </VCol>

            <VSpacer />

          <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
            <VBtn
              prepend-icon="tabler-plus"
              :to="{ name: 'dashboards-drink-add' }"
            >
              Add New drink
            </VBtn>
          </div>
          </VRow>
        </VCardText>

        <VDivider />

        <VDataTable
          :headers="headers"
          :items="paginatedDrinks"
          :loading="loading"
          class="text-no-wrap"
          :items-per-page="itemsPerPage"
          hide-default-footer
        >
          <template #item.id="{ item }">
            <div class="text-body-1">{{ item.id }}</div>
          </template>

          <template #item.code="{ item }">
            <div class="text-body-1">{{ item.code }}</div>
          </template>

          <template #item.name="{ item }">
            <div class="text-body-1">{{ item.name }}</div>
          </template>

          <template #item.qty="{ item }">
            <div class="text-body-1">{{ item.qty }}</div>
          </template>

          <template #item.price="{ item }">
            <div class="text-body-1">{{ item.price }}</div>
          </template>

          <template #item.action="{ item }">
            <div class="d-flex gap-x-2">
              <VBtn
                v-if="!item.deleted_at"
                icon
                size="small"
                color="primary"
                @click="editDrink(item)"
                title="Edit"
              >
                <VIcon icon="tabler-pencil" />
              </VBtn>

              <VBtn  
                v-if="!item.deleted_at"
                icon
                size="small"
                color="error"
                @click="deleteDrink(item)"
                title="Delete"
              >
                <VIcon icon="tabler-trash" />
              </VBtn>
              
              <VBtn
                v-if="item.deleted_at"
                icon
                size="small"
                color="success"
                @click="activateDrink(item)"
                title="Activate"
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
