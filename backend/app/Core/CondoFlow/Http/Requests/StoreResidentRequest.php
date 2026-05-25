<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Http\Requests;

use App\Core\CondoFlow\Enums\ResidentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResidentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'name' => ['required', 'string', 'max:255'],
            'rut' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::enum(ResidentStatus::class)],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
