<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\Audit;

/**
 * No-op audit sink used by default until a real Audit module is implemented.
 *
 * A future Audit module may bind its own AuthAuditSink implementation
 * in the container to replace this class without touching Identity/Auth code.
 */
final class NullAuthAuditSink implements AuthAuditSink
{
    public function record(AuthAuditEvent $event): void
    {
        // intentionally no-op: audit persistence is not yet implemented
    }
}
