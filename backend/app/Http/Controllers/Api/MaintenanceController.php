<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\TourTranslation;
use App\Support\StandardCancellationPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
