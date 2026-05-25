<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Http\Requests;

use App\Core\CondoFlow\Enums\TicketPriority;
use App\Core\CondoFlow\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'unit_id' => ['sometimes', 'nullable', 'integer', 'exists:units,id'],
            'resident_id' => ['sometimes', 'nullable', 'integer', 'exists:residents,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', Rule::enum(TicketStatus::class)],
            'priority' => ['sometimes', Rule::enum(TicketPriority::class)],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
