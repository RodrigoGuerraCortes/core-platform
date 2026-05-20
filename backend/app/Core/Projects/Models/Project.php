<?php

declare(strict_types=1);

namespace App\Core\Projects\Models;

use App\Core\Projects\Enums\ProjectStatus;
use App\Core\Tenancy\Models\Concerns\BelongsToTenant;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected static function newFactory(): Factory
    {
        return ProjectFactory::new();
    }

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'status',
        'metadata',
    ];

    // Model-level default mirrors the DB column default.
    // Without this, Project::create(['name' => ...]) without an explicit status
    // would leave $project->status as null in memory (the DB default doesn't
    // propagate back to the in-memory model after INSERT).
    protected $attributes = [
        'status' => 'active',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'metadata' => 'array',
        ];
    }
}
