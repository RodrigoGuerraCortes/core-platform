<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\Listeners;

use App\Core\IdentityAuth\Audit\AuthAuditPayloadFactory;
use App\Core\IdentityAuth\Audit\AuthAuditSink;

final class RecordAuthAuditEvent
{
    public function __construct(
        private readonly AuthAuditPayloadFactory $factory,
        private readonly AuthAuditSink $sink,
    ) {}

    public function handle(object $event): void
    {
        $auditEvent = $this->factory->fromEvent($event);

        if ($auditEvent === null) {
            return;
        }

        $this->sink->record($auditEvent);
    }
}
