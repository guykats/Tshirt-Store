<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DesignResource;
use App\Models\Design;
use Illuminate\Http\Request;

class DesignController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Design::class);

        return DesignResource::collection(
            Design::query()
                ->with('approvedBy')
                ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
                ->latest()
                ->paginate(20)
        );
    }

    public function approve(Request $request, Design $design)
    {
        $this->authorize('update', $design);

        $design->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return new DesignResource($design->fresh('approvedBy'));
    }

    public function reject(Request $request, Design $design)
    {
        $this->authorize('update', $design);

        $data = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $design->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => $data['rejection_reason'] ?? null,
        ]);

        return new DesignResource($design->fresh('approvedBy'));
    }
}
