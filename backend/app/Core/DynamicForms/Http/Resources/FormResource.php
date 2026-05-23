<?php

declare(strict_types=1);

namespace App\Core\DynamicForms\Http\Resources;

use App\Core\DynamicForms\Enums\FormStatus;
use App\Core\DynamicForms\Http\Resources\FormVersionResource;
use App\Core\DynamicForms\Models\Form;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Form */
class FormResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'tenant_id'         => $this->tenant_id,
            'name'              => $this->name,
            'slug'              => $this->slug,
            'description'       => $this->description,
            'status'            => $this->status instanceof FormStatus ? $this->status->value : $this->status,
            'active_version_id' => $this->active_version_id,
            'active_version'    => $this->whenLoaded('activeVersion', fn () => new FormVersionResource($this->activeVersion)),
            'metadata'          => $this->metadata,
            'created_at'        => $this->created_at?->toISOString(),
            'updated_at'        => $this->updated_at?->toISOString(),
        ];
    }
}
