<?php

declare(strict_types=1);

use function Pest\Laravel\get;

describe('InjectRequestId Middleware', function () {
    it('generates X-Request-ID when not provided', function () {
        $response = get('/health');

        $response->assertHeader('X-Request-ID');
        $requestId = $response->headers->get('X-Request-ID');
        expect($requestId)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i');
    });

    it('preserves X-Request-ID when provided by the client', function () {
        $customId = 'custom-correlation-id-123';

        $response = get('/health', ['X-Request-ID' => $customId]);

        $response->assertHeader('X-Request-ID', $customId);
    });
});

describe('Health Endpoints', function () {
    it('returns 200 OK from /health', function () {
        $response = get('/health');

        $response->assertOk()
            ->assertJsonStructure(['status', 'timestamp'])
            ->assertJson(['status' => 'ok']);
    });

    it('returns detailed health check from /health/detailed', function () {
        $response = get('/health/detailed');

        $response->assertJsonStructure([
            'status',
            'timestamp',
            'environment',
            'checks' => ['database', 'cache', 'queue', 'storage'],
        ]);
    });
});
