<?php

use App\Jobs\CalculateUserReputationJob;
use App\Jobs\UpdateContributionDnaJob;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('pulse:check')->daily()->at('09:00');
Schedule::command('issues:fetch')->dailyAt('03:00');

Schedule::call(function () {
    User::whereHas('ratingsReceived')->chunk(50, function ($users) {
        foreach ($users as $user) {
            CalculateUserReputationJob::dispatch($user);
        }
    });
})->dailyAt('02:00')->name('reputation-sweep');

Schedule::call(function () {
    User::chunk(50, fn($users) => $users->each(fn($u) => UpdateContributionDnaJob::dispatch($u)));
})->weeklyOn(1, '04:00')->name('dna-update');
