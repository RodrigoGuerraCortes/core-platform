<?php

declare(strict_types=1);

namespace App\Core\DynamicForms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * FormVersion is immutable after creation.
 * No updates are permitted. New edits require a new version record.
 */
class FormVersion extends Model
{
    protected $table = 'dynamic_form_versions';

    protected $fillable = [
        'tenant_id',
        'form_id',
        'version_number',
        'schema',
        'schema_hash',
        'label',
        'published_at',
        'created_by',
    ];

    // FormVersion has no SoftDeletes — it must never be deleted while
    // referenced by a FormSubmission or by dynamic_forms.active_version_id.

    protected function casts(): array
    {
        return [
            'schema'       => 'array',
            'published_at' => 'datetime',
            'created_by'   => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'form_version_id');
    }

    // ─── Immutability guard ───────────────────────────────────────────────────

    /**
     * Prevent any updates to FormVersion records.
     * All mutation attempts are silent no-ops — the original data is preserved.
     *
     * Rationale: FormVersion.schema is the permanent reference for all submissions
     * that reference this version. Mutating it would corrupt historical submissions.
     */
    protected static function booted(): void
    {
        static::updating(function (): bool {
            return false;
        });
    }

    // ─── Domain helpers ───────────────────────────────────────────────────────

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    /**
     * Mark this version as published. Updates only the published_at timestamp.
     * Uses a raw query to bypass the immutability guard on `updating`.
     */
    public function markPublished(): void
    {
        $this->timestamps = false;
        static::withoutEvents(function () {
            $this->getConnection()
                ->table($this->getTable())
                ->where('id', $this->id)
                ->update(['published_at' => now()]);
        });
        $this->published_at = now();
    }
}
