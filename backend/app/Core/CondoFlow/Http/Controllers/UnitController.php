<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Http\Controllers;

use App\Core\CondoFlow\Http\Requests\StoreUnitRequest;
use App\Core\CondoFlow\Http\Requests\UpdateUnitRequest;
use App\Core\CondoFlow\Http\Resources\UnitResource;
use App\Core\CondoFlow\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class UnitController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Unit::class);

        $query = Unit::with('building');

        if ($search = $request->query('search')) {
            $query->where('number', 'ilike', "%{$search}%");
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($buildingId = $request->query('building_id')) {
            $query->where('building_id', $buildingId);
        }

        $sortField = $request->query('sort', 'number');
        $sortDir = $request->query('direction', 'asc');
        $query->orderBy($sortField, $sortDir);

        return UnitResource::collection(
            $query->paginate($request->integer('per_page', 15))
        );
    }

    public function show(Unit $unit): UnitResource
    {
        Gate::authorize('view', $unit);
        $unit->load('building');
        return new UnitResource($unit);
    }

    public function store(StoreUnitRequest $request): JsonResponse
    {
        Gate::authorize('create', Unit::class);
        $unit = Unit::create($request->validated());
        return (new UnitResource($unit))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateUnitRequest $request, Unit $unit): UnitResource
    {
        Gate::authorize('update', $unit);
        $unit->update($request->validated());
        return new UnitResource($unit);
    }

    public function destroy(Unit $unit): Response
    {
        Gate::authorize('delete', $unit);
        $unit->delete();
        return response()->noContent();
    }
}
