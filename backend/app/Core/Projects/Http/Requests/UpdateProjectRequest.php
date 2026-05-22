<?php

declare(strict_types=1);

namespace App\Core\Projects\Http\Requests;

use App\Core\Projects\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', Rule::enum(ProjectStatus::class)],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
