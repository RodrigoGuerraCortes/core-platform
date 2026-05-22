import type { FormSchema, FormDetail, FormVersionDetail } from '../../types'

export const basicSchema: FormSchema = {
  version: 1,
  title: 'Contact Form',
  settings: { allow_multiple_submissions: true },
  fields: [
    { key: 'full_name', type: 'text', label: 'Full Name', required: true, order: 1 },
    { key: 'email', type: 'email', label: 'Email', required: true, order: 2 },
    {
      key: 'country',
      type: 'select',
      label: 'Country',
      required: true,
      order: 3,
      options: [
        { value: 'us', label: 'United States' },
        { value: 'ca', label: 'Canada' },
      ],
    },
    { key: 'notes', type: 'textarea', label: 'Notes', required: false, order: 4 },
  ],
}

export const schemaWithSection: FormSchema = {
  version: 1,
  title: 'Sectioned Form',
  fields: [
    { key: 'sec_personal', type: 'section', label: 'Personal Info', required: false, order: 0 },
    { key: 'name', type: 'text', label: 'Name', required: true, order: 1 },
    { key: 'sec_contact', type: 'section', label: 'Contact Info', required: false, order: 2 },
    { key: 'email', type: 'email', label: 'Email', required: true, order: 3 },
  ],
}

export const schemaWithAllTypes: FormSchema = {
  version: 1,
  title: 'All Field Types',
  fields: [
    { key: 'txt', type: 'text', label: 'Text', required: false, order: 1 },
    { key: 'area', type: 'textarea', label: 'Textarea', required: false, order: 2 },
    { key: 'num', type: 'number', label: 'Number', required: false, order: 3 },
    { key: 'em', type: 'email', label: 'Email', required: false, order: 4 },
    { key: 'dt', type: 'date', label: 'Date', required: false, order: 5 },
    { key: 'chk', type: 'checkbox', label: 'Checkbox', required: false, order: 6 },
    { key: 'sec', type: 'section', label: 'Section', required: false, order: 7 },
    {
      key: 'sel',
      type: 'select',
      label: 'Select',
      required: false,
      order: 8,
      options: [{ value: 'a', label: 'A' }],
    },
    {
      key: 'rad',
      type: 'radio',
      label: 'Radio',
      required: false,
      order: 9,
      options: [{ value: 'x', label: 'X' }, { value: 'y', label: 'Y' }],
    },
  ],
}

export const mockVersion: FormVersionDetail = {
  id: 1,
  form_id: 1,
  version_number: 1,
  schema: basicSchema,
  schema_hash: 'abc123',
  label: 'v1',
  published_at: '2026-05-22T00:00:00Z',
}

export const mockForm: FormDetail = {
  id: 1,
  tenant_id: 1,
  name: 'Contact Form',
  slug: 'contact-form',
  status: 'active',
  active_version_id: 1,
  active_version: mockVersion,
  created_at: '2026-05-22T00:00:00Z',
  updated_at: '2026-05-22T00:00:00Z',
}
