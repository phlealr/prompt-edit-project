<?php

namespace App\Providers;

use App\ImageProviders\ImageProvider;
use App\ImageProviders\ImageProviders;
use App\ImageProviders\Pixelforge\PixelforgeImageProvider;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One binding per provider; a generation is handled by the one it was sent to (ADR 0044).
        $this->app->bind('image-providers.pixelforge', PixelforgeImageProvider::class);

        // New generations and the estimate use the provider named in configuration.
        $this->app->bind(ImageProvider::class, fn ($app): ImageProvider => $app->make(ImageProviders::class)->default());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        // Only operators see the operator report (ADR 0016).
        Gate::define('view-operator-report', fn (User $user): bool => $user->is_admin === true);

        // `composer run dev` also runs the scheduler, which polls the provider every minute.
        DevCommands::artisan('schedule:work', 'scheduler');
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
