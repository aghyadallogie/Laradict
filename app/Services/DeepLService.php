<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DeepLService
{
    protected $authKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->authKey = config('services.deepl.auth_key');
        $this->baseUrl = rtrim(config('services.deepl.url'), '/');
    }

    /**
     * Detects the source language of the given text and returns translations for all user languages.
     *
     * This method performs the following steps:
     * 1. Detects the source language by translating the text to the first available user language (or English as fallback).
     * 2. Stores the original text under the detected language code in the translations array.
     * 3. Always adds an English translation (if the detected language is not English).
     * 4. Translates the text to all other user languages (except the detected language and English),
     *    and adds them to the translations array.
     *
     * @param string $text The text to translate.
     * @param array $userLanguages The user's preferred language keys (e.g., ['en', 'de', 'fr']).
     * @param array $languageMap Map of language keys to DeepL language codes (e.g., ['en' => 'EN', 'de' => 'DE']).
     * @return array Associative array of translations, keyed by language code (e.g., ['en' => 'Hello', 'de' => 'Hallo']).
     */
    public function detectAndTranslate(string $text, array $userLanguages, array $languageMap): array
    {
        // Detect source language
        $firstTargetLang = collect($userLanguages)
            ->map(fn($lang) => $languageMap[$lang] ?? null)
            ->filter()
            ->first() ?? 'EN';

        $detection = $this->translate($text, $firstTargetLang);
        $detectedLang = strtolower($detection['detected'] ?? 'en');
        $translations = [$detectedLang => $text];

        // Always add English translation
        if ($detectedLang !== 'en') {
            $translations['en'] = $this->translate($text, 'EN', strtoupper($detectedLang))['text'] ?? '';
        }

        // Translate to all other user languages except detected and English using the translate method
        collect($userLanguages)
            ->map(fn($lang) => strtolower($languageMap[$lang] ?? ''))
            ->filter(fn($langCode) => $langCode && $langCode !== $detectedLang && $langCode !== 'en')
            ->each(function ($langCode) use (&$translations, $text, $detectedLang) {
                $translations[$langCode] = $this->translate($text, strtoupper($langCode), strtoupper($detectedLang))['text'] ?? '';
            });
        return $translations;
    }

    /**
     * Translates text using the DeepL API.
     *
     * Sends a request to the DeepL translation API to auto-detect language of the input text 
     * and then use translate method on it to the specified target languages.
     *
     * Returns an array containing:
     *   - 'text': The translated text (string, or empty string on failure)
     *   - 'detected': The detected source language code (lowercase, or null on failure)
     *
     * If the API call fails or an exception occurs, returns ['text' => '', 'detected' => null].
     *
     * @param string $text The text to translate.
     * @param string $targetLang The target language code (e.g., 'EN').
     * @param string|null $sourceLang The source language code (optional, e.g., 'DE').
     * @return array ['text' => translated text, 'detected' => detected source language code]
     */
    public function translate(string $text, string $targetLang, ?string $sourceLang = null): array
    {
        $params = [
            'text' => $text,
            'target_lang' => strtoupper($targetLang),
        ];
        if ($sourceLang) {
            $params['source_lang'] = strtoupper($sourceLang);
        }
        try {
            $response = Http::withHeaders(['Authorization' => 'DeepL-Auth-Key ' . $this->authKey])
                ->asForm()
                ->timeout(5)
                ->post($this->baseUrl . '/v2/translate', $params);

            if ($response->failed()) {
                return ['text' => '', 'detected' => null];
            }

            $data = $response->json();
            return [
                'text' => $data['translations'][0]['text'] ?? '',
                'detected' => strtolower($data['translations'][0]['detected_source_language'] ?? ''),
            ];
        } catch (\Exception $e) {
            return ['text' => '', 'detected' => null];
        }
    }
}
