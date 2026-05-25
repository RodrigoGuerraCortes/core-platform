<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBuildingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'floors' => ['sometimes', 'integer', 'min:1', 'max:200'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
