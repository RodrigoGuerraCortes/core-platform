import apiClient from '@/shared/api/client'
import type { ApiResponse, PaginatedResponse } from '@/shared/types'
import type { Building, Unit, Resident, MaintenanceTicket, CondoDashboard } from '../types'

// ─── Buildings ────────────────────────────────────────────────────────────────

export async function fetchBuildings(params: Record<string, unknown> = {}): Promise<PaginatedResponse<Building>> {
  const { data } = await apiClient.get<PaginatedResponse<Building>>('/condoflow/buildings', {
    params: Object.fromEntries(Object.entries(params).filter(([, v]) => v != null && v !== '')),
  })
  return data
}

export async function fetchBuilding(id: number): Promise<ApiResponse<Building>> {
  const { data } = await apiClient.get<ApiResponse<Building>>(`/condoflow/buildings/${id}`)
  return data
}

export async function createBuilding(payload: Partial<Building>): Promise<ApiResponse<Building>> {
  const { data } = await apiClient.post<ApiResponse<Building>>('/condoflow/buildings', payload)
  return data
}

export async function updateBuilding(id: number, payload: Partial<Building>): Promise<ApiResponse<Building>> {
  const { data } = await apiClient.patch<ApiResponse<Building>>(`/condoflow/buildings/${id}`, payload)
  return data
}

export async function deleteBuilding(id: number): Promise<void> {
  await apiClient.delete(`/condoflow/buildings/${id}`)
}

// ─── Units ────────────────────────────────────────────────────────────────────

export async function fetchUnits(params: Record<string, unknown> = {}): Promise<PaginatedResponse<Unit>> {
  const { data } = await apiClient.get<PaginatedResponse<Unit>>('/condoflow/units', {
    params: Object.fromEntries(Object.entries(params).filter(([, v]) => v != null && v !== '')),
  })
  return data
}

export async function createUnit(payload: Partial<Unit>): Promise<ApiResponse<Unit>> {
  const { data } = await apiClient.post<ApiResponse<Unit>>('/condoflow/units', payload)
  return data
}

export async function deleteUnit(id: number): Promise<void> {
  await apiClient.delete(`/condoflow/units/${id}`)
}

// ─── Residents ────────────────────────────────────────────────────────────────

export async function fetchResidents(params: Record<string, unknown> = {}): Promise<PaginatedResponse<Resident>> {
  const { data } = await apiClient.get<PaginatedResponse<Resident>>('/condoflow/residents', {
    params: Object.fromEntries(Object.entries(params).filter(([, v]) => v != null && v !== '')),
  })
  return data
}

export async function createResident(payload: Partial<Resident>): Promise<ApiResponse<Resident>> {
  const { data } = await apiClient.post<ApiResponse<Resident>>('/condoflow/residents', payload)
  return data
}

export async function deleteResident(id: number): Promise<void> {
  await apiClient.delete(`/condoflow/residents/${id}`)
}

// ─── Tickets ──────────────────────────────────────────────────────────────────

export async function fetchTickets(params: Record<string, unknown> = {}): Promise<PaginatedResponse<MaintenanceTicket>> {
  const { data } = await apiClient.get<PaginatedResponse<MaintenanceTicket>>('/condoflow/tickets', {
    params: Object.fromEntries(Object.entries(params).filter(([, v]) => v != null && v !== '')),
  })
  return data
}

export async function fetchTicket(id: number): Promise<ApiResponse<MaintenanceTicket>> {
  const { data } = await apiClient.get<ApiResponse<MaintenanceTicket>>(`/condoflow/tickets/${id}`)
  return data
}

export async function createTicket(payload: Partial<MaintenanceTicket>): Promise<ApiResponse<MaintenanceTicket>> {
  const { data } = await apiClient.post<ApiResponse<MaintenanceTicket>>('/condoflow/tickets', payload)
  return data
}

export async function updateTicket(id: number, payload: Partial<MaintenanceTicket>): Promise<ApiResponse<MaintenanceTicket>> {
  const { data } = await apiClient.patch<ApiResponse<MaintenanceTicket>>(`/condoflow/tickets/${id}`, payload)
  return data
}

export async function deleteTicket(id: number): Promise<void> {
  await apiClient.delete(`/condoflow/tickets/${id}`)
}

// ─── Dashboard ────────────────────────────────────────────────────────────────

export async function fetchCondoDashboard(): Promise<ApiResponse<CondoDashboard>> {
  const { data } = await apiClient.get<ApiResponse<CondoDashboard>>('/condoflow/dashboard')
  return data
}
