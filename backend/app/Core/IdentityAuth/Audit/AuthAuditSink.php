<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\Audit;

interface AuthAuditSink
{
    public function record(AuthAuditEvent $event): void;
}
