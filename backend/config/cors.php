<?php

// ------------------------------------------------------------------
// Core Platform — CORS configuration
//
// Consumed by Illuminate\Http\Middleware\HandleCors (included in
// Laravel's default global middleware stack).
//
// Security notes:
//  - allowed_origins lists ONLY the local dev frontend origin.
//  - Production origins must be supplied via environment variables.
//  - supports_credentials is false until Sanctum cookie-auth is wired.
//  - Wildcard '*' is intentionally absent on all sensitive keys.
// ------------------------------------------------------------------

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) configuration
    |--------------------------------------------------------------------------
    |
    | Routes that CORS headers are applied to. The default covers the API.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | List every origin that may call the API directly (i.e. not through the
    | Vite dev proxy). In development that is the Vite dev server.
    |
    | VITE_ORIGIN env var lets CI / staging override without code changes.
    |
    */

    'allowed_origins' => array_filter([
        'http://localhost:5173',                    // Vite dev server (host)
        env('CORS_ALLOWED_ORIGIN'),                 // staging / CI override
    ]),

    'allowed_origins_patterns' => [],

    /*
    |--------------------------------------------------------------------------
    | Allowed Headers
    |--------------------------------------------------------------------------
    |
    | Standard request headers plus the platform-specific ones:
    |   X-Tenant-Id   — tenant resolution middleware reads this
    |   Authorization — Bearer token for Sanctum / future JWT
    |
    */

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-Tenant-Id',
    ],

    'exposed_headers' => [],

    /*
    |--------------------------------------------------------------------------
    | Preflight cache
    |--------------------------------------------------------------------------
    */

    'max_age' => 0,

    /*
    |--------------------------------------------------------------------------
    | Credentials
    |--------------------------------------------------------------------------
    |
    | Must be true once Sanctum cookie-based auth is introduced.
    | Keep false until then — true + wildcard origins is a security issue.
    |
    */

    // Must be true for Sanctum SPA cookie auth (withCredentials in Axios).
    'supports_credentials' => true,

];
