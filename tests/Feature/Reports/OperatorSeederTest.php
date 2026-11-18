<?php

use App\Models\User;
use Database\Seeders\OperatorSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;

it('creates the default operator from configuration', function () {
    config()->set('operator.email', 'reviewer@example.test');
    config()->set('operator.name', 'Reviewer');
    config()->set('operator.password', 'A-strong-Pass-123!');

    $this->seed(OperatorSeeder::class);

    $operator = User::query()->sole();
    expect($operator->email)->toBe('reviewer@example.test')
        ->and($operator->name)->toBe('Reviewer')
        ->and($operator->is_admin)->toBeTrue()
        ->and(Hash::check('A-strong-Pass-123!', $operator->password))->toBeTrue();
});

it('creates nothing new when run again, and keeps the account an operator', function () {
    config()->set('operator.email', 'reviewer@example.test');

    $this->seed(OperatorSeeder::class);
    $this->seed(OperatorSeeder::class);

    expect(User::query()->count())->toBe(1)
        ->and(User::query()->sole()->is_admin)->toBeTrue();
});

it('defaults to the login published in the README', function () {
    $operator = require config_path('operator.php');
    $readme = (string) file_get_contents(base_path('README.md'));

    expect($readme)->toContain($operator['email'])
        ->toContain($operator['password']);
});

it('does not let anyone change the shared operator\'s profile or password from the web', function () {
    $this->seed(OperatorSeeder::class);
    $operator = User::query()->sole();
    $password = $operator->password;

    $this->actingAs($operator)
        ->patch(route('profile.update'), ['name' => 'Troll', 'email' => 'troll@example.test'])
        ->assertForbidden();
    $this->actingAs($operator)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->put(route('user-password.update'), [
            'current_password' => config('operator.password'),
            'password' => 'Another-Pass-123!',
            'password_confirmation' => 'Another-Pass-123!',
        ])
        ->assertForbidden();

    $operator->refresh();
    expect($operator->email)->toBe(config('operator.email'))
        ->and($operator->name)->toBe(config('operator.name'))
        ->and($operator->password)->toBe($password);
});

it('does not let anyone reset the shared operator\'s password', function () {
    $this->seed(OperatorSeeder::class);
    $operator = User::query()->sole();
    $token = Password::createToken($operator);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $operator->email,
        'password' => 'Another-Pass-123!',
        'password_confirmation' => 'Another-Pass-123!',
    ])->assertSessionHasErrors('email');

    expect(Hash::check(config('operator.password'), $operator->fresh()?->password))->toBeTrue();
});

it('still lets every other user change their own profile', function () {
    $this->seed(OperatorSeeder::class);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => 'New name', 'email' => $user->email])
        ->assertSessionHasNoErrors();

    expect($user->fresh()?->name)->toBe('New name');
});

it('tells the shared operator on the settings pages that the account cannot be changed', function () {
    $this->seed(OperatorSeeder::class);
    $operator = User::query()->where('email', config('operator.email'))->firstOrFail();
    $other = User::factory()->create();

    $this->withSession(['auth.password_confirmed_at' => time()]);

    foreach (['profile.edit', 'security.edit'] as $route) {
        $this->actingAs($operator)->get(route($route))
            ->assertInertia(fn (Assert $page) => $page->where('locked', true));
        $this->actingAs($other)->get(route($route))
            ->assertInertia(fn (Assert $page) => $page->where('locked', false));
    }
});
