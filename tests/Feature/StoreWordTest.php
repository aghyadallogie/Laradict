<?php

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

function fakeDeepL(array $failing = [], int $status = 456): void
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

it('saves every translation when DeepL succeeds', function () {
    fakeDeepL();

    $this->actingAs($this->user)
        ->post('/', ['text_to_translate' => 'Hallo'])
        ->assertRedirect('/')
        ->assertSessionMissing('warning');

    expect(Word::first()->translations)->toBe([
        'de' => 'Hallo', 'en' => 'Hello', 'fr' => 'Bonjour', 'es' => 'Hola',
    ]);
});

it('saves the word without blanks and warns when only some languages fail', function () {
    fakeDeepL(failing: ['ES']);

    $this->actingAs($this->user)
        ->post('/', ['text_to_translate' => 'Hallo'])
        ->assertRedirect('/')
        ->assertSessionHas('warning', 'Saved, but these languages could not be translated: ES');

    expect(Word::first()->translations)->toBe(['de' => 'Hallo', 'en' => 'Hello', 'fr' => 'Bonjour']);
});

it('saves nothing and shows an error when DeepL is unusable', function () {
    fakeDeepL(failing: ['FR', 'EN', 'ES'], status: 403);

    $this->actingAs($this->user)
        ->from('/')
        ->post('/', ['text_to_translate' => 'Hallo'])
        ->assertRedirect('/')
        ->assertSessionHasErrors('text_to_translate');

    expect(Word::count())->toBe(0);
});
