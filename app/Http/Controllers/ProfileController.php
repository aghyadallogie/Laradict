<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLanguagesRequest;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Show the authenticated user's profile page.
     *
     * @return \Illuminate\View\View
     */
    public function show()
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
    public function updateLanguages(UpdateLanguagesRequest $request)
    {
        $request->user()->update(['languages' => $request->validated('languages', [])]);

        return redirect()->route('welcome')->with('success', 'Languages updated successfully!');
    }
}
