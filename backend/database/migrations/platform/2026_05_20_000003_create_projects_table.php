<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // tenant_id index enables efficient per-tenant lookups.
            // TenantScope always adds WHERE tenant_id = ? so this is load-bearing.
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
