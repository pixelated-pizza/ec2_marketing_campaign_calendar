<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    \App\Models\SheetMirror::all()->each(
        fn ($mirror) => (new \App\Services\SheetMirrorSync($mirror))->pullFromSheet()
    );
})->everyMinute();