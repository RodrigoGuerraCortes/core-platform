<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Http\Controllers;

use App\Core\CondoFlow\Http\Requests\StoreBuildingRequest;
use App\Core\CondoFlow\Http\Requests\UpdateBuildingRequest;
use App\Core\CondoFlow\Http\Resources\BuildingResource;
use App\Core\CondoFlow\Models\Building;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class BuildingController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Building::class);

        $query = Building::withCount('units');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('address', 'ilike', "%{$search}%");
            });
        }

        $sortField = $request->query('sort', 'created_at');
        $sortDir = $request->query('direction', 'desc');
        $query->orderBy($sortField, $sortDir);

        return BuildingResource::collection(
            $query->paginate($request->integer('per_page', 15))
        );
    }

    public function show(Building $building): BuildingResource
    {
        Gate::authorize('view', $building);
        $building->loadCount('units');
        return new BuildingResource($building);
    }

    public function store(StoreBuildingRequest $request): JsonResponse
    {
        Gate::authorize('create', Building::class);
        $building = Building::create($request->validated());
        return (new BuildingResource($building))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateBuildingRequest $request, Building $building): BuildingResource
    {
        Gate::authorize('update', $building);
        $building->update($request->validated());
        return new BuildingResource($building);
    }

    public function destroy(Building $building): Response
    {
        Gate::authorize('delete', $building);
        $building->delete();
        return response()->noContent();
    }
}
