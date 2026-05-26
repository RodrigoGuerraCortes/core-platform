export interface Building {
  id: number
  tenant_id: number
  name: string
  address: string | null
  floors: number
  units_count?: number
  metadata: Record<string, unknown> | null
  created_at: string
  updated_at: string
  [key: string]: unknown
}

export interface Unit {
  id: number
  tenant_id: number
  building_id: number
  building?: Building
  number: string
  floor: number
  type: 'apartment' | 'office' | 'commercial' | 'parking' | 'storage'
  status: 'available' | 'occupied' | 'maintenance'
  metadata: Record<string, unknown> | null
  created_at: string
  updated_at: string
  [key: string]: unknown
}

export interface Resident {
  id: number
  tenant_id: number
  unit_id: number | null
  unit?: Unit
  name: string
  rut: string | null
  email: string | null
  phone: string | null
  status: 'active' | 'inactive'
  metadata: Record<string, unknown> | null
  created_at: string
  updated_at: string
  [key: string]: unknown
}

export interface MaintenanceTicket {
  id: number
  tenant_id: number
  unit_id: number | null
  resident_id: number | null
  unit?: Unit
  resident?: Resident
  title: string
  description: string | null
  status: 'open' | 'in_progress' | 'resolved' | 'closed'
  priority: 'low' | 'medium' | 'high'
  metadata: Record<string, unknown> | null
  created_at: string
  updated_at: string
  [key: string]: unknown
}

export interface CondoDashboard {
  buildings_count: number
  units_count: number
  residents_count: number
  open_tickets_count: number
  in_progress_tickets_count: number
  tickets_by_priority: {
    high: number
    medium: number
    low: number
  }
  recent_tickets: Array<{
    id: number
    title: string
    status: string
    priority: string
    created_at: string
  }>
}
