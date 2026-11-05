<?php

namespace App\Http\Controllers;

use App\Exceptions\DeepLException;
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

        try {
            $result = $deepL->detectAndTranslate($validated['text_to_translate'], $userLanguages, $languageMap);
        } catch (DeepLException $e) {
            // Nothing usable came back (already logged by the service), so don't save a blank word.
            return back()
                ->withInput()
                ->withErrors(['text_to_translate' => 'Translation is unavailable right now, please try again in a moment.']);
        }

        Word::create([
            'user_id' => $user->id,
            'translations' => $result['translations'],
        ]);

        $redirect = redirect('/');

        if ($result['failed']) {
            $redirect->with('warning', 'Saved, but these languages could not be translated: ' . strtoupper(implode(', ', $result['failed'])));
        }

        return $redirect;
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
