<?php

declare(strict_types=1);

namespace App\Core\Projects\Models;

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

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
