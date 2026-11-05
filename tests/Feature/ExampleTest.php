<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests from the home page to login', function () {
    $this->get('/')->assertRedirect(route('show.login'));
});

it('shows the home page to an authenticated user', function () {
    $this->withoutVite()
        ->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk()
        ->assertSee('You have not translated anything yet!');
});
