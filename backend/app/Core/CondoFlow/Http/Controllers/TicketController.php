<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Http\Controllers;

use App\Core\CondoFlow\Http\Requests\StoreTicketRequest;
use App\Core\CondoFlow\Http\Requests\UpdateTicketRequest;
use App\Core\CondoFlow\Http\Resources\TicketResource;
use App\Core\CondoFlow\Models\MaintenanceTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class TicketController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', MaintenanceTicket::class);

        $query = MaintenanceTicket::with(['unit', 'resident']);

        if ($search = $request->query('search')) {
            $query->where('title', 'ilike', "%{$search}%");
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->query('priority')) {
            $query->where('priority', $priority);
        }

        $sortField = $request->query('sort', 'created_at');
        $sortDir = $request->query('direction', 'desc');
        $query->orderBy($sortField, $sortDir);

        return TicketResource::collection(
            $query->paginate($request->integer('per_page', 15))
        );
    }

    public function show(MaintenanceTicket $ticket): TicketResource
    {
        Gate::authorize('view', $ticket);
        $ticket->load(['unit', 'resident']);
        return new TicketResource($ticket);
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        Gate::authorize('create', MaintenanceTicket::class);
        $ticket = MaintenanceTicket::create($request->validated());
        return (new TicketResource($ticket))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateTicketRequest $request, MaintenanceTicket $ticket): TicketResource
    {
        Gate::authorize('update', $ticket);
        $ticket->update($request->validated());
        return new TicketResource($ticket);
    }

    public function destroy(MaintenanceTicket $ticket): Response
    {
        Gate::authorize('delete', $ticket);
        $ticket->delete();
        return response()->noContent();
    }
}
