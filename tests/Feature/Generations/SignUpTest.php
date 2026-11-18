<?php

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

it('enables only registration and password reset in Fortify', function () {
    expect(config('fortify.features'))->toBe([Features::registration(), Features::resetPasswords()]);
});

it('lands a new user on Generate with no email verification step', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'password', 'password_confirmation' => 'password',
    ])->assertRedirect(route('generate', absolute: false));

    $this->get(route('generate'))->assertOk();
    expect(User::query()->sole())->not->toBeInstanceOf(MustVerifyEmail::class);
    Notification::assertNothingSent();
});

it('offers no two-factor authentication, passkeys or email verification', function () {
    expect(Route::has('two-factor.enable'))->toBeFalse()
        ->and(Route::has('two-factor.login'))->toBeFalse()
        ->and(Route::has('passkey.login'))->toBeFalse()
        ->and(Route::has('verification.notice'))->toBeFalse();
});
