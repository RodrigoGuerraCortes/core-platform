<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Models;

use App\Core\CondoFlow\Enums\TicketPriority;
use App\Core\CondoFlow\Enums\TicketStatus;
use App\Core\Tenancy\Models\Concerns\BelongsToTenant;
use Database\Factories\CondoFlow\MaintenanceTicketFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceTicket extends Model
{
    /** @use HasFactory<MaintenanceTicketFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $table = 'maintenance_tickets';

    protected $fillable = [
        'tenant_id',
        'unit_id',
        'resident_id',
        'title',
        'description',
        'status',
        'priority',
        'metadata',
    ];

    protected $attributes = [
        'status' => 'open',
        'priority' => 'medium',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'metadata' => 'array',
        ];
    }

    protected static function newFactory(): Factory
    {
        return MaintenanceTicketFactory::new();
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }
}
