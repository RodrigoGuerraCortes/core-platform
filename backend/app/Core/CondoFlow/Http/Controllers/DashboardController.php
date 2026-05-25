<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Http\Controllers;

use App\Core\CondoFlow\Models\Building;
use App\Core\CondoFlow\Models\MaintenanceTicket;
use App\Core\CondoFlow\Models\Resident;
use App\Core\CondoFlow\Models\Unit;
use Illuminate\Http\JsonResponse;

class DashboardController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'buildings_count' => Building::count(),
                'units_count' => Unit::count(),
                'residents_count' => Resident::count(),
                'open_tickets_count' => MaintenanceTicket::where('status', 'open')->count(),
                'in_progress_tickets_count' => MaintenanceTicket::where('status', 'in_progress')->count(),
                'tickets_by_priority' => [
                    'high' => MaintenanceTicket::where('priority', 'high')
                        ->whereIn('status', ['open', 'in_progress'])->count(),
                    'medium' => MaintenanceTicket::where('priority', 'medium')
                        ->whereIn('status', ['open', 'in_progress'])->count(),
                    'low' => MaintenanceTicket::where('priority', 'low')
                        ->whereIn('status', ['open', 'in_progress'])->count(),
                ],
                'recent_tickets' => MaintenanceTicket::with(['unit', 'resident'])
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(fn ($t) => [
                        'id' => $t->id,
                        'title' => $t->title,
                        'status' => $t->status->value ?? $t->status,
                        'priority' => $t->priority->value ?? $t->priority,
                        'created_at' => $t->created_at?->toISOString(),
                    ]),
            ],
        ]);
    }
}
