<?php

declare(strict_types=1);

namespace App\Core\DynamicForms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'payload' => ['required', 'array'],
        ];
    }

    /**
     * Return the typed submission payload.
     *
     * @return array<string, mixed>
     */
    public function submissionPayload(): array
    {
        return $this->input('payload', []);
    }
}
