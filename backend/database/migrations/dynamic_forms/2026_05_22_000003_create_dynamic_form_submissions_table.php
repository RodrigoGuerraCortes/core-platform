<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dynamic_form_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('form_id')->constrained('dynamic_forms')->cascadeOnDelete();
            $table->foreignId('form_version_id')->constrained('dynamic_form_versions')->restrictOnDelete();
            $table->unsignedBigInteger('submitted_by')->nullable(); // user.id — no FK (users may be deleted)
            $table->json('payload');
            $table->json('metadata')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('form_id');
            $table->index('form_version_id');
            $table->index('submitted_by');
            $table->index('submitted_at');
            $table->index(['form_id', 'submitted_by']);  // duplicate submission guard query
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dynamic_form_submissions');
    }
};
