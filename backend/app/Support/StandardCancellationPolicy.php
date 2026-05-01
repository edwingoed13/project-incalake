<?php

namespace App\Support;

/**
 * Default cancellation policy that applies to every tour marked as 'standard'.
 * Stored as HTML in tour_translations.booking_texts.policyDescription so the
 * existing rendering on the frontend (cart, checkout, email) keeps working.
 */
class StandardCancellationPolicy
{
    /**
     * Map of language code (uppercase) -> HTML table.
     */
    public static function all(): array
    {
        return [
            'ES' => self::buildHtml(
                ['Periodo de Anticipación para Anulación', 'Penalidad', 'Detalles'],
                [
                    ['Hasta 48 horas antes del inicio del tour', '20% del total', 'Gastos administrativos, comisiones de tarjeta de crédito/débito y otros relacionados.'],
                    ['Dentro de las 48 horas antes del inicio del tour', '100% del total', 'Monto total acordado del servicio.'],
                ],
            ),
            'EN' => self::buildHtml(
                ['Cancellation Notice Period', 'Penalty', 'Details'],
                [
                    ['Up to 48 hours before the tour starts', '20% of total', 'Administrative costs, credit/debit card fees and other related charges.'],
                    ['Within 48 hours before the tour starts', '100% of total', 'Full agreed service amount.'],
                ],
            ),
            'PT' => self::buildHtml(
                ['Prazo de Antecedência para Cancelamento', 'Penalidade', 'Detalhes'],
                [
                    ['Até 48 horas antes do início do tour', '20% do total', 'Despesas administrativas, taxas de cartão de crédito/débito e outras relacionadas.'],
                    ['Dentro de 48 horas antes do início do tour', '100% do total', 'Valor total acordado do serviço.'],
                ],
            ),
            'FR' => self::buildHtml(
                ["Période d'Anticipation pour Annulation", 'Pénalité', 'Détails'],
                [
                    ['Jusqu\'à 48 heures avant le début du tour', '20% du total', 'Frais administratifs, commissions de carte de crédit/débit et autres frais associés.'],
                    ['Dans les 48 heures avant le début du tour', '100% du total', 'Montant total convenu du service.'],
                ],
            ),
            'DE' => self::buildHtml(
                ['Stornierungsfrist', 'Gebühr', 'Details'],
                [
                    ['Bis zu 48 Stunden vor Tourbeginn', '20% des Gesamtbetrags', 'Verwaltungskosten, Kredit-/Debitkartengebühren und andere damit verbundene Kosten.'],
                    ['Innerhalb von 48 Stunden vor Tourbeginn', '100% des Gesamtbetrags', 'Vollständig vereinbarter Servicebetrag.'],
                ],
            ),
            'IT' => self::buildHtml(
                ['Periodo di Preavviso per Annullamento', 'Penalità', 'Dettagli'],
                [
                    ['Fino a 48 ore prima dell\'inizio del tour', '20% del totale', 'Spese amministrative, commissioni di carta di credito/debito e altre correlate.'],
                    ['Entro 48 ore prima dell\'inizio del tour', '100% del totale', 'Importo totale concordato del servizio.'],
                ],
            ),
        ];
    }

    /**
     * Get the HTML for a single language. Falls back to ES when missing.
     */
    public static function forLanguage(string $code): string
    {
        $code = strtoupper(trim($code));
        $all = self::all();
        return $all[$code] ?? $all['ES'];
    }

    private static function buildHtml(array $headers, array $rows): string
    {
        $h = '';
        foreach ($headers as $header) {
            $h .= '<th>' . htmlspecialchars($header, ENT_QUOTES, 'UTF-8') . '</th>';
        }

        $body = '';
        foreach ($rows as $row) {
            $body .= '<tr>';
            foreach ($row as $cell) {
                $body .= '<td>' . htmlspecialchars($cell, ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $body .= '</tr>';
        }

        return '<table class="tiptap-table"><thead><tr>' . $h . '</tr></thead><tbody>' . $body . '</tbody></table>';
    }
}
