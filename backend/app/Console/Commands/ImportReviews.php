<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Review;
use App\Models\TourTranslation;

class ImportReviews extends Command
{
    protected $signature = 'import:reviews {file? : Path to JSON file (defaults to database/data/reviews_4_5_estrellas.json)}';
    protected $description = 'Import reviews from JSON file';

    // Map opinion text to tour by matching translation titles
    private $tourCache = [];

    public function handle()
    {
        $file = $this->argument('file') ?? database_path('data/reviews_4_5_estrellas.json');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $data = json_decode(file_get_contents($file), true);
        if (!$data) {
            $this->error("Invalid JSON");
            return 1;
        }

        $this->info("Found " . count($data) . " reviews to import.");

        // Pre-load tour translations for matching
        $this->buildTourCache();

        $imported = 0;
        $matched = 0;

        foreach ($data as $review) {
            // Normalize rating: anything > 5 becomes 5
            $rating = min((int) $review['rating'], 5);
            $rating = max($rating, 1);

            // Detect language
            $language = $this->detectLanguage($review['comment'] ?? '', $review['title'] ?? '');

            // Try to match tour
            $tourId = null;
            if (!empty($review['opinion'])) {
                $tourId = $this->matchTour($review['opinion']);
                if ($tourId) $matched++;
            }

            Review::create([
                'tour_id' => $tourId,
                'name' => $review['name'] ?? 'Anonymous',
                'review_date' => $review['date'] ?? null,
                'rating' => $rating,
                'title' => $review['title'] ?? null,
                'comment' => $review['comment'] ?? '',
                'language' => $language,
                'opinion' => $review['opinion'] ?? null,
                'published' => true,
                'featured' => $rating === 5 && strlen($review['comment'] ?? '') > 100,
            ]);

            $imported++;
        }

        $this->info("Imported: {$imported} reviews ({$matched} matched to tours).");

        return 0;
    }

    private function buildTourCache(): void
    {
        // Build a map of title variations → tour_id
        $translations = DB::table('tour_translations')
            ->select('tour_id', 'h1_title', 'slug')
            ->get();

        foreach ($translations as $t) {
            if ($t->h1_title) {
                $key = $this->normalizeTitle($t->h1_title);
                $this->tourCache[$key] = $t->tour_id;
            }
        }

        $this->info("Tour cache: " . count($this->tourCache) . " entries.");
    }

    private function matchTour(string $opinion): ?int
    {
        if (empty($opinion)) return null;

        $key = $this->normalizeTitle($opinion);

        // Exact match
        if (isset($this->tourCache[$key])) {
            return $this->tourCache[$key];
        }

        // Fuzzy match: find best match
        $bestMatch = null;
        $bestScore = 0;

        foreach ($this->tourCache as $title => $tourId) {
            // Check if opinion contains the tour title or vice versa
            similar_text($key, $title, $score);
            if ($score > $bestScore && $score > 60) {
                $bestScore = $score;
                $bestMatch = $tourId;
            }
        }

        return $bestMatch;
    }

    private function normalizeTitle(string $title): string
    {
        $title = mb_strtolower(trim($title));
        $title = preg_replace('/[^a-z0-9\s]/', '', $title);
        $title = preg_replace('/\s+/', ' ', $title);
        return $title;
    }

    private function detectLanguage(string $comment, string $title): string
    {
        $text = $comment . ' ' . $title;

        // Spanish indicators
        if (preg_match('/\b(excelente|increíble|maravilloso|hermoso|recomendado|bueno|guía|paseo|viaje|isla|incluye|experiencia|muy bien|nos encantó)\b/iu', $text)) {
            return 'es';
        }

        // French indicators
        if (preg_match('/\b(très|magnifique|excellent|merci|notre|guide|voyage|nous|avec|cette|belle|bien)\b/iu', $text)) {
            return 'fr';
        }

        // German indicators
        if (preg_match('/\b(sehr|wunderschön|ausgezeichnet|unser|reise|empfehlenswert|haben|waren|diese|toll)\b/iu', $text)) {
            return 'de';
        }

        // Portuguese indicators
        if (preg_match('/\b(excelente|maravilhoso|muito|nosso|viagem|recomendo|experiência|passeio|guia|incrível)\b/iu', $text)) {
            return 'pt';
        }

        // Italian indicators
        if (preg_match('/\b(bellissimo|eccellente|molto|nostro|viaggio|guida|esperienza|consiglio|isola)\b/iu', $text)) {
            return 'it';
        }

        // Default to English
        return 'en';
    }
}
