<?php

declare(strict_types=1);

namespace App\Core\DynamicForms\Http\Controllers;

use App\Core\DynamicForms\Http\Requests\StoreFormRequest;
use App\Core\DynamicForms\Http\Requests\UpdateFormRequest;
use App\Core\DynamicForms\Http\Resources\FormResource;
use App\Core\DynamicForms\Models\Form;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FormController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Form::class);

        $query = Form::query();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return FormResource::collection($query->paginate(15));
    }

    public function show(Form $form): FormResource
    {
        Gate::authorize('view', $form);

        return new FormResource($form);
    }

    public function store(StoreFormRequest $request): JsonResponse
    {
        Gate::authorize('create', Form::class);

        $form = Form::create($request->validated());

        return (new FormResource($form))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateFormRequest $request, Form $form): FormResource
    {
        Gate::authorize('update', $form);

        $form->update($request->validated());

        return new FormResource($form->fresh());
    }

    public function publish(Form $form): FormResource
    {
        Gate::authorize('publish', $form);

        // Resolve the latest version to publish
        $version = $form->versions()->orderByDesc('version_number')->first();

        if ($version === null) {
            abort(422, 'This form has no versions to publish.');
        }

        $fields = $version->schema['fields'] ?? [];
        $hasNonSection = collect($fields)->contains(
            fn (array $f): bool => ($f['type'] ?? '') !== 'section'
        );

        if (! $hasNonSection) {
            abort(422, 'A form must have at least one non-section field before it can be published.');
        }

        \DB::transaction(function () use ($form, $version): void {
            $version->markPublished();
            $form->publishVersion($version);
        });

        return new FormResource($form->fresh());
    }

    public function archive(Form $form): FormResource
    {
        Gate::authorize('archive', $form);

        $form->archive();

        return new FormResource($form->fresh());
    }
}
