<?php

declare(strict_types=1);

namespace App\Core\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:active,inactive,archived'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
