<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBuildingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'floors' => ['nullable', 'integer', 'min:1', 'max:200'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
