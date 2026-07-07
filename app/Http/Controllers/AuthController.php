<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(private UserService $users)
    {
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:50',
            'password' => 'required|string',
        ]);

        $user = $this->users->verifyCredentials($validated['username'], $validated['password']);

        if (! $user) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Invalid username or password.']);
        }

        $request->session()->regenerate();
        $request->session()->put('auth_user', $user);

        return redirect()->intended(route('dashboard'));
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:50|alpha_dash',
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        try {
            $user = $this->users->register(
                $validated['name'],
                $validated['username'],
                $validated['password']
            );
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['username' => $e->getMessage()]);
        }

        $request->session()->regenerate();
        $request->session()->put('auth_user', $user);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('auth_user');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
