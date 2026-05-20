<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_user', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Enum enforces valid values at the DB level (MySQL/PostgreSQL).
            // SQLite (used in tests) stores as VARCHAR — application-level validation
            // must guard valid values when SQLite is the runtime.
            $table->enum('membership_role', ['owner', 'admin', 'member'])->default('member');
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id']);
            // The composite unique index satisfies tenant_id-only lookups via the
            // leftmost-prefix rule (MySQL/Postgres), so a standalone tenant_id index
            // would be redundant and has been intentionally omitted.
            // user_id index is retained for efficient reverse lookups (all tenants for a user).
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_user');
    }
};
