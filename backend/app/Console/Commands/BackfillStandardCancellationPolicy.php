<?php

namespace App\Console\Commands;

use App\Models\Language;
use App\Models\TourTranslation;
use App\Support\StandardCancellationPolicy;
use Illuminate\Console\Command;

class BackfillStandardCancellationPolicy extends Command
{
    protected $signature = 'tours:backfill-standard-policy
        {--force : Overwrite existing policy text even if a tour already has one}
        {--dry-run : Show what would change without writing}';

    protected $description = 'Insert the standard cancellation policy table into every tour translation\'s booking_texts.policyDescription';

    public function handle(): int
    {
        $policies = StandardCancellationPolicy::all();
        $languages = Language::all()->keyBy(fn($l) => strtoupper($l->code));

        $force = (bool) $this->option('force');
        $dry = (bool) $this->option('dry-run');

        $updated = 0;
        $skipped = 0;
        $missing = 0;

        $translations = TourTranslation::with('language')->get();

        $bar = $this->output->createProgressBar($translations->count());
        $bar->start();

        foreach ($translations as $trans) {
            $bar->advance();

            $code = strtoupper(optional($trans->language)->code ?? '');
            if (!$code || !isset($policies[$code])) {
                $missing++;
                continue;
            }

            $bookingTexts = $trans->booking_texts ?? [];
            if (!is_array($bookingTexts)) {
                $bookingTexts = [];
            }

            $existing = trim((string) ($bookingTexts['policyDescription'] ?? ''));
            $hasExisting = $existing !== '' && $existing !== '<p></p>';

            if ($hasExisting && !$force) {
                $skipped++;
                continue;
            }

            $bookingTexts['policyDescription'] = $policies[$code];

            if (!$dry) {
                $trans->booking_texts = $bookingTexts;
                $trans->save();
            }
            $updated++;
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Updated', 'Skipped (had text)', 'Missing language code'],
            [[$updated, $skipped, $missing]]
        );

        if ($dry) {
            $this->warn('Dry run — no changes saved. Re-run without --dry-run to persist.');
        }

        return self::SUCCESS;
    }
}
