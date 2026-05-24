<?php

namespace App\Mail\Concerns;

use App\Models\Tour;

/**
 * Builds clean "includes / not includes" lists for confirmation emails.
 *
 * The CMS stores these per-translation as rich-text HTML (<ul><li>…</li></ul>
 * with nested, inline-styled <span>s). Dropping that markup straight into an
 * email renders inconsistently across clients, so we extract just the <li>
 * text and let the email template style a compact, uniform bullet list.
 */
trait FormatsTourContent
{
    /**
     * @return array{includes: string[], excludes: string[]}
     */
    protected function tourIncludeLists(?Tour $tour): array
    {
        if (!$tour) {
            return ['includes' => [], 'excludes' => []];
        }

        // Emails are sent in Spanish; fall back to the first available translation.
        $t = $tour->getTranslation('ES') ?? $tour->translations()->first();

        return [
            'includes' => $this->htmlToList($t?->what_includes),
            'excludes' => $this->htmlToList($t?->what_not_includes),
        ];
    }

    /** Extract a flat list of trimmed text items from rich-text HTML (or an array, or newline text). */
    protected function htmlToList($value): array
    {
        if (empty($value)) {
            return [];
        }

        // Some translations may already be stored as a JSON array of strings.
        if (is_array($value)) {
            $items = $value;
        } elseif (preg_match_all('/<li[^>]*>(.*?)<\/li>/is', (string) $value, $m) && !empty($m[1])) {
            $items = $m[1];
        } else {
            // No list markup: split plain text on <br> / line breaks.
            $items = preg_split('/<br\s*\/?>|\r\n|\r|\n/i', (string) $value) ?: [];
        }

        $clean = [];
        foreach ($items as $raw) {
            $text = html_entity_decode(strip_tags((string) $raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = trim(preg_replace('/\s+/u', ' ', str_replace("\xC2\xA0", ' ', $text)));

            // Drop empties and a stray "Incluye:" / "No incluye:" heading line.
            if ($text === '' || preg_match('/^(incluye|no\s+incluye|not?\s+includes?)\s*:?$/iu', $text)) {
                continue;
            }
            $clean[] = $text;
        }

        return $clean;
    }
}
