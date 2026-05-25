<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Http\Resources;

use App\Core\CondoFlow\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Building */
class BuildingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'address' => $this->address,
            'floors' => $this->floors,
            'units_count' => $this->whenCounted('units'),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
