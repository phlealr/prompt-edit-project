<?php

arch('only the image providers use the HTTP client')
    ->expect('App')
    ->not->toUse(['Illuminate\Support\Facades\Http', 'Illuminate\Http\Client'])
    ->ignoring('App\ImageProviders');

arch('only the Pixelforge implementation knows Pixelforge')
    ->expect('App\ImageProviders\Pixelforge')
    ->toOnlyBeUsedIn(['App\ImageProviders\Pixelforge', 'App\Providers']);

arch('no web request sends a generation to the provider')
    ->expect('App\Http')
    ->not->toUse(['App\Actions\Generations\SendGeneration', 'App\Jobs\Generations\SubmitGenerationJob']);

arch('submission, webhooks and polling all change generations through the one action')
    ->expect([
        'App\Actions\Generations\SendGeneration',
        'App\Actions\Generations\ApplyWebhookEvent',
        'App\Actions\Generations\PollGenerations',
        'App\Jobs\Generations\SubmitGenerationJob',
    ])
    ->toUse('App\Actions\Generations\ApplyProviderUpdate');

arch('the history page reads only our database')
    ->expect(['App\Http\Controllers\Generations\ListGenerationsController', 'App\Actions\Generations\ListGenerations'])
    ->not->toUse('App\ImageProviders\ImageProvider');

test('nothing outside the Pixelforge implementation knows its 92-day usage limit', function () {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2).'/app'));
    $mentions = [];

    foreach ($files as $file) {
        $path = $file->getPathname();

        if (str_ends_with($path, '.php') && ! str_contains($path, '/ImageProviders/Pixelforge/') && preg_match('/\\b92\\b/', (string) file_get_contents($path))) {
            $mentions[] = $path;
        }
    }

    expect($mentions)->toBe([]);
});
