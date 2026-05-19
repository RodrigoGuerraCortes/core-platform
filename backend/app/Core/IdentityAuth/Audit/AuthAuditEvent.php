<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\Audit;

use DateTimeImmutable;

/**
 * Immutable value object representing an audit-ready auth event.
 *
 * Rules: must never contain passwords, reset tokens, plain API tokens,
 * roles, permissions, tenant context, or authorization headers.
 */
final class AuthAuditEvent
{
    public function __construct(
        public readonly string $eventName,
        public readonly DateTimeImmutable $occurredAt,
        public readonly ?int $actorId = null,
        public readonly ?int $subjectId = null,
        public readonly ?string $email = null,
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
        public readonly array $metadata = [],
    ) {}
}
