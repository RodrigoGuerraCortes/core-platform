# Form Rendering Conventions

**Block:** 6.3 — Frontend Architecture & Governance  
**Status:** Frozen  
**Date:** 2026-05-22

---

## Overview

Forms are one of the highest-risk areas for module drift. Without conventions, every module invents its own validation strategy, error display, and submission state management. This document freezes the approved approach.

This also serves as the architectural foundation for the upcoming DynamicForms module, which will drive schema-driven field rendering from a backend-defined schema.

---

## Two Form Modes

The platform supports two complementary form modes that must stay cleanly separated:

| Mode | Description | Use Case |
|---|---|---|
| **Static forms** | Hard-coded Vue components with known fields | Auth forms, settings, simple CRUD |
| **Dynamic forms** | Schema-driven field rendering from a backend schema | DynamicForms module (future) |

This document governs both, with a focus on static forms now and the dynamic contract as a future-safe boundary.

---

## Static Form Architecture

### Structure

```
modules/{module}/
  components/
    {Module}Form.vue         # The form UI component (renders fields only)
  composables/
    use{Module}Form.ts       # Form state, validation, submission
  types/
    forms.ts                 # Payload types for this module's forms
```

### Component Responsibility

Form components are **presentational only**. They receive state from a composable via props and emit events — they do not own submit logic.

```vue
<!-- modules/projects/components/ProjectForm.vue -->
<script setup lang="ts">
import type { CreateProjectPayload } from '../types/forms'

const props = defineProps<{
  modelValue: CreateProjectPayload
  validationErrors: Record<string, string[]>
  isSubmitting: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: CreateProjectPayload]
  submit: []
}>()
</script>
```

### Composable Responsibility

All form state, validation, and submission are owned by `use{Module}Form.ts`. See [composables-conventions.md](./composables-conventions.md) for the composable shape.

---

## Validation Layering

Validation is a two-layer system. Both layers must always be present:

### Layer 1 — Client-side validation

Light, fast, UX-focused checks. Runs before API submission. Prevents obviously invalid payloads.

- Required fields
- Format checks (email, URL, date format)
- Min/max length
- Cross-field dependencies (e.g., end date after start date)

Use [VeeValidate](https://vee-validate.logaretm.com/) or [Zod](https://zod.dev/) for schema-based validation. The chosen library must be consistent across all modules (select one, document it here).

**Recommended:** Zod schemas defined alongside the payload types:

```typescript
// modules/projects/types/forms.ts

import { z } from 'zod'

export const createProjectSchema = z.object({
  name: z.string().min(1, 'Name is required').max(255),
  description: z.string().max(1000).optional(),
})

export type CreateProjectPayload = z.infer<typeof createProjectSchema>
```

### Layer 2 — Server-side validation errors

Backend returns validation errors in Laravel's standard format:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."],
    "email": ["The email has already been taken."]
  }
}
```

The form composable catches these errors and maps them to `validationErrors`:

```typescript
// In use{Module}Form.ts
catch (error: unknown) {
  if (isApiError(error) && error.errors) {
    validationErrors.value = error.errors
  }
}
```

Field components read from `validationErrors[fieldName]` to display server messages.

---

## Error Display Contract

Every input field component must support an `errors` prop:

```vue
<!-- shared/ui/FormField.vue -->
<script setup lang="ts">
defineProps<{
  label: string
  errors?: string[]
}>()
</script>

<template>
  <div class="form-field">
    <label>{{ label }}</label>
    <slot />
    <p v-for="error in errors" :key="error" class="field-error">{{ error }}</p>
  </div>
</template>
```

Rules:
- Server errors display as-is (they are already translated by the backend)
- Client errors display the Zod error message
- Server errors clear when the user starts editing the field (not on submit)

---

## Submission State

Forms expose three states during submission. All three must be handled:

| State | Description | UI Behavior |
|---|---|---|
| `isSubmitting: true` | Request in flight | Disable submit button, show spinner |
| `validationErrors` | Field-level server errors | Display under each field |
| `serverError` | Non-field error (e.g., 500, auth) | Display as top-level alert |

---

## Dynamic Form Contract (Future — DynamicForms Module)

The DynamicForms module will receive a JSON schema from the backend that defines:

- Field list (name, type, label, validation rules, order)
- Section grouping
- Conditional visibility rules

The frontend rendering engine must:

1. Accept a `FormSchema` type (defined in `shared/types/forms.ts`)
2. Resolve each field to a registered field renderer component
3. Apply backend validation rules as client-side constraints
4. Return the filled payload in the backend's expected shape

### Field Renderer Registry (Future)

A registry maps field type strings to Vue components:

```typescript
// shared/lib/fieldRenderers.ts (future)

const fieldRenderers: Record<string, Component> = {
  text: TextFieldRenderer,
  email: EmailFieldRenderer,
  select: SelectFieldRenderer,
  date: DateFieldRenderer,
  checkbox: CheckboxFieldRenderer,
}

export function resolveFieldRenderer(type: string): Component {
  return fieldRenderers[type] ?? UnknownFieldRenderer
}
```

This is frozen as a future contract — do not implement until the DynamicForms module is formally started.

---

## Banned Patterns

- `v-model` bound directly to Pinia store state in a form
- Form submission logic (`axios.post()`) inside `<script setup>` of a form component
- Catching validation errors inside a page component instead of the form composable
- Displaying raw error objects (e.g., `{{ error }}`) in templates
- Server validation messages hardcoded in the frontend
- Forms that reset state on every route navigation without explicit intent
