<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Tag;
use App\Models\TourTranslation;
use App\Support\StandardCancellationPolicy;
use Database\Seeders\StandardTagsSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * One-shot maintenance endpoints that the admin UI can trigger when SSH /
 * artisan access isn't available on the host (cPanel without terminal).
 */
class MaintenanceController extends Controller
{
    /**
     * Insert the standard cancellation policy table into every tour
     * translation's booking_texts.policyDescription. By default skips rows
     * that already have content; pass ?force=1 to overwrite all of them.
     */
    public function backfillStandardPolicy(Request $request): JsonResponse
    {
        $force = $request->boolean('force');

        $policies = StandardCancellationPolicy::all();

        $updated = 0;
        $skipped = 0;
        $missing = 0;

        $translations = TourTranslation::with('language')->get();

        foreach ($translations as $trans) {
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
            $trans->booking_texts = $bookingTexts;
            $trans->save();
            $updated++;
        }

        return response()->json([
            'success' => true,
            'message' => $force
                ? 'Política estándar aplicada (forzado, sobrescribió todo).'
                : 'Política estándar aplicada solo donde estaba vacío.',
            'stats' => [
                'updated' => $updated,
                'skipped' => $skipped,
                'missing_language' => $missing,
                'total' => $translations->count(),
            ],
        ]);
    }

    /**
     * Run pending migrations from the browser when cPanel doesn't allow artisan.
     * Returns the migration output for verification.
     */
    public function runMigrations(): JsonResponse
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Migraciones ejecutadas correctamente.',
                'output' => $output,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al ejecutar migraciones.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Seed the 15 standardized tourism tags. Idempotent — updateOrCreate by slug.
     * Returns the resulting tag count.
     */
    public function seedStandardTags(): JsonResponse
    {
        try {
            $before = Tag::count();
            (new StandardTagsSeeder())->run();
            $after = Tag::count();

            return response()->json([
                'success' => true,
                'message' => 'Tags estándar aplicados correctamente.',
                'stats' => [
                    'before' => $before,
                    'after' => $after,
                    'added' => $after - $before,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al seedear tags.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * One-shot SQL: expand booking_anticipation_unit enum to include 'minutes'.
     * Use this if `runMigrations` fails or the migration was already applied locally.
     */
    public function expandAnticipationEnum(): JsonResponse
    {
        try {
            DB::statement("ALTER TABLE tours MODIFY COLUMN booking_anticipation_unit ENUM('minutes', 'hours', 'days') NOT NULL DEFAULT 'hours'");

            return response()->json([
                'success' => true,
                'message' => "Enum 'booking_anticipation_unit' actualizado a ('minutes', 'hours', 'days').",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al alterar enum.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
