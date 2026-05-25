<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useTicketsQuery } from '../composables'
import { AppPageLayout, AppButton, AppStatusChip } from '@/shared/ui'
import { AppDataTable } from '@/shared/table'
import type { TableQueryParams } from '@/shared/table'

const router = useRouter()
const queryParams = ref<TableQueryParams>({ page: 1, per_page: 15 })
const statusFilter = ref<string>('')
const priorityFilter = ref<string>('')

const { data, isLoading, isError } = useTicketsQuery(
  computed(() => ({
    ...queryParams.value,
    status: statusFilter.value || undefined,
    priority: priorityFilter.value || undefined,
  })),
)

const items = computed(() => data.value?.data ?? [])
const meta = computed(() => data.value?.meta)

const columns = [
  { key: 'title', label: 'Título', sortable: true },
  { key: 'status', label: 'Estado' },
  { key: 'priority', label: 'Prioridad' },
  { key: 'unit.number', label: 'Unidad' },
  { key: 'resident.name', label: 'Residente' },
  { key: 'created_at', label: 'Creado', sortable: true },
]

const statusOptions = [
  { title: 'Todos', value: '' },
  { title: 'Abierto', value: 'open' },
  { title: 'En Progreso', value: 'in_progress' },
  { title: 'Resuelto', value: 'resolved' },
  { title: 'Cerrado', value: 'closed' },
]

const priorityOptions = [
  { title: 'Todas', value: '' },
  { title: 'Alta', value: 'high' },
  { title: 'Media', value: 'medium' },
  { title: 'Baja', value: 'low' },
]

function onPageChange(page: number) {
  queryParams.value = { ...queryParams.value, page }
}

function onSearch(search: string) {
  queryParams.value = { ...queryParams.value, page: 1, search }
}

const ticketStatusColor = (s: string) => ({ open: 'info', in_progress: 'warning', resolved: 'success', closed: 'default' }[s] ?? 'default')
const priorityColor = (s: string) => ({ high: 'error', medium: 'warning', low: 'success' }[s] ?? 'default')

function viewTicket(ticket: { id: number }) {
  router.push({ name: 'condoflow.tickets.detail', params: { id: ticket.id } })
}
</script>

<template>
  <AppPageLayout title="Tickets de Mantención" subtitle="Gestión de solicitudes de mantenimiento">
    <template #actions>
      <AppButton color="primary" prepend-icon="mdi-plus">
        Nuevo Ticket
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
      <v-col cols="12" sm="4">
        <v-select
          v-model="priorityFilter"
          :items="priorityOptions"
          label="Prioridad"
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
      @row-click="viewTicket"
    >
      <template #col-status="{ item }">
        <AppStatusChip :label="item.status" :color="ticketStatusColor(item.status)" />
      </template>
      <template #col-priority="{ item }">
        <AppStatusChip :label="item.priority" :color="priorityColor(item.priority)" />
      </template>
      <template #[`col-unit.number`]="{ item }">
        {{ item.unit?.number ?? '—' }}
      </template>
      <template #[`col-resident.name`]="{ item }">
        {{ item.resident?.name ?? '—' }}
      </template>
      <template #col-created_at="{ value }">
        {{ new Date(value).toLocaleDateString() }}
      </template>
    </AppDataTable>
  </AppPageLayout>
</template>
