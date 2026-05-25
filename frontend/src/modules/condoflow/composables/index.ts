import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { toValue, type MaybeRef } from 'vue'
import {
  fetchBuildings, fetchUnits, fetchResidents,
  fetchTickets, fetchTicket, fetchCondoDashboard,
  createBuilding, createUnit, createResident, createTicket,
  updateTicket, deleteBuilding, deleteUnit, deleteResident, deleteTicket,
} from '../api/condoflow'
import type { TableQueryParams } from '@/shared/table'

// ─── Queries ──────────────────────────────────────────────────────────────────

export function useBuildingsQuery(params: MaybeRef<TableQueryParams> = { page: 1, per_page: 15 }) {
  return useQuery({
    queryKey: ['condoflow', 'buildings', params] as const,
    queryFn: () => fetchBuildings(toValue(params)),
    staleTime: 30_000,
  })
}

export function useUnitsQuery(params: MaybeRef<TableQueryParams> = { page: 1, per_page: 15 }) {
  return useQuery({
    queryKey: ['condoflow', 'units', params] as const,
    queryFn: () => fetchUnits(toValue(params)),
    staleTime: 30_000,
  })
}

export function useResidentsQuery(params: MaybeRef<TableQueryParams> = { page: 1, per_page: 15 }) {
  return useQuery({
    queryKey: ['condoflow', 'residents', params] as const,
    queryFn: () => fetchResidents(toValue(params)),
    staleTime: 30_000,
  })
}

export function useTicketsQuery(params: MaybeRef<TableQueryParams> = { page: 1, per_page: 15 }) {
  return useQuery({
    queryKey: ['condoflow', 'tickets', params] as const,
    queryFn: () => fetchTickets(toValue(params)),
    staleTime: 30_000,
  })
}

export function useTicketDetailQuery(id: MaybeRef<number>) {
  return useQuery({
    queryKey: ['condoflow', 'tickets', id] as const,
    queryFn: () => fetchTicket(toValue(id)),
    staleTime: 30_000,
  })
}

export function useCondoDashboardQuery() {
  return useQuery({
    queryKey: ['condoflow', 'dashboard'] as const,
    queryFn: () => fetchCondoDashboard(),
    staleTime: 60_000,
  })
}

// ─── Mutations ────────────────────────────────────────────────────────────────

export function useCreateBuildingMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: createBuilding,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['condoflow', 'buildings'] }),
  })
}

export function useDeleteBuildingMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: deleteBuilding,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['condoflow', 'buildings'] }),
  })
}

export function useCreateUnitMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: createUnit,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['condoflow', 'units'] }),
  })
}

export function useDeleteUnitMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: deleteUnit,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['condoflow', 'units'] }),
  })
}

export function useCreateResidentMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: createResident,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['condoflow', 'residents'] }),
  })
}

export function useDeleteResidentMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: deleteResident,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['condoflow', 'residents'] }),
  })
}

export function useCreateTicketMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: createTicket,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['condoflow', 'tickets'] }),
  })
}

export function useUpdateTicketMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, ...payload }: { id: number } & Record<string, unknown>) => updateTicket(id, payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['condoflow', 'tickets'] }),
  })
}

export function useDeleteTicketMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: deleteTicket,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['condoflow', 'tickets'] }),
  })
}
