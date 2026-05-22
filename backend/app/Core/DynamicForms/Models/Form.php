<?php

declare(strict_types=1);

namespace App\Core\DynamicForms\Models;

use App\Core\DynamicForms\Enums\FormStatus;
use App\Core\Tenancy\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Form extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'dynamic_forms';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'active_version_id',
        'status',
        'metadata',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'status'   => FormStatus::class,
            'metadata' => 'array',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function versions(): HasMany
    {
        return $this->hasMany(FormVersion::class, 'form_id');
    }

    public function activeVersion(): BelongsTo
    {
        return $this->belongsTo(FormVersion::class, 'active_version_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'form_id');
    }

    // ─── Domain helpers ───────────────────────────────────────────────────────

    public function isPublished(): bool
    {
        return $this->status === FormStatus::Active && $this->active_version_id !== null;
    }

    public function isArchived(): bool
    {
        return $this->status === FormStatus::Archived;
    }

    public function canAcceptSubmissions(): bool
    {
        return $this->status->canAcceptSubmissions() && $this->active_version_id !== null;
    }

    /**
     * Publish a specific version: set it as active, mark form as active.
     * This is the only mutation permitted on a published form outside of archiving.
     */
    public function publishVersion(FormVersion $version): void
    {
        $this->active_version_id = $version->id;
        $this->status = FormStatus::Active;
        $this->save();
    }

    public function archive(): void
    {
        $this->status = FormStatus::Archived;
        $this->save();
    }
}
