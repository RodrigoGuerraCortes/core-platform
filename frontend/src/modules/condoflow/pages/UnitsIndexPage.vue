<script setup lang="ts">
import { ref, computed } from 'vue'
import { useUnitsQuery } from '../composables'
import { AppPageLayout, AppButton, AppStatusChip } from '@/shared/ui'
import { AppDataTable } from '@/shared/table'
import type { TableQueryParams } from '@/shared/table'

const unitStatusMap: Record<string, { status?: string; label?: string; color?: string }> = {
  occupied: { status: 'active' },
  vacant: { status: 'inactive' },
  available: { label: 'Available', color: 'success' },
  maintenance: { label: 'Maintenance', color: 'warning' },
}

const queryParams = ref<TableQueryParams>({ page: 1, per_page: 15 })
const statusFilter = ref<string>('')

const { data, isLoading, isError } = useUnitsQuery(
  computed(() => ({ ...queryParams.value, status: statusFilter.value || undefined })),
)

const items = computed(() => data.value?.data ?? [])
const meta = computed(() => data.value?.meta)

const columns = [
  { key: 'number', label: 'Número', sortable: true },
  { key: 'floor', label: 'Piso', sortable: true },
  { key: 'type', label: 'Tipo' },
  { key: 'status', label: 'Estado' },
  { key: 'building.name', label: 'Edificio' },
]

const statusOptions = [
  { title: 'Todos', value: '' },
  { title: 'Disponible', value: 'available' },
  { title: 'Ocupado', value: 'occupied' },
  { title: 'Mantenimiento', value: 'maintenance' },
]

function onPageChange(page: number) {
  queryParams.value = { ...queryParams.value, page }
}

function onSearch(search: string) {
  queryParams.value = { ...queryParams.value, page: 1, search }
}
</script>

<template>
  <AppPageLayout title="Unidades" subtitle="Gestión de unidades del condominio">
    <template #actions>
      <AppButton color="primary" prepend-icon="mdi-plus">
        Nueva Unidad
      </AppButton>
    </template>

    <v-row class="mb-4">
      <v-col cols="12" sm="4">
        <v-select
          v-model="statusFilter"
          :items="statusOptions"
          label="Estado"
          density="compact"
          variant="outlined"
          hide-details
        />
      </v-col>
    </v-row>

    <AppDataTable
      :items="items"
      :columns="columns"
      :loading="isLoading"
      :error="isError"
      :total="meta?.total ?? 0"
      :page="meta?.current_page ?? 1"
      :per-page="meta?.per_page ?? 15"
      searchable
      @update:page="onPageChange"
      @update:search="onSearch"
    >
      <template #col-status="{ item }">
        <AppStatusChip
          v-bind="unitStatusMap[item.status] ?? { label: item.status, color: 'default' }"
        />
      </template>
      <template #[`col-building.name`]="{ item }">
        {{ item.building?.name ?? '—' }}
      </template>
    </AppDataTable>
  </AppPageLayout>
</template>
