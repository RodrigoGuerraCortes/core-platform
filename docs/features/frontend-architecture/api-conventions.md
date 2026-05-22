# Frontend API Conventions

**Block:** 6.3 — Frontend Architecture & Governance  
**Status:** Frozen  
**Date:** 2026-05-22

---

## Overview

All API access is centralized through a single configured Axios instance. No module touches HTTP directly. Tenant context is propagated automatically at the client layer, not scattered across individual API functions.

---

## Single API Client

One Axios instance for the entire application, configured in `shared/api/client.ts`:

```typescript
// shared/api/client.ts

import axios from 'axios'
import { useTenantContext } from '@/shared/composables/useTenantContext'

export const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

apiClient.interceptors.request.use((config) => {
  const { tenantId } = useTenantContext()
  if (tenantId.value) {
    config.headers['X-Tenant-ID'] = tenantId.value
  }
  return config
})

apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    // Normalize API errors before they reach composables
    return Promise.reject(normalizeApiError(error))
  },
)
```

---

## Tenant Header Propagation

The `X-Tenant-ID` header is injected by the request interceptor automatically. No module-level API function sets tenant headers manually.

Rules:
- Tenant ID sourced from `useTenantContext()` (resolved once, reactive ref)
- If tenant context is missing on a tenant-required request, the interceptor must throw a typed error, not silently omit the header
- No hardcoded tenant IDs anywhere in API functions

---

## API Functions Per Module

Each module owns its API functions in `modules/{module}/api/`. They are thin wrappers over `apiClient` that return typed responses:

```typescript
// modules/projects/api/projects.ts

import { apiClient } from '@/shared/api/client'
import type { ApiResponse, PaginatedResponse } from '@/shared/types/api'
import type { Project, ProjectListItem, CreateProjectPayload } from '../types/project'

export async function fetchProjects(params?: Record<string, unknown>): Promise<PaginatedResponse<ProjectListItem>> {
  const response = await apiClient.get('/api/projects', { params })
  return response.data
}

export async function fetchProject(id: string): Promise<ApiResponse<Project>> {
  const response = await apiClient.get(`/api/projects/${id}`)
  return response.data
}

export async function createProject(payload: CreateProjectPayload): Promise<ApiResponse<Project>> {
  const response = await apiClient.post('/api/projects', payload)
  return response.data
}

export async function updateProject(id: string, payload: Partial<CreateProjectPayload>): Promise<ApiResponse<Project>> {
  const response = await apiClient.patch(`/api/projects/${id}`, payload)
  return response.data
}

export async function deleteProject(id: string): Promise<void> {
  await apiClient.delete(`/api/projects/${id}`)
}
```

---

## Response Type Contracts

All API responses map to typed wrappers defined in `shared/types/api.ts`:

```typescript
// shared/types/api.ts

export interface ApiResponse<T> {
  data: T
  message?: string
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: PaginationMeta
  links: PaginationLinks
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}

export interface PaginationLinks {
  first: string | null
  last: string | null
  prev: string | null
  next: string | null
}

export interface ApiError {
  message: string
  errors?: Record<string, string[]>
  status: number
}
```

---

## Error Normalization

The response interceptor normalizes all HTTP errors into `ApiError` before they reach composables or components:

```typescript
// shared/api/errors.ts

import type { ApiError } from '@/shared/types/api'

export function normalizeApiError(error: unknown): ApiError {
  if (axios.isAxiosError(error) && error.response) {
    return {
      message: error.response.data?.message ?? 'An unexpected error occurred.',
      errors: error.response.data?.errors,
      status: error.response.status,
    }
  }
  return { message: 'Network error. Please check your connection.', status: 0 }
}
```

Composables receive `ApiError`, never raw Axios errors.

---

## Authentication

Bearer tokens are attached by the request interceptor from the auth store (see state-management.md). No API function reads from `localStorage` directly.

```typescript
apiClient.interceptors.request.use((config) => {
  const token = authStore.accessToken  // Pinia store, not raw localStorage
  if (token) {
    config.headers['Authorization'] = `Bearer ${token}`
  }
  return config
})
```

---

## URL Conventions

Frontend API paths must mirror backend route conventions exactly:

| Backend route | Frontend API call |
|---|---|
| `GET /api/projects` | `apiClient.get('/api/projects')` |
| `GET /api/projects/{id}` | `apiClient.get('/api/projects/${id}')` |
| `POST /api/projects` | `apiClient.post('/api/projects', payload)` |
| `PATCH /api/projects/{id}` | `apiClient.patch('/api/projects/${id}', payload)` |
| `DELETE /api/projects/{id}` | `apiClient.delete('/api/projects/${id}')` |

Always use `PATCH` (not `PUT`) for partial updates, matching backend conventions.

---

## Banned Patterns

- Direct `axios.create()` inside modules — only `shared/api/client.ts` creates instances
- `fetch()` API calls anywhere in the frontend codebase
- Setting `X-Tenant-ID` manually in module API functions
- Reading `localStorage` or `sessionStorage` directly for auth tokens
- Untyped `any` return types on API functions
- Catching errors inside API functions and swallowing them silently
