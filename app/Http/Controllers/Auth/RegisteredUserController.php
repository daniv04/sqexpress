<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'regex:/^[0-9\s\-\+\(\)]+$/', 'min:7', 'max:20'],
            'cedula' => ['required', 'string', 'max:50'],
                        'provincia_id' => ['required', 'exists:provincias,id'],
                        'canton_id' => ['required', 'exists:cantones,id'],
                        'distrito_id' => ['required', 'exists:distritos,id'],
            'address' => ['required', 'string', 'max:500'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Generate unique locker code
        $lockerCode = $this->generateUniqueLockerCode();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'cedula' => $request->cedula,
                        'provincia_id' => $request->provincia_id,
                        'canton_id' => $request->canton_id,
                        'distrito_id' => $request->distrito_id,
            'address' => $request->address,
            'locker_code' => $lockerCode,
            'role' => 'user', // Set default role as user
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('panel', absolute: false));
    }

    /**
     * Generate a unique locker code.
     */
    private function generateUniqueLockerCode(): string
    {
        do {
            // Generate format: SQE-XXXX (e.g., SQE-1234)
            $code = 'SQE-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (User::where('locker_code', $code)->exists());

        return $code;
    }
}
