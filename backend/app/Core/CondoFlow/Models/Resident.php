<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Models;

use App\Core\CondoFlow\Enums\ResidentStatus;
use App\Core\Tenancy\Models\Concerns\BelongsToTenant;
use Database\Factories\CondoFlow\ResidentFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Resident extends Model
{
    /** @use HasFactory<ResidentFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'unit_id',
        'name',
        'rut',
        'email',
        'phone',
        'status',
        'metadata',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    protected function casts(): array
    {
        return [
            'status' => ResidentStatus::class,
            'metadata' => 'array',
        ];
    }

    protected static function newFactory(): Factory
    {
        return ResidentFactory::new();
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(MaintenanceTicket::class);
    }
}
