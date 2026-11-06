<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function registrationData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
        'languages' => ['french', 'german'],
    ], $overrides);
}

it('registers a user, saves their languages and logs them in', function () {
    $this->post('/register', registrationData())
        ->assertRedirect(route('welcome'))
        ->assertSessionHas('success');

    $user = User::firstWhere('email', 'jane@example.com');

    expect($user->languages)->toBe(['french', 'german'])
        ->and($user->password)->not->toBe('secret-password');
    $this->assertAuthenticatedAs($user);
});

it('registers a user without languages', function () {
    $this->post('/register', registrationData(['languages' => null]))->assertRedirect(route('welcome'));

    expect(User::count())->toBe(1);
});

it('rejects invalid registrations', function (array $overrides, string $field) {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->post('/register', registrationData($overrides))->assertSessionHasErrors($field);

    expect(User::count())->toBe(1);
    $this->assertGuest();
})->with([
    'missing name' => [['name' => ''], 'name'],
    'bad email' => [['email' => 'not-an-email'], 'email'],
    'email already taken' => [['email' => 'taken@example.com'], 'email'],
    'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'password mismatch' => [['password_confirmation' => 'different'], 'password'],
    'unknown language' => [['languages' => ['klingon']], 'languages.0'],
]);

it('logs the user out', function () {
    $this->actingAs(User::factory()->create())
        ->post('/logout')
        ->assertRedirect(route('show.login'));

    $this->assertGuest();
});

it('updates the user\'s languages from the profile page', function () {
    $user = User::factory()->create(['languages' => ['french']]);

    $this->actingAs($user)
        ->patch('/profile/languages', ['languages' => ['german', 'italian']])
        ->assertRedirect(route('welcome'))
        ->assertSessionHas('success');

    expect($user->fresh()->languages)->toBe(['german', 'italian']);
});

it('clears the languages when none are selected', function () {
    $user = User::factory()->create(['languages' => ['french']]);

    $this->actingAs($user)->patch('/profile/languages', [])->assertRedirect(route('welcome'));

    expect($user->fresh()->languages)->toBe([]);
});

it('rejects unknown languages on the profile page', function () {
    $user = User::factory()->create(['languages' => ['french']]);

    $this->actingAs($user)
        ->patch('/profile/languages', ['languages' => ['klingon']])
        ->assertSessionHasErrors('languages.0');

    expect($user->fresh()->languages)->toBe(['french']);
});

it('shows the profile page with the user\'s details and chosen languages checked', function () {
    $user = User::factory()->create(['name' => 'Jane Doe', 'languages' => ['french']]);

    $this->withoutVite()
        ->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertSee('Jane Doe')
        ->assertSee($user->email)
        ->assertSee('value="french"', false);
});

it('requires login for the profile page', function () {
    $this->get('/profile')->assertRedirect(route('show.login'));
});
