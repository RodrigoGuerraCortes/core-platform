<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\Audit;

use App\Core\IdentityAuth\Events\EmailVerified;
use App\Core\IdentityAuth\Events\LoginFailed;
use App\Core\IdentityAuth\Events\PasswordChanged;
use App\Core\IdentityAuth\Events\PasswordResetRequested;
use App\Core\IdentityAuth\Events\SanctumTokenIssued;
use App\Core\IdentityAuth\Events\SanctumTokenRevoked;
use App\Core\IdentityAuth\Events\UserLoggedIn;
use App\Core\IdentityAuth\Events\UserLoggedOut;
use App\Core\IdentityAuth\Events\VerificationEmailResent;
use DateTimeImmutable;

/**
 * Translates Identity/Auth internal events into audit-ready payloads.
 *
 * Payloads must never contain: passwords, reset tokens, plain API tokens,
 * roles, permissions, or tenant context.
 */
final class AuthAuditPayloadFactory
{
    public function fromEvent(object $event): ?AuthAuditEvent
    {
        return match (true) {
            $event instanceof UserLoggedIn => $this->fromUserLoggedIn($event),
            $event instanceof UserLoggedOut => $this->fromUserLoggedOut($event),
            $event instanceof LoginFailed => $this->fromLoginFailed($event),
            $event instanceof SanctumTokenIssued => $this->fromSanctumTokenIssued($event),
            $event instanceof SanctumTokenRevoked => $this->fromSanctumTokenRevoked($event),
            $event instanceof PasswordResetRequested => $this->fromPasswordResetRequested($event),
            $event instanceof PasswordChanged => $this->fromPasswordChanged($event),
            $event instanceof EmailVerified => $this->fromEmailVerified($event),
            $event instanceof VerificationEmailResent => $this->fromVerificationEmailResent($event),
            default => null,
        };
    }

    private function fromUserLoggedIn(UserLoggedIn $event): AuthAuditEvent
    {
        return new AuthAuditEvent(
            eventName: 'auth.user_logged_in',
            occurredAt: new DateTimeImmutable(),
            actorId: $event->user->id,
            subjectId: $event->user->id,
            email: $event->user->email,
            ipAddress: $event->ipAddress,
            userAgent: $event->userAgent,
        );
    }

    private function fromUserLoggedOut(UserLoggedOut $event): AuthAuditEvent
    {
        return new AuthAuditEvent(
            eventName: 'auth.user_logged_out',
            occurredAt: new DateTimeImmutable(),
            actorId: $event->user->id,
            subjectId: $event->user->id,
            email: $event->user->email,
            ipAddress: $event->ipAddress,
            userAgent: $event->userAgent,
        );
    }

    private function fromLoginFailed(LoginFailed $event): AuthAuditEvent
    {
        return new AuthAuditEvent(
            eventName: 'auth.login_failed',
            occurredAt: new DateTimeImmutable(),
            email: $event->email,
            ipAddress: $event->ipAddress,
            userAgent: $event->userAgent,
        );
    }

    private function fromSanctumTokenIssued(SanctumTokenIssued $event): AuthAuditEvent
    {
        return new AuthAuditEvent(
            eventName: 'auth.sanctum_token_issued',
            occurredAt: new DateTimeImmutable(),
            actorId: $event->user->id,
            subjectId: $event->user->id,
            email: $event->user->email,
            ipAddress: $event->ipAddress,
            userAgent: $event->userAgent,
            metadata: ['token_name' => $event->tokenName],
        );
    }

    private function fromSanctumTokenRevoked(SanctumTokenRevoked $event): AuthAuditEvent
    {
        return new AuthAuditEvent(
            eventName: 'auth.sanctum_token_revoked',
            occurredAt: new DateTimeImmutable(),
            actorId: $event->user->id,
            subjectId: $event->user->id,
            email: $event->user->email,
            ipAddress: $event->ipAddress,
            userAgent: $event->userAgent,
            metadata: ['token_name' => $event->tokenName],
        );
    }

    private function fromPasswordResetRequested(PasswordResetRequested $event): AuthAuditEvent
    {
        return new AuthAuditEvent(
            eventName: 'auth.password_reset_requested',
            occurredAt: new DateTimeImmutable(),
            email: $event->email,
            ipAddress: $event->ipAddress,
            userAgent: $event->userAgent,
        );
    }

    private function fromPasswordChanged(PasswordChanged $event): AuthAuditEvent
    {
        return new AuthAuditEvent(
            eventName: 'auth.password_changed',
            occurredAt: new DateTimeImmutable(),
            actorId: $event->user->id,
            subjectId: $event->user->id,
            email: $event->user->email,
            ipAddress: $event->ipAddress,
            userAgent: $event->userAgent,
        );
    }

    private function fromEmailVerified(EmailVerified $event): AuthAuditEvent
    {
        return new AuthAuditEvent(
            eventName: 'auth.email_verified',
            occurredAt: new DateTimeImmutable(),
            actorId: $event->user->id,
            subjectId: $event->user->id,
            email: $event->user->email,
        );
    }

    private function fromVerificationEmailResent(VerificationEmailResent $event): AuthAuditEvent
    {
        return new AuthAuditEvent(
            eventName: 'auth.verification_email_resent',
            occurredAt: new DateTimeImmutable(),
            actorId: $event->user->id,
            subjectId: $event->user->id,
            email: $event->user->email,
        );
    }
}
