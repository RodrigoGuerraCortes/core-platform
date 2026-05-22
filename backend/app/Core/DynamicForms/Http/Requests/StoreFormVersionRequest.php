<?php

declare(strict_types=1);

namespace App\Core\DynamicForms\Http\Requests;

use App\Core\DynamicForms\Validation\FormSchemaValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreFormVersionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'schema'       => ['required', 'array'],
            'schema.version' => ['required', 'integer'],
            'schema.title'   => ['required', 'string', 'max:255'],
            'schema.fields'  => ['required', 'array'],
            'label'        => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * After standard validation, run the domain schema validator.
     * Errors are merged back into the validator error bag with dot-notation paths.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return; // Don't run domain validator if basic structure is already invalid
            }

            $schemaErrors = (new FormSchemaValidator())->validate($this->input('schema', []));

            foreach ($schemaErrors as $field => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($field, $message);
                }
            }
        });
    }
}
