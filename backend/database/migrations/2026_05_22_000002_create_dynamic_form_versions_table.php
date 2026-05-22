<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dynamic_form_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('form_id')->constrained('dynamic_forms')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->json('schema');
            $table->string('schema_hash', 64);     // SHA-256 of schema JSON for integrity checks
            $table->string('label')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); // user.id — no FK (users may be deleted)
            $table->timestamps();

            $table->unique(['form_id', 'version_number']);
            $table->index('form_id');
            $table->index('tenant_id');
        });

        // Now that dynamic_form_versions exists, add the FK constraint for active_version_id.
        Schema::table('dynamic_forms', function (Blueprint $table): void {
            $table->foreign('active_version_id')
                ->references('id')
                ->on('dynamic_form_versions')
                ->nullOnDelete();

            $table->index('active_version_id');
        });
    }

    public function down(): void
    {
        Schema::table('dynamic_forms', function (Blueprint $table): void {
            $table->dropForeign(['active_version_id']);
            $table->dropIndex(['active_version_id']);
        });

        Schema::dropIfExists('dynamic_form_versions');
    }
};
