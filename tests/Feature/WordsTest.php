<?php

use App\Models\User;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

function wordFor(User $user, string $text = 'Hallo'): Word
{
    return Word::create(['user_id' => $user->id, 'translations' => ['de' => $text, 'en' => 'Hello']]);
}

it('has an index for listing a user\'s words newest first', function () {
    expect(Schema::hasIndex('words', ['user_id', 'created_at']))->toBeTrue();
});

it('paginates the word list 15 per page, newest first', function () {
    foreach (range(1, 16) as $i) {
        wordFor($this->user, "word-$i")->forceFill(['created_at' => now()->addSeconds($i)])->save();
    }

    // "word-1<" matches the oldest word only (the closing tag stops it matching word-16 and friends).
    $first = $this->withoutVite()->actingAs($this->user)->get('/words')->assertOk();
    $first->assertSee('word-16', false)->assertDontSee('word-1<', false);
    expect($first->viewData('words'))->toHaveCount(15);

    $second = $this->withoutVite()->actingAs($this->user)->get('/words?page=2')->assertOk();
    $second->assertSee('word-1<', false);
    expect($second->viewData('words'))->toHaveCount(1);
});

it('only lists the user\'s own words', function () {
    wordFor($this->user, 'mine');
    wordFor(User::factory()->create(), 'theirs');

    $this->withoutVite()->actingAs($this->user)->get('/words')
        ->assertSee('mine')
        ->assertDontSee('theirs');
});

it('lets the owner delete a word', function () {
    $word = wordFor($this->user);

    $this->actingAs($this->user)
        ->delete(route('words.destroy', $word))
        ->assertRedirect('/words')
        ->assertSessionHas('success');

    expect(Word::count())->toBe(0);
});

it('forbids deleting someone else\'s word', function () {
    $word = wordFor(User::factory()->create());

    $this->actingAs($this->user)
        ->delete(route('words.destroy', $word))
        ->assertForbidden();

    expect(Word::count())->toBe(1);
});

it('returns 404 when deleting a word that does not exist', function () {
    $this->actingAs($this->user)->delete('/words/999')->assertNotFound();
});

it('throttles translations to 20 per minute per user', function () {
    // Invalid input fails validation before DeepL is called, but still counts towards the limit.
    for ($i = 0; $i < 20; $i++) {
        $this->actingAs($this->user)->post('/', [])->assertStatus(302);
    }

    $this->actingAs($this->user)->post('/', [])->assertStatus(429);

    // Another user has their own allowance.
    $this->actingAs(User::factory()->create())->post('/', [])->assertStatus(302);
});
