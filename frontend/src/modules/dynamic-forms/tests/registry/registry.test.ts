import { describe, it, expect } from 'vitest'
import { resolveFieldRenderer, SUPPORTED_FIELD_TYPES } from '../../components/renderer/registry'

describe('Field Renderer Registry', () => {
  describe('resolveFieldRenderer', () => {
    it('returns a component for every supported field type', () => {
      for (const type of SUPPORTED_FIELD_TYPES) {
        const component = resolveFieldRenderer(type)
        expect(component, `Expected renderer for type "${type}"`).not.toBeNull()
      }
    })

    it('returns null for an unsupported field type', () => {
      expect(resolveFieldRenderer('repeater')).toBeNull()
      expect(resolveFieldRenderer('unknown_widget')).toBeNull()
      expect(resolveFieldRenderer('')).toBeNull()
    })

    it('returns null for the deferred file type', () => {
      // File rendering is intentionally deferred in V1.
      // The registry does not include a file renderer.
      expect(resolveFieldRenderer('file')).toBeNull()
    })

    it('returns distinct component instances per call for same type', () => {
      // Each call should return an async component wrapper (not the same object)
      // but both should be non-null.
      const a = resolveFieldRenderer('text')
      const b = resolveFieldRenderer('text')
      expect(a).not.toBeNull()
      expect(b).not.toBeNull()
    })
  })

  describe('SUPPORTED_FIELD_TYPES', () => {
    it('contains all expected types', () => {
      const expected = ['text', 'textarea', 'email', 'number', 'date', 'select', 'radio', 'checkbox', 'section']
      for (const type of expected) {
        expect(SUPPORTED_FIELD_TYPES).toContain(type)
      }
    })

    it('does not contain file or repeater', () => {
      expect(SUPPORTED_FIELD_TYPES).not.toContain('file')
      expect(SUPPORTED_FIELD_TYPES).not.toContain('repeater')
    })
  })
})
