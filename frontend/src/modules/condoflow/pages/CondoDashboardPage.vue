<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useCondoDashboardQuery } from '../composables'
import { AppPageLayout, AppButton, AppStatusChip } from '@/shared/ui'

const router = useRouter()
const { data, isLoading, isError } = useCondoDashboardQuery()

const dashboard = computed(() => data.value?.data)

const ticketStatusColor = (s: string) => ({ open: 'info', in_progress: 'warning', resolved: 'success', closed: 'default' }[s] ?? 'default')
const priorityColor = (s: string) => ({ high: 'error', medium: 'warning', low: 'success' }[s] ?? 'default')

function navigateTo(name: string) {
  router.push({ name })
}
</script>

<template>
  <AppPageLayout title="CondoFlow" subtitle="Panel de administración de condominio">
    <template #actions>
      <AppButton color="primary" @click="navigateTo('condoflow.tickets.index')">
        Ver Tickets
      </AppButton>
    </template>

    <v-alert v-if="isError" type="error" class="mb-4">
      Error al cargar el dashboard. Intente nuevamente.
    </v-alert>

    <v-progress-linear v-if="isLoading" indeterminate class="mb-4" />

    <template v-if="dashboard">
      <!-- Summary cards -->
      <v-row class="mb-6">
        <v-col cols="12" sm="6" md="3">
          <v-card class="pa-4 text-center" @click="navigateTo('condoflow.buildings.index')">
            <div class="text-h4">{{ dashboard.buildings_count }}</div>
            <div class="text-body-2 text-medium-emphasis">Edificios</div>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card class="pa-4 text-center" @click="navigateTo('condoflow.units.index')">
            <div class="text-h4">{{ dashboard.units_count }}</div>
            <div class="text-body-2 text-medium-emphasis">Unidades</div>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card class="pa-4 text-center" @click="navigateTo('condoflow.residents.index')">
            <div class="text-h4">{{ dashboard.residents_count }}</div>
            <div class="text-body-2 text-medium-emphasis">Residentes</div>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card class="pa-4 text-center" @click="navigateTo('condoflow.tickets.index')">
            <div class="text-h4">{{ dashboard.open_tickets_count }}</div>
            <div class="text-body-2 text-medium-emphasis">Tickets Abiertos</div>
          </v-card>
        </v-col>
      </v-row>

      <!-- Priority breakdown -->
      <v-row class="mb-6">
        <v-col cols="12" md="4">
          <v-card class="pa-4">
            <div class="text-subtitle-1 mb-2">Tickets por Prioridad</div>
            <div class="d-flex align-center mb-1">
              <AppStatusChip label="High" color="error" class="mr-2" />
              <span>Alta: {{ dashboard.tickets_by_priority.high }}</span>
            </div>
            <div class="d-flex align-center mb-1">
              <AppStatusChip label="Medium" color="warning" class="mr-2" />
              <span>Media: {{ dashboard.tickets_by_priority.medium }}</span>
            </div>
            <div class="d-flex align-center">
              <AppStatusChip label="Low" color="success" class="mr-2" />
              <span>Baja: {{ dashboard.tickets_by_priority.low }}</span>
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" md="8">
          <v-card class="pa-4">
            <div class="text-subtitle-1 mb-2">Tickets Recientes</div>
            <v-list density="compact">
              <v-list-item
                v-for="ticket in dashboard.recent_tickets"
                :key="ticket.id"
                @click="router.push({ name: 'condoflow.tickets.detail', params: { id: ticket.id } })"
              >
                <v-list-item-title>{{ ticket.title }}</v-list-item-title>
                <template #append>
                  <AppStatusChip :label="ticket.priority" :color="priorityColor(ticket.priority)" size="small" class="mr-2" />
                  <AppStatusChip :label="ticket.status" :color="ticketStatusColor(ticket.status)" size="small" />
                </template>
              </v-list-item>
            </v-list>
          </v-card>
        </v-col>
      </v-row>
    </template>
  </AppPageLayout>
</template>
