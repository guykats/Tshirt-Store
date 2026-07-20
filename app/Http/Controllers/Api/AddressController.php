<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Self-service address book for a logged-in customer, so checkout doesn't
 * force a full re-entry of the shipping address on every order (see
 * CheckoutController::store's optional shipping_address_id). Every action
 * here is scoped to the authenticated user via AddressPolicy — a customer
 * can never view/edit/delete/default another customer's address.
 */
class AddressController extends Controller
{
    /**
     * Shared field validation, matching the shape CheckoutController::store
     * already validates inline for a brand-new shipping_address.
     */
    protected function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'size:2'],
            'phone' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * The current user's saved addresses, default first.
     */
    public function index(Request $request)
    {
        $addresses = $request->user()
            ->addresses()
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => AddressResource::collection($addresses)]);
    }

    /**
     * Save a new address. The very first address a customer ever saves
     * becomes their default automatically — a customer with exactly one
     * address on file should never see it as "not the default".
     */
    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        $user = $request->user();
        $isFirstAddress = $user->addresses()->doesntExist();

        $address = $user->addresses()->create([
            'type' => 'shipping',
            ...$data,
            'is_default' => $isFirstAddress,
        ]);

        return (new AddressResource($address))->response()->setStatusCode(201);
    }

    /**
     * Edit an existing saved address in place. Does not touch is_default —
     * that's a separate, dedicated action (see setDefault below) so an edit
     * can never accidentally change which address checkout defaults to.
     */
    public function update(Request $request, Address $address)
    {
        $this->authorize('update', $address);

        $data = $request->validate($this->rules());

        $address->update($data);

        return new AddressResource($address);
    }

    /**
     * Remove a saved address. An address still referenced by a past order
     * can't be hard-deleted (orders.shipping_address_id/billing_address_id
     * are restrictOnDelete — see create_orders_table migration), so that
     * case is rejected with a clear message instead of a raw DB error. If
     * the deleted address was the default, the next-most-recent remaining
     * address (if any) is promoted so a customer with saved addresses left
     * never ends up with zero defaults.
     */
    public function destroy(Request $request, Address $address)
    {
        $this->authorize('delete', $address);

        $referencedByOrder = Order::where('shipping_address_id', $address->id)
            ->orWhere('billing_address_id', $address->id)
            ->exists();

        if ($referencedByOrder) {
            return response()->json([
                'message' => 'This address is attached to a past order and cannot be deleted.',
            ], 422);
        }

        $wasDefault = $address->is_default;
        $userId = $address->user_id;

        $address->delete();

        if ($wasDefault) {
            Address::where('user_id', $userId)->orderByDesc('id')->first()?->update(['is_default' => true]);
        }

        return response()->json(null, 204);
    }

    /**
     * Set one address as the default, atomically unsetting every other
     * address belonging to the same user — a customer must never end up
     * with two defaults (ambiguous checkout preselection) or, once they
     * have at least one address, zero.
     */
    public function setDefault(Request $request, Address $address)
    {
        $this->authorize('update', $address);

        DB::transaction(function () use ($address) {
            Address::where('user_id', $address->user_id)->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return new AddressResource($address->fresh());
    }
}
