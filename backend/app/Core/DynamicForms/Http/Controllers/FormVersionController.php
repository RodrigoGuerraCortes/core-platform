<?php

declare(strict_types=1);

namespace App\Core\DynamicForms\Http\Controllers;

use App\Core\DynamicForms\Http\Requests\StoreFormVersionRequest;
use App\Core\DynamicForms\Http\Resources\FormVersionResource;
use App\Core\DynamicForms\Models\Form;
use App\Core\DynamicForms\Models\FormVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class FormVersionController
{
    public function index(Form $form): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', [FormVersion::class, $form]);

        $versions = $form->versions()
            ->orderByDesc('version_number')
            ->paginate(15);

        return FormVersionResource::collection($versions);
    }

    public function show(FormVersion $version): FormVersionResource
    {
        Gate::authorize('view', $version);

        return new FormVersionResource($version);
    }

    public function store(StoreFormVersionRequest $request, Form $form): JsonResponse
    {
        Gate::authorize('create', [FormVersion::class, $form]);

        $schema = $request->input('schema');

        // Version number: use DB-level max + 1 (UNIQUE constraint is the race-condition guard)
        $nextVersion = ($form->versions()->max('version_number') ?? 0) + 1;

        $version = FormVersion::create([
            'tenant_id'      => $form->tenant_id,
            'form_id'        => $form->id,
            'version_number' => $nextVersion,
            'schema'         => $schema,
            'schema_hash'    => hash('sha256', json_encode($schema)),
            'label'          => $request->input('label'),
            'created_by'     => $request->user()?->id,
        ]);

        return (new FormVersionResource($version))
            ->response()
            ->setStatusCode(201);
    }
}
