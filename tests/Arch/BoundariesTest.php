<?php

arch('only the image providers use the HTTP client')
    ->expect('App')
    ->not->toUse(['Illuminate\Support\Facades\Http', 'Illuminate\Http\Client'])
    ->ignoring('App\ImageProviders');

arch('only the Pixelforge implementation knows Pixelforge')
    ->expect('App\ImageProviders\Pixelforge')
    ->toOnlyBeUsedIn(['App\ImageProviders\Pixelforge', 'App\Providers']);
