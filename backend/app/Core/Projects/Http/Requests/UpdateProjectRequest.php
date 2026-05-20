<?php

declare(strict_types=1);

namespace App\Core\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'string', 'in:active,inactive,archived'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
