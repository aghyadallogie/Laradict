<?php

namespace App\Actions;

use App\Exceptions\DeepLException;
use App\Models\User;
use App\Models\Word;
use App\Services\DeepLService;

/**
 * Translates a text into the user's languages and saves it as one of their words.
 */
class StoreWord
{
    public function __construct(private DeepLService $deepL) {}

    /**
     * @return array{word: Word, failed: list<string>} The saved word, and the language codes that couldn't be translated.
     *
     * @throws DeepLException If nothing usable came back from DeepL; nothing is saved then.
     */
    public function handle(User $user, string $text): array
    {
        $result = $this->deepL->detectAndTranslate($text, $user->languages ?? [], config('languages'));

        return [
            'word' => $user->words()->create(['translations' => $result['translations']]),
            'failed' => $result['failed'],
        ];
    }
}
