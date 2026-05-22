<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Models;

use App\Models\User;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, SoftDeletes;

    protected static function newFactory(): Factory
    {
        return TenantFactory::new();
    }

    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'slug',
        'metadata',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'settings' => 'array',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->withPivot('membership_role')
            ->withTimestamps();
    }
}
