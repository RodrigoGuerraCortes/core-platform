<?php

declare(strict_types=1);

namespace App\Core\Projects\Http\Controllers;

use App\Core\Projects\Http\Requests\StoreProjectRequest;
use App\Core\Projects\Http\Requests\UpdateProjectRequest;
use App\Core\Projects\Http\Resources\ProjectResource;
use App\Core\Projects\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ProjectController
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Project::class);

        return ProjectResource::collection(Project::paginate(15));
    }

    public function show(Project $project): ProjectResource
    {
        Gate::authorize('view', $project);

        return new ProjectResource($project);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        Gate::authorize('create', Project::class);

        // tenant_id is NOT in validated() — it is auto-filled by BelongsToTenant::creating()
        // from TenantContextContract. User-supplied tenant_id is never accepted.
        $project = Project::create($request->validated());

        return (new ProjectResource($project))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        Gate::authorize('update', $project);

        $project->update($request->validated());

        return new ProjectResource($project);
    }

    public function destroy(Project $project): Response
    {
        Gate::authorize('delete', $project);

        $project->delete();

        return response()->noContent();
    }
}
