<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:ingestao-email')
    ->everyTwoMinutes()
    ->withoutOverlapping()
    ->runInBackground();
