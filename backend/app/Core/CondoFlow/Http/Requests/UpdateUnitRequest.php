<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Http\Requests;

use App\Core\CondoFlow\Enums\UnitStatus;
use App\Core\CondoFlow\Enums\UnitType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'building_id' => ['sometimes', 'integer', 'exists:buildings,id'],
            'number' => ['sometimes', 'string', 'max:50'],
            'floor' => ['sometimes', 'integer', 'min:0', 'max:200'],
            'type' => ['sometimes', Rule::enum(UnitType::class)],
            'status' => ['sometimes', Rule::enum(UnitStatus::class)],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
