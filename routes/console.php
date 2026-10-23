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

Artisan::command('permissions:sync {--prune : Supprimer les permissions qui ne correspondent plus a aucune action}', function () {
    [$created, $obsolete] = \App\Support\PermissionSync::run((bool) $this->option('prune'));

    $this->info('Permissions creees: ' . count($created));
    foreach ($created as $name) {
        $this->line('  + ' . $name . ' — ' . \App\Support\PermissionCatalog::label($name));
    }
    if ($obsolete) {
        $this->warn(($this->option('prune') ? 'Supprimees: ' : 'Obsoletes (utilisez --prune): ') . implode(', ', $obsolete));
    }
})->purpose('Creer une permission pour chaque action du systeme (a lancer apres ajout de nouvelles routes)');
