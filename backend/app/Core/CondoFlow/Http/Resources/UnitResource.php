<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Http\Resources;

use App\Core\CondoFlow\Enums\UnitStatus;
use App\Core\CondoFlow\Enums\UnitType;
use App\Core\CondoFlow\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Unit */
class UnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'building_id' => $this->building_id,
            'building' => new BuildingResource($this->whenLoaded('building')),
            'number' => $this->number,
            'floor' => $this->floor,
            'type' => $this->type instanceof UnitType ? $this->type->value : $this->type,
            'status' => $this->status instanceof UnitStatus ? $this->status->value : $this->status,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
