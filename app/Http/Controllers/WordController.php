<?php

namespace App\Http\Controllers;

use App\Actions\StoreWord;
use App\Exceptions\DeepLException;
use App\Http\Requests\StoreWordRequest;
use App\Models\Word;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class WordController extends Controller
{
    /**
     * Store a new word and its translations for the authenticated user.
     *
     * Validates the input, detects the source language, translates the text to the user's preferred languages,
     * and saves all translations to the database.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreWordRequest $request, StoreWord $storeWord)
    {
        try {
            $result = $storeWord->handle($request->user(), $request->validated('text_to_translate'));
        } catch (DeepLException) {
            return back()
                ->withInput()
                ->withErrors(['text_to_translate' => 'Translation is unavailable right now, please try again in a moment.']);
        }

        $redirect = redirect('/');

        if ($result['failed']) {
            $redirect->with('warning', 'Saved, but these languages could not be translated: '.strtoupper(implode(', ', $result['failed'])));
        }

        return $redirect;
    }

    /**
     * Display a paginated list of the authenticated user's words, newest first.
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        $words = Auth::user()->words()->latest()->latest('id')->paginate(15);

        return view('words.show', compact('words'));
    }

    /**
     * Display the welcome page with the latest word for the authenticated user.
     *
     * @return \Illuminate\View\View
     */
    public function showWelcome()
    {
        $latestWord = Auth::user()->words()->latest()->latest('id')->first();

        return view('welcome', compact('latestWord'));
    }

    /**
     * Delete a word. Only its owner may do so (see WordPolicy).
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Word $word)
    {
        Gate::authorize('delete', $word);

        $word->delete();

        return redirect('/words')->with('success', 'Word deleted successfully.');
    }
}
