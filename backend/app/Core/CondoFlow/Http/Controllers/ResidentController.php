<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Http\Controllers;

use App\Core\CondoFlow\Http\Requests\StoreResidentRequest;
use App\Core\CondoFlow\Http\Requests\UpdateResidentRequest;
use App\Core\CondoFlow\Http\Resources\ResidentResource;
use App\Core\CondoFlow\Models\Resident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ResidentController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Resident::class);

        $query = Resident::with('unit');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('rut', 'ilike', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $sortField = $request->query('sort', 'name');
        $sortDir = $request->query('direction', 'asc');
        $query->orderBy($sortField, $sortDir);

        return ResidentResource::collection(
            $query->paginate($request->integer('per_page', 15))
        );
    }

    public function show(Resident $resident): ResidentResource
    {
        Gate::authorize('view', $resident);
        $resident->load('unit');
        return new ResidentResource($resident);
    }

    public function store(StoreResidentRequest $request): JsonResponse
    {
        Gate::authorize('create', Resident::class);
        $resident = Resident::create($request->validated());
        return (new ResidentResource($resident))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateResidentRequest $request, Resident $resident): ResidentResource
    {
        Gate::authorize('update', $resident);
        $resident->update($request->validated());
        return new ResidentResource($resident);
    }

    public function destroy(Resident $resident): Response
    {
        Gate::authorize('delete', $resident);
        $resident->delete();
        return response()->noContent();
    }
}
