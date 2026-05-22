<?php

declare(strict_types=1);

namespace App\Core\DynamicForms\Models;

use App\Core\Tenancy\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FormSubmission is immutable after creation.
 * No update or delete operations are permitted.
 *
 * The payload field stores the cleaned, validated submission data
 * exactly as submitted. The referenced FormVersion.schema is the
 * permanent key for interpreting the payload.
 */
class FormSubmission extends Model
{
    use BelongsToTenant;

    protected $table = 'dynamic_form_submissions';

    protected $fillable = [
        'tenant_id',
        'form_id',
        'form_version_id',
        'submitted_by',
        'payload',
        'metadata',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'payload'      => 'array',
            'metadata'     => 'array',
            'submitted_at' => 'datetime',
            'submitted_by' => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(FormVersion::class, 'form_version_id');
    }

    // ─── Immutability guard ───────────────────────────────────────────────────

    /**
     * Prevent any updates to FormSubmission records.
     * Submissions are an immutable audit record of what was submitted
     * at a specific point in time against a specific schema version.
     */
    protected static function booted(): void
    {
        static::updating(function (): bool {
            return false;
        });

        static::deleting(function (): bool {
            return false;
        });
    }
}
