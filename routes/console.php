<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\StorageBillingService;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('invoices:generate-storage {--date=}', function () {
    $date = $this->option('date') ? Carbon::parse($this->option('date')) : now();
    $result = app(StorageBillingService::class)->generateAutomatic($date);

    $this->info('Regles lues: ' . $result['rules']);
    $this->info('Factures generees: ' . $result['generated']);
    $this->info('Ignorees: ' . $result['skipped']);
})->purpose('Generer automatiquement les factures de stockage clients');

Schedule::command('invoices:generate-storage')->dailyAt('00:10');
