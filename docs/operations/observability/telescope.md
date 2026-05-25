# Telescope — Internal Observability

**Platform:** Core Platform  
**Status:** Active (local + staging only by default)

---

## Purpose

Laravel Telescope provides a real-time debugging dashboard for:
- HTTP requests and responses
- Database queries (with slow query highlighting)
- Exceptions and stack traces
- Queued jobs (pending, completed, failed)
- Cache operations
- Mail and notifications
- Scheduled tasks
- Model events

---

## Access Policy

| Environment | Telescope | Access |
|---|---|---|
| Local | ✅ Enabled | Any authenticated user |
| Staging | ✅ Enabled | Platform admins only |
| Production | ❌ Disabled by default | n/a |

Production can be temporarily enabled with `TELESCOPE_ENABLED=true` for debugging, but this MUST be reverted immediately after investigation.

---

## URL

```
https://{app-url}/telescope
```

---

## What Is Monitored

| Watcher | Default | Notes |
|---|---|---|
| Requests | ✅ | All HTTP requests with response codes |
| Exceptions | ✅ | Full stack traces, context |
| Queries | ✅ | SQL with bindings; slow threshold: 50ms |
| Jobs | ✅ | Queued jobs: dispatched, completed, failed |
| Cache | ✅ | Hits, misses, puts, forgets |
| Mail | ✅ | Recipients, subject, preview |
| Notifications | ✅ | Channel, notifiable, data |
| Schedule | ✅ | Scheduled tasks execution |
| Commands | ✅ | Artisan commands |
| Models | ✅ | Created, updated, deleted events |
| Views | ❌ | Disabled — too noisy |
| Redis | ✅ | If Redis is configured |
| Gates | ✅ | Authorization checks |

---

## Custom Tags

Every Telescope entry is automatically tagged with:

| Tag | Format | Example |
|---|---|---|
| Tenant | `tenant:{slug}` | `tenant:acme` |
| User | `user:{id}` | `user:42` |
| Module | `module:{name}` | `module:forms` |

Use Telescope's tag search to filter entries by tenant, user, or module.

---

## Production Rules

1. **Never leave Telescope enabled in production** for extended periods
2. Telescope stores data in the database — it will grow unbounded without pruning
3. Run `php artisan telescope:prune --hours=48` on a schedule in non-prod environments
4. Telescope entries are NOT a replacement for structured logs — they are ephemeral
5. Do not build monitoring dashboards on Telescope data

---

## Performance Caveats

- Each request adds ~5ms overhead when Telescope is recording
- Query watcher with `hydrations: true` adds significant overhead — keep it `false`
- High-traffic environments should use `TELESCOPE_QUEUE=telescope` to process asynchronously
- The `ignore_paths` configuration excludes Telescope's own routes from recording

---

## Configuration

See `config/telescope.php` for all options.

Key environment variables:
```env
TELESCOPE_ENABLED=true      # Master switch
TELESCOPE_DRIVER=database   # Storage driver
TELESCOPE_PATH=telescope    # URL path
TELESCOPE_QUEUE=            # Queue name for async processing (optional)
```

---

## Pruning

Add to your scheduler (non-production):
```php
Schedule::command('telescope:prune --hours=48')->daily();
```

---

## Related Docs

- [Observability Rules](../../governance/backend/observability-rules.md)
- [Structured Logging](./structured-logging.md)
- [Health Checks](../health-checks.md)
