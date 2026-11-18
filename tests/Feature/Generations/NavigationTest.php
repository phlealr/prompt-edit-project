<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

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

it('shows a guest our own home page, saying what the app does and how it charges', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Welcome')->where('name', config('app.name')));

    $page = (string) file_get_contents(resource_path('js/pages/Welcome.vue'));
    expect($page)->toContain('plus 25%')
        ->toContain('Failed generations are free')
        ->not->toContain('Laracasts')
        ->not->toContain('laravel.com');
});
