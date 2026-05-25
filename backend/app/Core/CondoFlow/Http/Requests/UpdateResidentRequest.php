<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Http\Requests;

use App\Core\CondoFlow\Enums\ResidentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateResidentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'unit_id' => ['sometimes', 'nullable', 'integer', 'exists:units,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'rut' => ['sometimes', 'nullable', 'string', 'max:20'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'status' => ['sometimes', Rule::enum(ResidentStatus::class)],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
