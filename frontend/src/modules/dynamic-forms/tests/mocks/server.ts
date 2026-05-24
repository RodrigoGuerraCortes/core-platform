// Re-export the global test server so dynamic-forms module tests can call
// server.use() against the same MSW instance that is started in setup.ts.
// This ensures per-test handler overrides actually apply.
export { server } from '@/tests/mocks/server'
