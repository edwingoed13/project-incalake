<?php

namespace App\Services;

use App\Models\Tour;
use Carbon\Carbon;

/**
 * Decides whether a tour can actually run on a given date.
 *
 * This mirrors, rule for rule, what TourCalendar.vue greys out in the browser.
 * Until now that calendar was the *only* thing stopping someone booking an
 * unavailable date: the booking endpoint checked `after_or_equal:today` and
 * nothing else, so any request that skipped the UI — or any moment the
 * calendar failed to receive the window — would be accepted. 133 published
 * tours are past their end date, and every one of them would take a booking.
 *
 * Matching the calendar exactly matters in both directions. Stricter than the
 * UI and real customers get rejected on dates they were shown as free;
 * looser and the gap stays open.
 */
class TourAvailabilityService
{
    /**
     * @return array{available: bool, reason: ?string}
     */
    public function check(Tour $tour, string $date): array
    {
        try {
            $day = Carbon::parse($date)->startOfDay();
        } catch (\Throwable) {
            return $this->no('La fecha no es válida.');
        }

        $data = is_array($tour->availability_data) ? $tour->availability_data : [];
        $dateStr = $day->toDateString();

        if ($day->lt(Carbon::today())) {
            return $this->no('La fecha ya pasó.');
        }

        $start = trim((string) ($data['start'] ?? ''));
        if ($start !== '' && $dateStr < $start) {
            return $this->no('El tour todavía no está disponible en esa fecha.');
        }

        // No end date, or explicitly marked as never expiring, means no upper
        // bound — the same reading every other consumer already applies.
        $end = trim((string) ($data['end'] ?? ''));
        if (!($data['neverExpires'] ?? false) && $end !== '' && $dateStr > $end) {
            return $this->no('El tour ya no está disponible en esa fecha.');
        }

        // 0 = Sunday … 6 = Saturday, matching the calendar's getDay().
        $activeDays = $data['activeDays'] ?? null;
        if (is_array($activeDays) && $activeDays !== []) {
            $activeDays = array_map('intval', $activeDays);
            if (!in_array((int) $day->dayOfWeek, $activeDays, true)) {
                return $this->no('El tour no opera ese día de la semana.');
            }
        }

        foreach ($this->blocks($tour, $data) as $block) {
            $from = trim((string) ($block['startDate'] ?? ''));
            $to = trim((string) ($block['endDate'] ?? ''));
            if ($from !== '' && $to !== '' && $dateStr >= $from && $dateStr <= $to) {
                return $this->no('Esa fecha está bloqueada para este tour.');
            }
        }

        // Recurring holidays, stored as DD-MM so they repeat every year.
        $specialDays = $data['specialDays'] ?? [];
        if (is_array($specialDays) && in_array($day->format('d-m'), $specialDays, true)) {
            return $this->no('Esa fecha está bloqueada para este tour.');
        }

        return ['available' => true, 'reason' => null];
    }

    /** Blocks live in availability_data, with the older column as a fallback. */
    private function blocks(Tour $tour, array $data): array
    {
        $blocks = $data['blocks'] ?? null;
        if (!is_array($blocks)) {
            $blocks = is_array($tour->blocks_data) ? $tour->blocks_data : [];
        }

        return array_filter($blocks, 'is_array');
    }

    private function no(string $reason): array
    {
        return ['available' => false, 'reason' => $reason];
    }
}
