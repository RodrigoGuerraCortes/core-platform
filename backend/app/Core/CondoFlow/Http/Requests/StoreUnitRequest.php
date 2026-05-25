<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Http\Requests;

use App\Core\CondoFlow\Enums\UnitStatus;
use App\Core\CondoFlow\Enums\UnitType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'building_id' => ['required', 'integer', 'exists:buildings,id'],
            'number' => ['required', 'string', 'max:50'],
            'floor' => ['nullable', 'integer', 'min:0', 'max:200'],
            'type' => ['nullable', Rule::enum(UnitType::class)],
            'status' => ['nullable', Rule::enum(UnitStatus::class)],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
