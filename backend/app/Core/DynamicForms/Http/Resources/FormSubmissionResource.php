<?php

declare(strict_types=1);

namespace App\Core\DynamicForms\Http\Resources;

use App\Core\DynamicForms\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FormSubmission */
class FormSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'form_id'         => $this->form_id,
            'form_version_id' => $this->form_version_id,
            'tenant_id'       => $this->tenant_id,
            'submitted_by'    => $this->submitted_by,
            'payload'         => $this->payload,
            'metadata'        => $this->metadata,
            'submitted_at'    => $this->submitted_at?->toISOString(),
            'created_at'      => $this->created_at?->toISOString(),
        ];
    }
}
