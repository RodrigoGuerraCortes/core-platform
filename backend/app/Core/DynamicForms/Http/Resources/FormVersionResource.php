<?php

declare(strict_types=1);

namespace App\Core\DynamicForms\Http\Resources;

use App\Core\DynamicForms\Models\FormVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FormVersion */
class FormVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'form_id'        => $this->form_id,
            'tenant_id'      => $this->tenant_id,
            'version_number' => $this->version_number,
            'label'          => $this->label,
            'schema'         => $this->schema,
            'schema_hash'    => $this->schema_hash,
            'published_at'   => $this->published_at?->toISOString(),
            'created_by'     => $this->created_by,
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}
