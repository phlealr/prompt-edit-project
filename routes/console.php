<?php

use App\Jobs\Generations\PollGenerationsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Lost webhooks are caught up by polling the provider every minute (ADR 0010, ADR 0027).
Schedule::job(new PollGenerationsJob)->everyMinute();
