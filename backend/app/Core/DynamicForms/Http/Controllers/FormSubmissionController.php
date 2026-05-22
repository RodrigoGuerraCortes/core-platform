<?php

declare(strict_types=1);

namespace App\Core\DynamicForms\Http\Controllers;

use App\Core\DynamicForms\Http\Requests\SubmitFormRequest;
use App\Core\DynamicForms\Http\Resources\FormSubmissionResource;
use App\Core\DynamicForms\Models\Form;
use App\Core\DynamicForms\Models\FormSubmission;
use App\Core\DynamicForms\Validation\FormSubmissionValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class FormSubmissionController
{
    public function __construct(
        private readonly FormSubmissionValidator $submissionValidator,
    ) {}

    public function index(Form $form): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', [FormSubmission::class, $form]);

        $submissions = FormSubmission::where('form_id', $form->id)
            ->orderByDesc('submitted_at')
            ->paginate(15);

        return FormSubmissionResource::collection($submissions);
    }

    public function show(FormSubmission $submission): FormSubmissionResource
    {
        Gate::authorize('view', $submission);

        return new FormSubmissionResource($submission);
    }

    public function submit(SubmitFormRequest $request, Form $form): JsonResponse
    {
        Gate::authorize('create', [FormSubmission::class, $form]);

        // Pre-condition: form must be active and have a published version
        if (! $form->canAcceptSubmissions()) {
            $status = $form->status->value;
            abort(410, "This form is {$status} and is not accepting submissions.");
        }

        $version = $form->activeVersion;

        if ($version === null) {
            abort(422, 'This form has no active version.');
        }

        $rawPayload = $request->submissionPayload();

        // Strip keys not in the schema (silent, per spec)
        $schemaKeys   = collect($version->schema['fields'] ?? [])
            ->pluck('key')
            ->filter()
            ->all();
        $cleanPayload = array_intersect_key($rawPayload, array_flip($schemaKeys));

        // Layer 3: authoritative server-side validation
        $errors = $this->submissionValidator->validate($cleanPayload, $version);

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        // Duplicate submission guard
        $userId = $request->user()?->id;
        $allowMultiple = (bool) ($version->schema['settings']['allow_multiple_submissions'] ?? true);

        if (! $allowMultiple && $userId !== null) {
            $alreadySubmitted = FormSubmission::where('form_id', $form->id)
                ->where('submitted_by', $userId)
                ->exists();

            if ($alreadySubmitted) {
                abort(409, 'You have already submitted this form.');
            }
        }

        $submission = DB::transaction(function () use ($form, $version, $cleanPayload, $request, $userId): FormSubmission {
            return FormSubmission::create([
                'form_id'         => $form->id,
                'form_version_id' => $version->id,
                'submitted_by'    => $userId,
                'payload'         => $cleanPayload,
                'metadata'        => [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'source'     => 'api',
                ],
                'submitted_at'    => now(),
            ]);
        });

        return (new FormSubmissionResource($submission))
            ->response()
            ->setStatusCode(201);
    }
}
