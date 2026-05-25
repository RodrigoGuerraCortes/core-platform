<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Http\Resources;

use App\Core\CondoFlow\Enums\ResidentStatus;
use App\Core\CondoFlow\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Resident */
class ResidentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'unit_id' => $this->unit_id,
            'unit' => new UnitResource($this->whenLoaded('unit')),
            'name' => $this->name,
            'rut' => $this->rut,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status instanceof ResidentStatus ? $this->status->value : $this->status,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
