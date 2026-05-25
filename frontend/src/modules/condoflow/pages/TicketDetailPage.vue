<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useTicketDetailQuery, useUpdateTicketMutation } from '../composables'
import { AppPageLayout, AppButton, AppStatusChip, AppDetailLayout } from '@/shared/ui'

const route = useRoute()
const router = useRouter()
const ticketId = computed(() => Number(route.params.id))

const { data, isLoading, isError } = useTicketDetailQuery(ticketId)
const updateMutation = useUpdateTicketMutation()

const ticket = computed(() => data.value?.data)

const statusTransitions: Record<string, { label: string; next: string; color: string }[]> = {
  open: [{ label: 'Iniciar Trabajo', next: 'in_progress', color: 'primary' }],
  in_progress: [
    { label: 'Marcar Resuelto', next: 'resolved', color: 'success' },
    { label: 'Volver a Abierto', next: 'open', color: 'warning' },
  ],
  resolved: [
    { label: 'Cerrar Ticket', next: 'closed', color: 'info' },
    { label: 'Reabrir', next: 'open', color: 'warning' },
  ],
  closed: [{ label: 'Reabrir', next: 'open', color: 'warning' }],
}

function transitionStatus(nextStatus: string) {
  if (!ticket.value) return
  updateMutation.mutate({ id: ticket.value.id, status: nextStatus })
}

const ticketStatusColor = (s: string) => ({ open: 'info', in_progress: 'warning', resolved: 'success', closed: 'default' }[s] ?? 'default')
const priorityColor = (s: string) => ({ high: 'error', medium: 'warning', low: 'success' }[s] ?? 'default')

function goBack() {
  router.push({ name: 'condoflow.tickets.index' })
}
</script>

<template>
  <AppPageLayout title="Detalle de Ticket">
    <template #actions>
      <AppButton variant="ghost" prepend-icon="mdi-arrow-left" @click="goBack">
        Volver
      </AppButton>
    </template>

    <v-progress-linear v-if="isLoading" indeterminate />

    <v-alert v-if="isError" type="error" class="mb-4">
      Error al cargar el ticket.
    </v-alert>

    <AppDetailLayout v-if="ticket">
      <template #header>
        <div class="d-flex align-center ga-3 flex-wrap">
          <h2 class="text-h5">{{ ticket.title }}</h2>
          <AppStatusChip :label="ticket.status" :color="ticketStatusColor(ticket.status)" />
          <AppStatusChip :label="ticket.priority" :color="priorityColor(ticket.priority)" />
        </div>
      </template>

      <template #content>
        <v-card class="mb-4">
          <v-card-text>
            <v-row>
              <v-col cols="12" md="6">
                <div class="text-caption text-medium-emphasis">Descripción</div>
                <div>{{ ticket.description || 'Sin descripción' }}</div>
              </v-col>
              <v-col cols="12" md="3">
                <div class="text-caption text-medium-emphasis">Unidad</div>
                <div>{{ ticket.unit?.number ?? 'No asignada' }}</div>
              </v-col>
              <v-col cols="12" md="3">
                <div class="text-caption text-medium-emphasis">Residente</div>
                <div>{{ ticket.resident?.name ?? 'No asignado' }}</div>
              </v-col>
            </v-row>

            <v-divider class="my-4" />

            <v-row>
              <v-col cols="12" md="4">
                <div class="text-caption text-medium-emphasis">Creado</div>
                <div>{{ new Date(ticket.created_at).toLocaleString() }}</div>
              </v-col>
              <v-col cols="12" md="4">
                <div class="text-caption text-medium-emphasis">Actualizado</div>
                <div>{{ new Date(ticket.updated_at).toLocaleString() }}</div>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <!-- Status transitions -->
        <v-card class="mb-4">
          <v-card-title class="text-subtitle-1">Acciones</v-card-title>
          <v-card-text>
            <div class="d-flex ga-2 flex-wrap">
              <AppButton
                v-for="action in (statusTransitions[ticket.status] ?? [])"
                :key="action.next"
                :color="action.color"
                :loading="updateMutation.isPending.value"
                @click="transitionStatus(action.next)"
              >
                {{ action.label }}
              </AppButton>
            </div>
          </v-card-text>
        </v-card>

        <!-- Activity Timeline placeholder -->
        <v-card>
          <v-card-title class="text-subtitle-1">Actividad</v-card-title>
          <v-card-text>
            <v-timeline density="compact" side="end">
              <v-timeline-item dot-color="primary" size="small">
                <div class="text-body-2">Ticket creado</div>
                <div class="text-caption text-medium-emphasis">
                  {{ new Date(ticket.created_at).toLocaleString() }}
                </div>
              </v-timeline-item>
            </v-timeline>
          </v-card-text>
        </v-card>
      </template>
    </AppDetailLayout>
  </AppPageLayout>
</template>
