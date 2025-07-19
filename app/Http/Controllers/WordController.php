<?php

namespace App\Http\Controllers;

use App\Models\Word;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\DeepLService;

class WordController extends Controller
{
    /**
     * Store a new word and its translations for the authenticated user.
     *
     * Validates the input, detects the source language, translates the text to the user's preferred languages,
     * and saves all translations to the database.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\DeepLService $deepL
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, DeepLService $deepL)
    {
        $validated = $request->validate([
            'text_to_translate' => 'required|string|min:1',
        ]);

        $user = Auth::user();
        $languageMap = config('languages');
        $userLanguages = $user->languages ?? [];

        $translations = $deepL->detectAndTranslate($validated['text_to_translate'], $userLanguages, $languageMap);

        Word::create([
            'user_id' => $user->id,
            'translations' => $translations,
        ]);

        return redirect('/');
    }

    /**
     * Display a list of all words for the authenticated user.
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        $words = Word::where('user_id', Auth::id())->orderBy('created_at', 'desc')->get();
        return view('words.show', compact('words'));
    }

    /**
     * Display the welcome page with the latest word for the authenticated user.
     *
     * @return \Illuminate\View\View
     */
    public function showWelcome()
    {
        $latestWord = Word::where('user_id', Auth::id())->orderBy('created_at', 'desc')->first();
        return view('welcome', compact('latestWord'));
    }

    /**
     * Delete a word belonging to the authenticated user.
     *
     * @param int $id The ID of the word to delete.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $word = Word::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $word->delete();
        return redirect('/words')->with('success', 'Word deleted successfully.');
    }
}
