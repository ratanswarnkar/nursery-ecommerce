<?php

use App\Models\CustomerOtpChallenge;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('otp:prune {--days=7 : Delete challenges older than specified days}', function () {
    $days = (int) $this->option('days');
    $count = CustomerOtpChallenge::where('created_at', '<', now()->subDays($days))->delete();
    $this->info("Pruned {$count} expired OTP challenge records older than {$days} days.");
})->purpose('Prune expired and old OTP challenges for data retention');
