<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgeStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Age bands (Adulto / Niño / …) and the ages each one covers.
 *
 * These rows are GLOBAL: every tour's prices hang off the same handful of
 * stages, so a range edited here moves on the whole catalogue at once. That is
 * why editing lives behind an admin route and why the wizard warns about it —
 * the pricing screen it is edited from looks per-tour, and it is not.
 *
 * Until now nothing could write them at all. The admin wizard carried its own
 * hardcoded copy ("Adulto 16-99", "Niño 3-11") and sent only prices back, so
 * what an operator typed into "Edad mín / Edad máx" was discarded on save and
 * the stored rows drifted out of sync with the screen — production ended up
 * with stage 1, the band the pricing treats as adult, named "Niño 0-3".
 */
class AgeStageController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => AgeStage::orderBy('id')->get(['id', 'description', 'min_age', 'max_age', 'editable']),
        ]);
    }

    /**
     * Update the bands in one call, so a screen showing several of them saves
     * as a unit instead of leaving half the change applied.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'stages' => 'required|array|min:1|max:20',
            'stages.*.id' => 'required|integer|exists:age_stages,id',
            'stages.*.description' => 'required|string|max:45',
            'stages.*.min_age' => 'required|integer|min:0|max:120',
            // 120 rather than the column's 999: a band ending at 999 is a
            // placeholder nobody meant, and it prints as "0 – 999 años".
            'stages.*.max_age' => 'required|integer|min:0|max:120',
        ]);

        foreach ($data['stages'] as $row) {
            if ($row['min_age'] > $row['max_age']) {
                return response()->json([
                    'success' => false,
                    'message' => "La etapa «{$row['description']}» tiene la edad mínima por encima de la máxima.",
                ], 422);
            }
        }

        $updated = [];
        foreach ($data['stages'] as $row) {
            $stage = AgeStage::find($row['id']);
            if (!$stage) {
                continue;
            }
            // `editable` is the flag protecting bands the business does not
            // want moved; honour it instead of trusting the client to hide them.
            if ($stage->editable === false) {
                continue;
            }
            $stage->update([
                'description' => $row['description'],
                'min_age' => $row['min_age'],
                'max_age' => $row['max_age'],
            ]);
            $updated[] = $stage->id;
        }

        return response()->json([
            'success' => true,
            'message' => 'Etapas de edad actualizadas.',
            'data' => AgeStage::orderBy('id')->get(['id', 'description', 'min_age', 'max_age', 'editable']),
            'updated' => $updated,
        ]);
    }
}
