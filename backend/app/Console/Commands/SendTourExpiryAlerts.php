<?php

namespace App\Console\Commands;

use App\Mail\TourExpiryAlertMail;
use App\Models\Tour;
use App\Services\TourExpiryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Emails the reservations desk about tours whose bookable window is closing.
 *
 * Safe to run as often as you like: what stops it repeating is
 * expiry_alert_sent_at per tour, not the schedule. Running twice in a day
 * sends one warning, not two.
 */
class SendTourExpiryAlerts extends Command
{
    protected $signature = 'tours:expiry-alerts
                            {--dry-run : Report what would be sent without sending or marking anything}
                            {--to= : Override the recipient, for testing}';

    protected $description = 'Warn the reservations desk about tours about to stop being bookable';

    public function handle(TourExpiryService $expiry): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $recipient = $this->option('to') ?: config('services.incalake.reservations_email');

        if (empty($recipient)) {
            $this->error('No recipient configured (services.incalake.reservations_email).');
            return self::FAILURE;
        }

        $expiring = $expiry->dueForWarning();
        $backlog = $expiry->backlog();

        $this->line(sprintf('Por caducar y pendientes de aviso: %d', $expiring->count()));
        $this->line(sprintf('Ya caducados sin reportar:        %d', $backlog->count()));

        if ($expiring->isEmpty() && $backlog->isEmpty()) {
            $this->info('Nothing to report.');
            return self::SUCCESS;
        }

        foreach ($expiring as $row) {
            $this->line(sprintf('  · %s (#%d) — quedan %d días', $row['tour']->code, $row['tour']->id, $row['days_left']));
        }
        if ($backlog->isNotEmpty()) {
            $this->line(sprintf('  · %d caducados se incluyen en el resumen único', $backlog->count()));
        }

        if ($dryRun) {
            $this->warn('DRY RUN — no se envió nada ni se marcó ningún tour.');
            return self::SUCCESS;
        }

        try {
            Mail::to($recipient)->send(new TourExpiryAlertMail($expiring, $backlog));
        } catch (\Throwable $e) {
            // Marking before a confirmed send would lose the warning entirely,
            // so nothing is marked unless the mail went out.
            $this->error('No se pudo enviar el aviso: ' . $e->getMessage());
            Log::error('Tour expiry alert failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        $ids = $expiring->pluck('tour.id')->merge($backlog->pluck('tour.id'))->unique()->all();
        Tour::whereIn('id', $ids)->update(['expiry_alert_sent_at' => now()]);

        $this->info(sprintf('Aviso enviado a %s (%d tour(s) marcados).', $recipient, count($ids)));

        return self::SUCCESS;
    }
}
