<?php

/*
| Custom Artisan commands (php artisan <name>). Crewly needs none of its own —
| this is Laravel's example command, kept so `php artisan inspire` still works.
*/

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
