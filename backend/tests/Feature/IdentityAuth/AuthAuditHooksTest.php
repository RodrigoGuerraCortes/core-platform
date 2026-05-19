<?php

declare(strict_types=1);

use App\Core\IdentityAuth\Audit\AuthAuditEvent;
use App\Core\IdentityAuth\Audit\AuthAuditPayloadFactory;
use App\Core\IdentityAuth\Audit\AuthAuditSink;
use App\Core\IdentityAuth\Audit\NullAuthAuditSink;
use App\Core\IdentityAuth\Events\EmailVerified;
use App\Core\IdentityAuth\Events\LoginFailed;
use App\Core\IdentityAuth\Events\PasswordResetRequested;
use App\Core\IdentityAuth\Events\SanctumTokenIssued;
use App\Core\IdentityAuth\Events\UserLoggedIn;
use App\Models\User;

// A recording sink that captures what was passed to it.
// Swapped into the container before each test so the real listener uses it.
function makeFakeAuditSink(): AuthAuditSink
{
    return new class implements AuthAuditSink {
        /** @var AuthAuditEvent[] */
        public array $recorded = [];

        public function record(AuthAuditEvent $event): void
        {
            $this->recorded[] = $event;
        }
    };
}

beforeEach(function (): void {
    $this->fakeSink = makeFakeAuditSink();
    app()->instance(AuthAuditSink::class, $this->fakeSink);
});

test('UserLoggedIn is converted into audit payload and sent to sink', function (): void {
    $user = User::factory()->create();

    event(new UserLoggedIn($user, '127.0.0.1', 'TestAgent/1.0'));

    expect($this->fakeSink->recorded)->toHaveCount(1);

    $payload = $this->fakeSink->recorded[0];
    expect($payload->eventName)->toBe('auth.user_logged_in');
    expect($payload->actorId)->toBe($user->id);
    expect($payload->subjectId)->toBe($user->id);
    expect($payload->email)->toBe($user->email);
    expect($payload->ipAddress)->toBe('127.0.0.1');
});

test('LoginFailed audit payload does not contain password data', function (): void {
    event(new LoginFailed('victim@example.com', '10.0.0.1', null));

    expect($this->fakeSink->recorded)->toHaveCount(1);

    $payload = $this->fakeSink->recorded[0];
    expect($payload->eventName)->toBe('auth.login_failed');
    expect($payload->email)->toBe('victim@example.com');
    expect($payload->actorId)->toBeNull();

    // Metadata must not carry any password-related keys
    expect($payload->metadata)->not->toHaveKey('password');
    expect($payload->metadata)->not->toHaveKey('raw_password');
});

test('SanctumTokenIssued audit payload does not contain plain token', function (): void {
    $user = User::factory()->create();

    event(new SanctumTokenIssued($user, 'my-api-client', '127.0.0.1', null));

    expect($this->fakeSink->recorded)->toHaveCount(1);

    $payload = $this->fakeSink->recorded[0];
    expect($payload->eventName)->toBe('auth.sanctum_token_issued');

    // token_name (the label) is safe and expected
    expect($payload->metadata)->toHaveKey('token_name');
    expect($payload->metadata['token_name'])->toBe('my-api-client');

    // Raw secret token must never appear in the payload
    expect($payload->metadata)->not->toHaveKey('token');
    expect($payload->metadata)->not->toHaveKey('plain_token');
    expect($payload->metadata)->not->toHaveKey('secret');
    expect($payload->metadata)->not->toHaveKey('raw_token');
});

test('PasswordResetRequested audit payload does not contain reset token', function (): void {
    event(new PasswordResetRequested('reset@example.com', '192.168.1.1', null));

    expect($this->fakeSink->recorded)->toHaveCount(1);

    $payload = $this->fakeSink->recorded[0];
    expect($payload->eventName)->toBe('auth.password_reset_requested');
    expect($payload->email)->toBe('reset@example.com');

    // Reset tokens must never appear in the payload
    expect($payload->metadata)->not->toHaveKey('token');
    expect($payload->metadata)->not->toHaveKey('reset_token');
});

test('EmailVerified is converted into audit payload', function (): void {
    $user = User::factory()->create();

    event(new EmailVerified($user));

    expect($this->fakeSink->recorded)->toHaveCount(1);

    $payload = $this->fakeSink->recorded[0];
    expect($payload->eventName)->toBe('auth.email_verified');
    expect($payload->subjectId)->toBe($user->id);
    expect($payload->email)->toBe($user->email);
});

test('unsupported event type is ignored safely by the factory', function (): void {
    $factory = app(AuthAuditPayloadFactory::class);

    $result = $factory->fromEvent(new stdClass());

    expect($result)->toBeNull();
});

test('NullAuthAuditSink records nothing and does not throw', function (): void {
    $sink = new NullAuthAuditSink();

    $auditEvent = new AuthAuditEvent(
        eventName: 'auth.test_event',
        occurredAt: new DateTimeImmutable(),
        actorId: 1,
        email: 'noop@example.com',
    );

    // Must not throw — simply a no-op
    $sink->record($auditEvent);

    expect(true)->toBeTrue();
});
