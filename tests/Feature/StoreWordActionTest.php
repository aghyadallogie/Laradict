<?php

use App\Actions\StoreWord;
use App\Exceptions\DeepLException;
use App\Models\User;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.deepl.auth_key' => 'test-key',
        'services.deepl.url' => 'https://api-free.deepl.com',
    ]);

    $this->user = User::factory()->create(['languages' => ['french', 'spanish']]);
});

function fakeTranslations(array $failing = [], int $status = 456): void
{
    Http::fake(function (Request $request) use ($failing, $status) {
        $target = $request['target_lang'];

        if (in_array($target, $failing, true)) {
            return Http::response(['message' => 'Quota exceeded'], $status);
        }

        return Http::response(['translations' => [[
            'text' => ['FR' => 'Bonjour', 'EN' => 'Hello', 'ES' => 'Hola'][$target],
            'detected_source_language' => 'DE',
        ]]]);
    });
}

it('translates into the user\'s languages and saves the word for them', function () {
    fakeTranslations();

    $result = app(StoreWord::class)->handle($this->user, 'Hallo');

    expect($result['failed'])->toBe([])
        ->and($result['word'])->toBeInstanceOf(Word::class)
        ->and($result['word']->user_id)->toBe($this->user->id)
        ->and($result['word']->fresh()->translations)->toBe([
            'de' => 'Hallo', 'en' => 'Hello', 'fr' => 'Bonjour', 'es' => 'Hola',
        ]);
});

it('returns the languages that failed and saves the rest', function () {
    fakeTranslations(failing: ['ES']);

    $result = app(StoreWord::class)->handle($this->user, 'Hallo');

    expect($result['failed'])->toBe(['es'])
        ->and($result['word']->translations)->toBe(['de' => 'Hallo', 'en' => 'Hello', 'fr' => 'Bonjour']);
});

it('saves nothing and throws when DeepL is unusable', function () {
    fakeTranslations(failing: ['FR', 'EN', 'ES'], status: 403);

    expect(fn () => app(StoreWord::class)->handle($this->user, 'Hallo'))->toThrow(DeepLException::class);
    expect(Word::count())->toBe(0);
});
