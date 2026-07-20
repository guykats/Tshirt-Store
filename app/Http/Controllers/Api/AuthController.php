<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Address;
use App\Models\Order;
use App\Models\SystemEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $user = User::create($data)->fresh();

        Auth::login($user);
        $request->session()->regenerate();

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return new UserResource($request->user());
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

    /**
     * Self-service password change for an already-authenticated user (as
     * opposed to PasswordResetController's forgot-password email flow). The
     * current password is re-verified against the authenticated user's own
     * hash rather than trusted from the session, so a hijacked/left-open
     * session alone isn't enough to lock the real owner out.
     */
    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('auth.password'),
            ]);
        }

        $user->forceFill(['password' => $data['password']])->save();

        return response()->json(['message' => __('Your password has been changed.')]);
    }

    /**
     * Self-service account deletion. A hard `User::delete()` isn't viable —
     * `orders.user_id` (and `orders.shipping_address_id`/`billing_address_id`)
     * are `restrictOnDelete()`, so any user with order history can't be
     * removed outright without breaking financial records. Instead this
     * anonymizes the user's PII in place, hard-deletes what's safe to
     * (wishlist items, and any address not still referenced by an existing
     * order), and leaves orders/reviews untouched — ReviewResource already
     * reads the reviewer's name via the (now-scrubbed) User relation, so
     * review attribution is anonymized for free.
     *
     * Current password is re-verified the same way changePassword does, so a
     * hijacked/left-open session alone can't be used to wipe the real
     * owner's account.
     */
    public function deleteAccount(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('auth.password'),
            ]);
        }

        $user->wishlistItems()->delete();

        foreach ($user->addresses()->get() as $address) {
            $referencedByOrder = Order::where('shipping_address_id', $address->id)
                ->orWhere('billing_address_id', $address->id)
                ->exists();

            if (! $referencedByOrder) {
                $address->delete();
            }
        }

        $userId = $user->id;

        $user->forceFill([
            'name' => 'Deleted User',
            'email' => "deleted-user-{$userId}@deleted.invalid",
            'phone' => null,
            'password' => Hash::make(Str::random(40)),
            'remember_token' => null,
            'email_verified_at' => null,
        ])->save();

        SystemEvent::log(
            'user.self_deleted',
            "User #{$userId} deleted their own account.",
            null,
            'user',
            ['user_id' => $userId],
        );

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
