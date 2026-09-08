<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ShippingMethod;
use App\Services\GeodataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private GeodataService $geodataService) {}

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user()->load(['provincia', 'canton', 'distrito', 'barrio']);
        $provincias = $this->geodataService->getProvincias();
        $shippingMethods = ShippingMethod::where('active', true)
            ->whereNotNull('pais')
            ->get();

        return view('profile.edit', compact('user', 'provincias', 'shippingMethods'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Toggle whether the user wants their loyalty points redeemed on their next invoice.
     */
    public function toggleRedeemPoints(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->update([
            'redeem_points_requested' => ! $user->redeem_points_requested,
        ]);

        return Redirect::route('profile.edit')->with('status', 'redeem-points-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
