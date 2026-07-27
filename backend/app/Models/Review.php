<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'tour_id', 'name', 'review_date', 'review_date_parsed', 'rating', 'title',
        'comment', 'language', 'opinion', 'published', 'featured',
    ];

    protected $casts = [
        'published' => 'boolean',
        'featured' => 'boolean',
        'rating' => 'integer',
        'review_date_parsed' => 'date',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    /**
     * `review_date` is a free-form display string ("jul. 2024", "July 2024",
     * "12 de julio de 2024") — parse it into a sortable Y-m-d (or null) so
     * listings can order by the REAL review date instead of created_at (which
     * is just the DB-import timestamp for legacy reviews).
     */
    public static function parseReviewDate(?string $raw): ?string
    {
        $raw = mb_strtolower(trim((string) $raw));
        if ($raw === '') {
            return null;
        }

        if (!preg_match('/(19|20)\d{2}/', $raw, $m)) {
            return null;
        }
        $year = (int) $m[0];

        $months = [
            'ene' => 1, 'feb' => 2, 'mar' => 3, 'abr' => 4, 'may' => 5, 'jun' => 6,
            'jul' => 7, 'ago' => 8, 'sep' => 9, 'set' => 9, 'oct' => 10, 'nov' => 11, 'dic' => 12,
            'jan' => 1, 'apr' => 4, 'aug' => 8, 'dec' => 12,
        ];
        $month = null;
        foreach ($months as $token => $num) {
            if (str_contains($raw, $token)) {
                $month = $num;
                break;
            }
        }
        if (!$month && preg_match('/\b(0?[1-9]|1[0-2])[\/\-]/', $raw, $m)) {
            $month = (int) $m[1];
        }
        $month = $month ?: 1;

        $day = 1;
        if (preg_match('/\b([0-3]?\d)\b(?!\d)/', $raw, $m) && (int) $m[1] >= 1 && (int) $m[1] <= 31 && (int) $m[1] !== $year % 100) {
            $day = (int) $m[1];
        }

        return checkdate($month, $day, $year) ? sprintf('%04d-%02d-%02d', $year, $month, $day) : null;
    }
}
