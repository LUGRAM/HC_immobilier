<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\{Artisan, Schedule};

// ============================================
// SCHEDULED TASKS (Tâches planifiées)
// ============================================

// Envoyer les rappels de rendez-vous toutes les heures
Schedule::command('appointments:send-reminders')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Générer les factures mensuelles le 1er de chaque mois à 00:00
Schedule::command('invoices:generate-monthly')
    ->monthlyOn(1, '00:00')
    ->timezone('Africa/Libreville');

// Marquer les factures en retard tous les jours à 06:00
Schedule::command('invoices:mark-overdue')
    ->dailyAt('06:00')
    ->withoutOverlapping();

// Nettoyage des données tous les dimanches à 02:00
Schedule::command('cleanup:old-data')
    ->weekly()
    ->sundays()
    ->at('02:00');

// Backup de la base de données tous les jours à 03:00
Schedule::command('backup:run --only-db')
    ->dailyAt('03:00')
    ->onOneServer();

// Monitoring des queues toutes les 5 minutes
Schedule::command('queue:monitor redis:default --max=100')
    ->everyFiveMinutes();

// ============================================
// ARTISAN COMMANDS (Commandes manuelles)
// ============================================

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();