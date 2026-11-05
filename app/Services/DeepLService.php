<?php

namespace App\Services;

use App\Exceptions\DeepLException;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepLService
{
    protected const TIMEOUT = 5;

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
     *    That translation is kept, so it isn't requested again.
     * 2. Stores the original text under the detected language code in the translations array.
     * 3. Always adds an English translation (if the detected language is not English).
     * 4. Translates the text to all other user languages (except the detected language and English),
     *    and adds them to the translations array. Steps 3 and 4 are sent concurrently.
     *
     * @param string $text The text to translate.
     * @param array $userLanguages The user's preferred language keys (e.g., ['en', 'de', 'fr']).
     * @param array $languageMap Map of language keys to DeepL language codes (e.g., ['en' => 'EN', 'de' => 'DE']).
     * @return array{translations: array<string, string>, failed: list<string>}
     *         'translations' is keyed by language code (e.g., ['en' => 'Hello', 'de' => 'Hallo']) and only contains
     *         languages that were actually translated; 'failed' lists the language codes that could not be.
     * @throws DeepLException If the source language can't be detected, or nothing at all could be translated.
     */
    public function detectAndTranslate(string $text, array $userLanguages, array $languageMap): array
    {
        $targets = collect($userLanguages)
            ->map(fn($lang) => strtolower($languageMap[$lang] ?? ''))
            ->filter()
            ->unique()
            ->values();
        $firstTarget = $targets->first() ?? 'en';

        // Detect source language. This is also a real translation into $firstTarget, so keep it.
        $detection = $this->translate($text, strtoupper($firstTarget));
        if (! $detection['detected']) {
            throw new DeepLException('DeepL could not detect the source language.');
        }
        $detectedLang = $detection['detected'];

        $results = [];
        if ($detection['text'] !== '' && $firstTarget !== $detectedLang) {
            $results[$firstTarget] = $detection['text'];
        }

        // English is always added, then the user's languages; the detected one is the original text.
        $order = collect([$detectedLang, 'en'])->merge($targets)->unique();
        $pending = $order->reject(fn($code) => $code === $detectedLang || isset($results[$code]))->values();

        // Everything left goes out concurrently, so total latency doesn't grow with the number of languages.
        $responses = Http::pool(fn(Pool $pool) => $pending->map(
            fn($code) => $pool->as($code)
                ->withHeaders($this->headers())
                ->asForm()
                ->timeout(self::TIMEOUT)
                ->post($this->baseUrl . '/v2/translate', [
                    'text' => $text,
                    'target_lang' => strtoupper($code),
                    'source_lang' => strtoupper($detectedLang),
                ])
        )->all());

        foreach ($pending as $code) {
            $results[$code] = $this->parseResponse($responses[$code] ?? null, $code)['text'];
        }

        $failed = $order->reject(fn($code) => $code === $detectedLang || ($results[$code] ?? '') !== '')->values();

        // Only the original text survived: nothing worth saving.
        if ($failed->count() === $order->count() - 1) {
            throw new DeepLException('DeepL could not translate into any language.');
        }

        return [
            'translations' => $order
                ->reject(fn($code) => $failed->contains($code))
                ->mapWithKeys(fn($code) => [$code => $code === $detectedLang ? $text : $results[$code]])
                ->all(),
            'failed' => $failed->all(),
        ];
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
     * If the API call fails or an exception occurs, logs a warning and returns ['text' => '', 'detected' => null].
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
            $response = Http::withHeaders($this->headers())
                ->asForm()
                ->timeout(self::TIMEOUT)
                ->post($this->baseUrl . '/v2/translate', $params);
        } catch (\Exception $e) {
            return $this->parseResponse($e, $targetLang);
        }

        return $this->parseResponse($response, $targetLang);
    }

    protected function headers(): array
    {
        return ['Authorization' => 'DeepL-Auth-Key ' . $this->authKey];
    }

    /**
     * Normalizes a DeepL response and logs why it failed, if it did. Accepts null or an exception,
     * which Http::pool() returns in place of a response when a request fails to connect.
     * Only the target language is logged, never the key or the user's text.
     */
    protected function parseResponse(mixed $response, string $targetLang): array
    {
        if (!$response instanceof Response || $response->failed()) {
            Log::warning('DeepL translation failed', [
                'target_lang' => strtoupper($targetLang),
                'status' => $response instanceof Response ? $response->status() : null,
                'reason' => match (true) {
                    $response instanceof Response => $response->json('message') ?? 'HTTP error',
                    $response instanceof \Throwable => $response->getMessage(),
                    default => 'no response',
                },
            ]);

            return ['text' => '', 'detected' => null];
        }

        $data = $response->json();
        return [
            'text' => $data['translations'][0]['text'] ?? '',
            'detected' => strtolower($data['translations'][0]['detected_source_language'] ?? ''),
        ];
    }
}
