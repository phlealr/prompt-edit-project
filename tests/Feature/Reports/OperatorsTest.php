<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('makes a user an operator from the command line', function () {
    $user = User::factory()->create(['email' => 'ops@example.test']);

    $this->artisan('user:make-admin', ['email' => 'ops@example.test'])->assertSuccessful();

    expect($user->fresh()?->is_admin)->toBeTrue();
});

it('prints an error and changes nothing for an unknown email', function () {
    $user = User::factory()->create();

    $this->artisan('user:make-admin', ['email' => 'nobody@example.test'])
        ->expectsOutputToContain('No user has the email nobody@example.test.')
        ->assertFailed();

    expect($user->fresh()?->is_admin)->toBeFalse();
});

it('lets no web request set is_admin', function () {
    $this->post(route('register.store'), [
        'name' => 'Eve', 'email' => 'eve@example.test', 'password' => 'password', 'password_confirmation' => 'password', 'is_admin' => true,
    ]);
    $user = User::query()->where('email', 'eve@example.test')->sole();
    $this->actingAs($user)->patch(route('profile.update'), ['name' => 'Eve', 'email' => 'eve@example.test', 'is_admin' => true]);

    expect($user->fresh()?->is_admin)->toBeFalse();
});

it('shows the report and its sidebar entry to an operator', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->get(route('operator-report'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('reports/Index')->where('isOperator', true));
});

it('answers 403 to a user who is not an operator, and hides the entry', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('operator-report'))->assertForbidden();
    $this->actingAs($user)->get(route('history'))
        ->assertInertia(fn (Assert $page) => $page->where('isOperator', false));
});

it('redirects a guest to sign in', function () {
    $this->get(route('operator-report'))->assertRedirect(route('login'));
});
