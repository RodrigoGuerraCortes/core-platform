<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Http\Requests;

use App\Core\CondoFlow\Enums\TicketPriority;
use App\Core\CondoFlow\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'resident_id' => ['nullable', 'integer', 'exists:residents,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::enum(TicketStatus::class)],
            'priority' => ['nullable', Rule::enum(TicketPriority::class)],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
