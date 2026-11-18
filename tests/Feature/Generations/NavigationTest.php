<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

it('redirects a guest opening History to sign in', function () {
    $this->get(route('history'))->assertRedirect(route('login'));
});

it('has no Dashboard page or route', function () {
    expect(Route::has('dashboard'))->toBeFalse()
        ->and(file_exists(resource_path('js/pages/Dashboard.vue')))->toBeFalse();
});

it('lands a user who signs in on Generate', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('generate', absolute: false));
});

it('sends a signed-in user opening the home page to Generate', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertRedirect(route('generate'));
});

it('still shows the welcome page to a guest', function () {
    $this->get(route('home'))->assertOk();
});
