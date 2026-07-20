<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SystemEventResource;
use App\Models\SystemEvent;
use Illuminate\Http\Request;

class SystemEventController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $events = SystemEvent::query()
            ->when($request->query('event_type'), fn ($q, $eventType) => $q->where('event_type', $eventType))
            ->when($request->query('actor_type'), fn ($q, $actorType) => $q->where('actor_type', $actorType))
            // whereDate() compiles to DATE(created_at) on both SQLite and MySQL, so this
            // stays portable rather than reaching for a MySQL-only date function.
            ->when($request->query('date_from'), fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($request->query('date_to'), fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->when($request->query('search'), fn ($q, $search) => $q->where('description', 'like', '%'.$search.'%'))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return SystemEventResource::collection($events);
    }
}
