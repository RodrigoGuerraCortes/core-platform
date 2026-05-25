<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Http\Resources;

use App\Core\CondoFlow\Enums\TicketPriority;
use App\Core\CondoFlow\Enums\TicketStatus;
use App\Core\CondoFlow\Models\MaintenanceTicket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MaintenanceTicket */
class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'unit_id' => $this->unit_id,
            'resident_id' => $this->resident_id,
            'unit' => new UnitResource($this->whenLoaded('unit')),
            'resident' => new ResidentResource($this->whenLoaded('resident')),
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status instanceof TicketStatus ? $this->status->value : $this->status,
            'priority' => $this->priority instanceof TicketPriority ? $this->priority->value : $this->priority,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
