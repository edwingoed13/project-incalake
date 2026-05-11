<?php

namespace App\Console\Commands;

use App\Models\Language;
use App\Models\Tour;
use App\Models\TourTranslation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetStandardPoliciesCommand extends Command
{
    protected $signature = 'tours:set-standard-policies
        {--dry-run : Show what would change without writing}
        {--only-empty : Only update tours/translations whose policy field is empty}';

    protected $description = 'Overwrite tours.policy_description and tour_translations.cancellation_policy with the standard cancellation table (multi-locale).';

    private array $policiesByCode = [];

    public function handle(): int
    {
        $this->policiesByCode = $this->buildPolicies();

        $dryRun = (bool) $this->option('dry-run');
        $onlyEmpty = (bool) $this->option('only-empty');

        $tours = Tour::with(['translations.language', 'primaryLanguage'])->get();
        $this->info("Found {$tours->count()} tours.");

        $languages = Language::all()->keyBy('id');
        $codeFallback = 'ES';

        $updatedTours = 0;
        $updatedTranslations = 0;
        $createdTranslations = 0;

        DB::beginTransaction();
        try {
            foreach ($tours as $tour) {
                $primaryCode = $tour->primaryLanguage->code ?? $codeFallback;
                $primaryHtml = $this->htmlFor($primaryCode);

                if (!$onlyEmpty || empty($tour->policy_description)) {
                    if (!$dryRun) {
                        $tour->update(['policy_description' => $primaryHtml]);
                    }
                    $updatedTours++;
                }

                foreach ($languages as $language) {
                    $html = $this->htmlFor($language->code);

                    $translation = $tour->translations->firstWhere('language_id', $language->id);

                    if ($translation) {
                        if (!$onlyEmpty || empty($translation->cancellation_policy)) {
                            if (!$dryRun) {
                                $translation->update(['cancellation_policy' => $html]);
                            }
                            $updatedTranslations++;
                        }
                    } else {
                        if (!$dryRun) {
                            TourTranslation::create([
                                'tour_id' => $tour->id,
                                'language_id' => $language->id,
                                'cancellation_policy' => $html,
                            ]);
                        }
                        $createdTranslations++;
                    }
                }
            }

            if ($dryRun) {
                DB::rollBack();
                $this->warn('DRY RUN — no changes committed.');
            } else {
                DB::commit();
                $this->info('Committed.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Tours updated (policy_description)', $updatedTours],
                ['Translations updated (cancellation_policy)', $updatedTranslations],
                ['Translations created', $createdTranslations],
            ]
        );

        return self::SUCCESS;
    }

    private function htmlFor(string $code): string
    {
        return $this->policiesByCode[$code] ?? $this->policiesByCode['EN'];
    }

    private function buildPolicies(): array
    {
        return [
            'ES' => $this->buildTableHtml(
                ['Periodo de Anticipación para Anulación', 'Penalidad', 'Detalles'],
                [
                    ['Hasta 48 horas antes del inicio del tour', '20% del total', 'Gastos administrativos, comisiones de tarjeta de crédito/débito y otros relacionados.'],
                    ['Dentro de las 48 horas antes del inicio del tour', '100% del total', 'Monto total acordado del servicio.'],
                ]
            ),
            'EN' => $this->buildTableHtml(
                ['Cancellation Notice Period', 'Penalty', 'Details'],
                [
                    ['Up to 48 hours before the tour start', '20% of total', 'Administrative fees, credit/debit card commissions and other related expenses.'],
                    ['Within 48 hours before the tour start', '100% of total', 'Total agreed service amount.'],
                ]
            ),
            'FR' => $this->buildTableHtml(
                ["Délai de préavis d'annulation", 'Pénalité', 'Détails'],
                [
                    ["Jusqu'à 48 heures avant le début du tour", '20% du total', 'Frais administratifs, commissions de carte de crédit/débit et autres frais associés.'],
                    ['Moins de 48 heures avant le début du tour', '100% du total', 'Montant total convenu du service.'],
                ]
            ),
            'DE' => $this->buildTableHtml(
                ['Stornierungsvorlauf', 'Strafgebühr', 'Details'],
                [
                    ['Bis zu 48 Stunden vor Tour-Beginn', '20% der Gesamtsumme', 'Verwaltungsgebühren, Kreditkarten-/EC-Karten-Provisionen und andere damit verbundene Kosten.'],
                    ['Innerhalb von 48 Stunden vor Tour-Beginn', '100% der Gesamtsumme', 'Vollständiger vereinbarter Servicebetrag.'],
                ]
            ),
            'BR' => $this->buildTableHtml(
                ['Período de Antecedência para Cancelamento', 'Penalidade', 'Detalhes'],
                [
                    ['Até 48 horas antes do início do tour', '20% do total', 'Despesas administrativas, comissões de cartão de crédito/débito e outras relacionadas.'],
                    ['Dentro de 48 horas antes do início do tour', '100% do total', 'Valor total acordado do serviço.'],
                ]
            ),
            'IT' => $this->buildTableHtml(
                ['Periodo di preavviso per la cancellazione', 'Penale', 'Dettagli'],
                [
                    ["Fino a 48 ore prima dell'inizio del tour", '20% del totale', 'Spese amministrative, commissioni di carta di credito/debito e altre correlate.'],
                    ["Entro 48 ore dall'inizio del tour", '100% del totale', "Importo totale concordato del servizio."],
                ]
            ),
        ];
    }

    private function buildTableHtml(array $headers, array $rows): string
    {
        $html = "<table>\n  <thead>\n    <tr>\n";
        foreach ($headers as $h) {
            $html .= '      <th>' . e($h) . "</th>\n";
        }
        $html .= "    </tr>\n  </thead>\n  <tbody>\n";
        foreach ($rows as $row) {
            $html .= "    <tr>\n";
            foreach ($row as $cell) {
                $html .= '      <td>' . e($cell) . "</td>\n";
            }
            $html .= "    </tr>\n";
        }
        $html .= "  </tbody>\n</table>";
        return $html;
    }
}
