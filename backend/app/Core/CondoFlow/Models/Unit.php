<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Models;

use App\Core\CondoFlow\Enums\UnitStatus;
use App\Core\CondoFlow\Enums\UnitType;
use App\Core\Tenancy\Models\Concerns\BelongsToTenant;
use Database\Factories\CondoFlow\UnitFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'building_id',
        'number',
        'floor',
        'type',
        'status',
        'metadata',
    ];

    protected $attributes = [
        'status' => 'available',
        'type' => 'apartment',
    ];

    protected function casts(): array
    {
        return [
            'floor' => 'integer',
            'type' => UnitType::class,
            'status' => UnitStatus::class,
            'metadata' => 'array',
        ];
    }

    protected static function newFactory(): Factory
    {
        return UnitFactory::new();
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function residents(): HasMany
    {
        return $this->hasMany(Resident::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(MaintenanceTicket::class);
    }
}
