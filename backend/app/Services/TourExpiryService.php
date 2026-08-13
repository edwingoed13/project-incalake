<?php

namespace App\Services;

use App\Models\Tour;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Works out which tours need an availability-expiry warning.
 *
 * A tour's bookable window lives in availability_data.end. Past that date the
 * calendar has no selectable days, so the tour is effectively unsellable — but
 * nothing says so: it stays published and the API still reports is_bookable,
 * which is how 133 tours ended up expired (some since 2019) with nobody
 * noticing. This is the part that notices.
 *
 * Deliberately read-only about visibility: it warns, it never unpublishes.
 * Hiding tours automatically would have retired those 133 in one go.
 */
class TourExpiryService
{
    /** Start warning this far ahead of the end date. */
    public const WARNING_WINDOW_DAYS = 90;

    /** And repeat this often while it stays inside that window. */
    public const REPEAT_EVERY_DAYS = 15;

    /**
     * Tours entering or inside the warning window that are due a reminder.
     *
     * @return Collection<int, array{tour: Tour, days_left: int}>
     */
    public function dueForWarning(?Carbon $now = null): Collection
    {
        $now = $now ?: Carbon::now();
        $repeatCutoff = $now->copy()->subDays(self::REPEAT_EVERY_DAYS);

        return $this->candidates()
            ->map(function (Tour $tour) use ($now) {
                $end = $this->endDate($tour);

                return $end ? ['tour' => $tour, 'end' => $end] : null;
            })
            ->filter()
            ->filter(function (array $row) use ($now, $repeatCutoff) {
                /** @var Carbon $end */
                $end = $row['end'];

                // Already past its date — that's the backlog's job, not this one.
                if ($end->lt($now->copy()->startOfDay())) {
                    return false;
                }
                if ($end->gt($now->copy()->addDays(self::WARNING_WINDOW_DAYS))) {
                    return false;
                }

                $last = $row['tour']->expiry_alert_sent_at;

                return $last === null || Carbon::parse($last)->lte($repeatCutoff);
            })
            ->map(fn (array $row) => [
                'tour' => $row['tour'],
                'days_left' => $now->copy()->startOfDay()->diffInDays($row['end'], false),
            ])
            ->sortBy('days_left')
            ->values();
    }

    /**
     * Tours whose date already passed and that have never been reported.
     *
     * Reported once, as a single list, then never again — a countdown for a
     * tour that expired six years ago is noise, and 133 of them repeating
     * every fortnight would make the whole alert unreadable.
     *
     * @return Collection<int, array{tour: Tour, days_overdue: int}>
     */
    public function backlog(?Carbon $now = null): Collection
    {
        $now = $now ?: Carbon::now();
        $today = $now->copy()->startOfDay();

        return $this->candidates()
            ->filter(fn (Tour $tour) => $tour->expiry_alert_sent_at === null)
            ->map(function (Tour $tour) use ($today) {
                $end = $this->endDate($tour);

                return ($end && $end->lt($today))
                    ? ['tour' => $tour, 'days_overdue' => $end->diffInDays($today)]
                    : null;
            })
            ->filter()
            ->sortByDesc('days_overdue')
            ->values();
    }

    /**
     * Published tours that can expire at all.
     *
     * Drafts and archived tours are excluded: nobody can book them, so a
     * warning about their calendar is just noise.
     */
    private function candidates(): Collection
    {
        return Tour::query()
            ->where('status', 'published')
            ->get()
            ->reject(fn (Tour $tour) => $this->neverExpires($tour));
    }

    public function neverExpires(Tour $tour): bool
    {
        $data = $tour->availability_data;

        return is_array($data) && !empty($data['neverExpires']);
    }

    /** The end of the bookable window, or null when there isn't one. */
    public function endDate(Tour $tour): ?Carbon
    {
        $data = $tour->availability_data;
        $raw = is_array($data) ? trim((string) ($data['end'] ?? '')) : '';

        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->startOfDay();
        } catch (\Throwable) {
            // A date nobody can parse can't be reasoned about; leave it alone
            // rather than guess and warn about the wrong thing.
            return null;
        }
    }
}
