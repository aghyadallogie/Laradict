<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;

    /**
     * Show the registration form view.
     *
     * @return \Illuminate\View\View
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Show the login form view.
     *
     * @return \Illuminate\View\View
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Handle user registration, validation, and login.
     *
     * Validates the registration form, creates a new user, logs them in, and redirects to the welcome page.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'languages' => ['nullable', 'array'],
            'languages.*' => ['string', Rule::in(array_keys(config('languages')))],
        ]);

        $user = User::create($validated);

        Auth::login($user);

        return redirect()->route('welcome')->with('success', 'Registration successful!');
    }

    /**
     * Handle user login.
     *
     * Validates login credentials and attempts authentication. On success, regenerates session and redirects to welcome page.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Limit failed attempts per email + IP so one account can't be brute-forced.
        $throttleKey = Str::lower($validated['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ])->onlyInput('email');
        }

        if (Auth::attempt($validated)) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            return redirect()->route('welcome');
        }

        RateLimiter::hit($throttleKey);

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    /**
     * Log out the current user and invalidate the session.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('show.login');
    }

    /**
     * Show the authenticated user's profile page.
     *
     * @return \Illuminate\View\View
     */
    public function showProfile()
    {
        return view('profile', ['user' => Auth::user()]);
    }

    /**
     * Update the authenticated user's preferred languages.
     *
     * Validates and updates the user's languages, then redirects to the welcome page.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateLanguages(Request $request)
    {
        $validated = $request->validate([
            'languages' => ['nullable', 'array'],
            'languages.*' => ['string', Rule::in(array_keys(config('languages')))],
        ]);

        $user = $request->user();
        $user->languages = $validated['languages'] ?? [];
        $user->save();

        return redirect()->route('welcome')->with('success', 'Languages updated successfully!');
    }
}
