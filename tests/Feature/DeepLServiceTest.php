<?php

use App\Exceptions\DeepLException;
use App\Services\DeepLService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    config([
        'services.deepl.auth_key' => 'test-key',
        'services.deepl.url' => 'https://api-free.deepl.com',
    ]);
});

function deeplResponse(string $text, string $detected = 'DE'): array
{
    return ['translations' => [['text' => $text, 'detected_source_language' => $detected]]];
}

it('sends the auth header and form params to the configured endpoint', function () {
    Http::fake(['*' => Http::response(deeplResponse('Hello'))]);

    $result = (new DeepLService)->translate('Hallo', 'en');

    expect($result)->toBe(['text' => 'Hello', 'detected' => 'de']);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api-free.deepl.com/v2/translate'
        && $request->hasHeader('Authorization', 'DeepL-Auth-Key test-key')
        && $request['text'] === 'Hallo'
        && $request['target_lang'] === 'EN'
        && ! isset($request['source_lang']));
});

it('uses the configured base url for pro keys', function () {
    config(['services.deepl.url' => 'https://api.deepl.com/']);
    Http::fake(['*' => Http::response(deeplResponse('Hello'))]);

    (new DeepLService)->translate('Hallo', 'EN', 'de');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.deepl.com/v2/translate'
        && $request['source_lang'] === 'DE');
});

it('returns an empty result on http errors', function () {
    Http::fake(['*' => Http::response(['message' => 'Quota exceeded'], 456)]);

    expect((new DeepLService)->translate('Hallo', 'EN'))
        ->toBe(['text' => '', 'detected' => null]);
});

it('returns an empty result when the connection fails', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    expect((new DeepLService)->translate('Hallo', 'EN'))
        ->toBe(['text' => '', 'detected' => null]);
});

it('detects the source language and translates to every user language', function () {
    Http::fake(function (Request $request) {
        return Http::response(match ($request['target_lang']) {
            'FR' => deeplResponse('Bonjour'),
            'EN' => deeplResponse('Hello'),
            'ES' => deeplResponse('Hola'),
        });
    });

    $result = (new DeepLService)->detectAndTranslate(
        'Hallo',
        ['fr', 'es'],
        ['fr' => 'FR', 'es' => 'ES'],
    );

    expect($result)->toBe([
        'translations' => [
            'de' => 'Hallo',
            'en' => 'Hello',
            'fr' => 'Bonjour',
            'es' => 'Hola',
        ],
        'failed' => [],
    ]);

    // Detection call (reused for FR) + EN + ES. Not one call per language plus a throwaway detection.
    Http::assertSentCount(3);
});

it('does not reuse the detection call when the text is already in the first target language', function () {
    Http::fake(function (Request $request) {
        return Http::response(match ($request['target_lang']) {
            'FR' => deeplResponse('Bonjour', 'FR'), // DeepL echoes the text back
            'EN' => deeplResponse('Hello', 'FR'),
            'ES' => deeplResponse('Hola', 'FR'),
        });
    });

    $result = (new DeepLService)->detectAndTranslate(
        'Bonjour',
        ['fr', 'es'],
        ['fr' => 'FR', 'es' => 'ES'],
    );

    expect($result['translations'])->toBe(['fr' => 'Bonjour', 'en' => 'Hello', 'es' => 'Hola']);
    Http::assertSentCount(3);
});

it('keeps the other translations, reports and logs the language that failed', function () {
    Log::spy();
    Http::fake(function (Request $request) {
        return match ($request['target_lang']) {
            'FR' => Http::response(deeplResponse('Bonjour')),
            'EN' => Http::response(deeplResponse('Hello')),
            'ES' => Http::response(['message' => 'Quota exceeded'], 456),
        };
    });

    $result = (new DeepLService)->detectAndTranslate(
        'Hallo',
        ['fr', 'es'],
        ['fr' => 'FR', 'es' => 'ES'],
    );

    // No blank entry is stored for the failed language.
    expect($result)->toBe([
        'translations' => ['de' => 'Hallo', 'en' => 'Hello', 'fr' => 'Bonjour'],
        'failed' => ['es'],
    ]);

    Log::shouldHaveReceived('warning')->once()->withArgs(fn ($message, $context) => $message === 'DeepL translation failed'
        && $context === ['target_lang' => 'ES', 'status' => 456, 'reason' => 'Quota exceeded']);
});

it('throws and logs when the source language cannot be detected', function () {
    Log::spy();
    Http::fake(['*' => Http::response(['message' => 'Forbidden'], 403)]);

    expect(fn () => (new DeepLService)->detectAndTranslate('Hallo', ['fr'], ['fr' => 'FR']))
        ->toThrow(DeepLException::class);

    Log::shouldHaveReceived('warning')->once()->withArgs(fn ($message, $context) => $context['status'] === 403
        && $context['reason'] === 'Forbidden');
});

it('throws when nothing could be translated', function () {
    Http::fake(function (Request $request) {
        // Detection succeeds into FR but returns no text; every other language fails.
        return $request['target_lang'] === 'FR' && ! isset($request['source_lang'])
            ? Http::response(['translations' => [['text' => '', 'detected_source_language' => 'DE']]])
            : Http::response(['message' => 'Too many requests'], 429);
    });

    expect(fn () => (new DeepLService)->detectAndTranslate('Hallo', ['fr', 'es'], ['fr' => 'FR', 'es' => 'ES']))
        ->toThrow(DeepLException::class);
});

it('never logs the auth key or the text being translated', function () {
    Log::spy();
    Http::fake(['*' => Http::response(['message' => 'Forbidden'], 403)]);

    try {
        (new DeepLService)->detectAndTranslate('secret sentence', ['fr'], ['fr' => 'FR']);
    } catch (DeepLException) {
    }

    Log::shouldHaveReceived('warning')->withArgs(function ($message, $context) {
        $logged = $message.json_encode($context);

        return ! str_contains($logged, 'test-key') && ! str_contains($logged, 'secret sentence');
    });
});
