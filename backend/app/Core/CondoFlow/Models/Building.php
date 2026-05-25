<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Models;

use App\Core\CondoFlow\Enums\UnitStatus;
use App\Core\CondoFlow\Enums\UnitType;
use App\Core\Tenancy\Models\Concerns\BelongsToTenant;
use Database\Factories\CondoFlow\BuildingFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Building extends Model
{
    /** @use HasFactory<BuildingFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'address',
        'floors',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'floors' => 'integer',
            'metadata' => 'array',
        ];
    }

    protected static function newFactory(): Factory
    {
        return BuildingFactory::new();
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }
}
