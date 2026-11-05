<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['email' => 'jane@example.com']);
});

function failLogin(int $times, string $email = 'jane@example.com'): void
{
    for ($i = 0; $i < $times; $i++) {
        test()->post('/login', ['email' => $email, 'password' => 'wrong-password']);
    }
}

it('locks login after 5 failed attempts, even with the right password', function () {
    failLogin(5);

    $this->post('/login', ['email' => 'jane@example.com', 'password' => 'password'])
        ->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toContain('Too many login attempts');
    $this->assertGuest();
});

it('still allows login after 4 failed attempts and resets the counter', function () {
    failLogin(4);

    $this->post('/login', ['email' => 'jane@example.com', 'password' => 'password'])
        ->assertRedirect(route('welcome'));
    $this->assertAuthenticatedAs($this->user);

    // The counter was cleared, so the failures start from zero again.
    auth()->logout();
    failLogin(4);
    $this->post('/login', ['email' => 'jane@example.com', 'password' => 'password'])
        ->assertRedirect(route('welcome'));
});

it('does not let failures against one email lock another', function () {
    User::factory()->create(['email' => 'john@example.com']);

    failLogin(5);

    $this->post('/login', ['email' => 'john@example.com', 'password' => 'password'])
        ->assertRedirect(route('welcome'));
});

it('is not case sensitive about the email when counting attempts', function () {
    failLogin(3);
    failLogin(2, 'JANE@example.com');

    $this->post('/login', ['email' => 'jane@example.com', 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('throttles registration to 10 requests per hour per IP', function () {
    for ($i = 0; $i < 10; $i++) {
        $this->post('/register', [])->assertStatus(302); // fails validation, but still counts
    }

    $this->post('/register', [])->assertStatus(429);
});
