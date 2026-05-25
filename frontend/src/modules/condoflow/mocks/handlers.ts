import { http, HttpResponse } from 'msw'
import type { Building, Unit, Resident, MaintenanceTicket, CondoDashboard } from '../types'

const buildings: Building[] = [
  { id: 1, tenant_id: 1, name: 'Torre Norte', address: 'Av. Principal 100', floors: 20, units_count: 40, metadata: null, created_at: '2026-01-15T10:00:00.000Z', updated_at: '2026-01-15T10:00:00.000Z' },
  { id: 2, tenant_id: 1, name: 'Torre Sur', address: 'Av. Principal 200', floors: 15, units_count: 30, metadata: null, created_at: '2026-02-01T10:00:00.000Z', updated_at: '2026-02-01T10:00:00.000Z' },
]

const units: Unit[] = [
  { id: 1, tenant_id: 1, building_id: 1, number: '101', floor: 1, type: 'apartment', status: 'occupied', metadata: null, created_at: '2026-01-15T10:00:00.000Z', updated_at: '2026-01-15T10:00:00.000Z' },
  { id: 2, tenant_id: 1, building_id: 1, number: '102', floor: 1, type: 'apartment', status: 'available', metadata: null, created_at: '2026-01-15T10:00:00.000Z', updated_at: '2026-01-15T10:00:00.000Z' },
  { id: 3, tenant_id: 1, building_id: 2, number: '201', floor: 2, type: 'office', status: 'maintenance', metadata: null, created_at: '2026-02-01T10:00:00.000Z', updated_at: '2026-02-01T10:00:00.000Z' },
]

const residents: Resident[] = [
  { id: 1, tenant_id: 1, unit_id: 1, name: 'Juan Pérez', rut: '12.345.678-9', email: 'juan@test.cl', phone: '+56912345678', status: 'active', metadata: null, created_at: '2026-01-20T10:00:00.000Z', updated_at: '2026-01-20T10:00:00.000Z' },
  { id: 2, tenant_id: 1, unit_id: null, name: 'María López', rut: null, email: 'maria@test.cl', phone: null, status: 'inactive', metadata: null, created_at: '2026-03-01T10:00:00.000Z', updated_at: '2026-03-01T10:00:00.000Z' },
]

const tickets: MaintenanceTicket[] = [
  { id: 1, tenant_id: 1, unit_id: 1, resident_id: 1, title: 'Fuga de agua en baño', description: 'Se detectó fuga bajo el lavamanos.', status: 'open', priority: 'high', metadata: null, created_at: '2026-05-20T08:00:00.000Z', updated_at: '2026-05-20T08:00:00.000Z' },
  { id: 2, tenant_id: 1, unit_id: 2, resident_id: null, title: 'Pintura descascarada', description: 'Pasillo piso 1', status: 'in_progress', priority: 'low', metadata: null, created_at: '2026-05-18T10:00:00.000Z', updated_at: '2026-05-19T14:00:00.000Z' },
  { id: 3, tenant_id: 1, unit_id: 3, resident_id: null, title: 'Ascensor detenido', description: null, status: 'resolved', priority: 'high', metadata: null, created_at: '2026-05-10T10:00:00.000Z', updated_at: '2026-05-15T10:00:00.000Z' },
]

function paginate<T>(items: T[], params: URLSearchParams) {
  const page = Number(params.get('page') || 1)
  const perPage = Number(params.get('per_page') || 15)
  const start = (page - 1) * perPage
  const paged = items.slice(start, start + perPage)
  return {
    data: paged,
    meta: { current_page: page, last_page: Math.ceil(items.length / perPage), per_page: perPage, total: items.length, from: start + 1, to: start + paged.length },
    links: { first: '', last: '', prev: null, next: null },
  }
}

export const condoflowHandlers = [
  // Dashboard
  http.get('*/api/condoflow/dashboard', () => {
    const dashboard: CondoDashboard = {
      buildings_count: buildings.length,
      units_count: units.length,
      residents_count: residents.length,
      open_tickets_count: tickets.filter(t => t.status === 'open').length,
      in_progress_tickets_count: tickets.filter(t => t.status === 'in_progress').length,
      tickets_by_priority: {
        high: tickets.filter(t => t.priority === 'high' && ['open', 'in_progress'].includes(t.status)).length,
        medium: tickets.filter(t => t.priority === 'medium' && ['open', 'in_progress'].includes(t.status)).length,
        low: tickets.filter(t => t.priority === 'low' && ['open', 'in_progress'].includes(t.status)).length,
      },
      recent_tickets: tickets.slice(0, 5).map(t => ({ id: t.id, title: t.title, status: t.status, priority: t.priority, created_at: t.created_at })),
    }
    return HttpResponse.json({ data: dashboard })
  }),

  // Buildings
  http.get('*/api/condoflow/buildings', ({ request }) => {
    const url = new URL(request.url)
    let filtered = [...buildings]
    const search = url.searchParams.get('search')
    if (search) filtered = filtered.filter(b => b.name.toLowerCase().includes(search.toLowerCase()))
    return HttpResponse.json(paginate(filtered, url.searchParams))
  }),

  // Units
  http.get('*/api/condoflow/units', ({ request }) => {
    const url = new URL(request.url)
    let filtered = [...units]
    const status = url.searchParams.get('status')
    if (status) filtered = filtered.filter(u => u.status === status)
    return HttpResponse.json(paginate(filtered, url.searchParams))
  }),

  // Residents
  http.get('*/api/condoflow/residents', ({ request }) => {
    const url = new URL(request.url)
    let filtered = [...residents]
    const status = url.searchParams.get('status')
    if (status) filtered = filtered.filter(r => r.status === status)
    return HttpResponse.json(paginate(filtered, url.searchParams))
  }),

  // Tickets
  http.get('*/api/condoflow/tickets', ({ request }) => {
    const url = new URL(request.url)
    let filtered = [...tickets]
    const status = url.searchParams.get('status')
    if (status) filtered = filtered.filter(t => t.status === status)
    const priority = url.searchParams.get('priority')
    if (priority) filtered = filtered.filter(t => t.priority === priority)
    return HttpResponse.json(paginate(filtered, url.searchParams))
  }),

  http.get('*/api/condoflow/tickets/:id', ({ params }) => {
    const ticket = tickets.find(t => t.id === Number(params.id))
    if (!ticket) return new HttpResponse(null, { status: 404 })
    return HttpResponse.json({ data: ticket })
  }),

  http.patch('*/api/condoflow/tickets/:id', async ({ params, request }) => {
    const ticket = tickets.find(t => t.id === Number(params.id))
    if (!ticket) return new HttpResponse(null, { status: 404 })
    const body = await request.json() as Record<string, unknown>
    Object.assign(ticket, body)
    return HttpResponse.json({ data: ticket })
  }),
]
