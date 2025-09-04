<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Ping log tiap menit (buat bukti scheduler jalan)
Schedule::call(function () {
    \Log::info('Scheduler ping: '.now()->toDateTimeString());
})->everyMinute();

// Tandai overdue (TEST: tiap menit dulu)
Schedule::command('tagihan:mark-overdue')
    ->dailyAt('01:15')
    ->timezone('Asia/Jakarta');

// PRODUKSI: ganti dengan ->dailyAt('01:15')->timezone('Asia/Jakarta');
