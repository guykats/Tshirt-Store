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

        return SystemEventResource::collection(
            SystemEvent::query()->latest()->paginate(30)
        );
    }
}
