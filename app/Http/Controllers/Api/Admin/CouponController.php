<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use App\Models\SystemEvent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin CRUD for coupon codes. CouponService (App\Services\CouponService) and
 * Coupon::isRedeemable() already handle validation/redemption at checkout —
 * this controller is the missing other half: the only way to get a
 * redeemable row into the coupons table without it.
 */
class CouponController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $search = trim((string) $request->query('search', ''));

        $coupons = Coupon::query()
            ->when($search !== '', fn ($query) => $query->where('code', 'like', '%'.strtoupper($search).'%'))
            ->latest()
            ->paginate(20);

        return CouponResource::collection($coupons);
    }

    public function show(Request $request, Coupon $coupon)
    {
        abort_unless($request->user()->isAdmin(), 403);

        return new CouponResource($coupon);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $this->validated($request);

        $coupon = Coupon::create($data);

        SystemEvent::log(
            'coupon.created',
            "Coupon \"{$coupon->code}\" created by {$request->user()->name}.",
            $request->user()->name,
            'user',
        );

        return (new CouponResource($coupon))->response()->setStatusCode(201);
    }

    public function update(Request $request, Coupon $coupon)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $this->validated($request, $coupon);

        $coupon->update($data);

        SystemEvent::log(
            'coupon.updated',
            "Coupon \"{$coupon->code}\" updated by {$request->user()->name}.",
            $request->user()->name,
            'user',
        );

        return new CouponResource($coupon->fresh());
    }

    /**
     * Coupons are normalized to uppercase before the unique check runs (see
     * Coupon::code()'s set mutator) so "save10" and "SAVE10" are treated as
     * the same code regardless of what case an admin types.
     */
    protected function validated(Request $request, ?Coupon $coupon = null): array
    {
        $merged = $request->all();
        if (isset($merged['code'])) {
            $merged['code'] = strtoupper(trim((string) $merged['code']));
        }
        $request->merge($merged);

        return $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('coupons', 'code')->ignore($coupon?->id)],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'numeric', 'min:0'],
            'expires_at' => ['nullable', 'date'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'max_redemptions_per_user' => ['nullable', 'integer', 'min:1'],
            'active' => ['sometimes', 'boolean'],
        ]);
    }
}
