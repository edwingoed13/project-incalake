<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Models\TourRevision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Draft buffer for tours that are already live.
 *
 * The payload is opaque here on purpose: it is the admin wizard's own state,
 * and the wizard is what replays it. Publishing does NOT go through this
 * controller — the admin loads the draft into the wizard and calls the normal
 * tour update endpoint, so there is exactly one code path that writes a tour.
 * This controller only parks, returns and discards the blob.
 */
class TourRevisionController extends Controller
{
    /** Guard against a runaway payload filling the row (translations carry HTML). */
    private const MAX_PAYLOAD_BYTES = 2 * 1024 * 1024;

    /**
     * Current pending draft for a tour, if any.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $tour = Tour::findOrFail($id);
        $revision = TourRevision::with('editor:id,name,email')
            ->where('tour_id', $tour->id)
            ->first();

        if (!$revision) {
            return response()->json(['success' => true, 'data' => null]);
        }

        // A draft written by an older wizard shape would restore garbage into
        // fields that moved or vanished, so it must not be applied. It is NOT
        // deleted here: this used to, and a GET that destroys data destroys it
        // for every caller that gets the parameter wrong — a typo in the query
        // string was enough to wipe an operator's parked work, silently, while
        // returning 200. Reading is now read-only; the client is told the draft
        // is unusable and DELETE remains the way to actually remove one.
        $expected = (string) $request->query('schema_version', 'v1');
        if ($revision->schema_version !== $expected) {
            Log::info('Tour draft ignored: schema mismatch', [
                'tour_id' => $tour->id,
                'stored' => $revision->schema_version,
                'expected' => $expected,
            ]);

            return response()->json([
                'success' => true,
                'data' => null,
                'stale' => true,
                'message' => 'El borrador guardado es de una versión anterior del editor y no se puede aplicar.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'payload' => $revision->payload,
                'schema_version' => $revision->schema_version,
                // The client echoes this back on save so a write built on a
                // stale copy can be rejected instead of erasing someone.
                'version' => $revision->version,
                'updated_at' => $revision->updated_at,
                'updated_by' => $revision->updated_by,
                'updated_by_name' => $revision->editor?->name,
                'updated_by_tab' => $revision->updated_by_tab,
            ],
        ]);
    }

    /**
     * Create or replace the pending draft. This is where autosave lands while
     * the tour is published — the live row is never touched.
     */
    public function store(Request $request, $id): JsonResponse
    {
        $tour = Tour::findOrFail($id);

        $data = $request->validate([
            'payload' => 'required|array',
            'schema_version' => 'nullable|string|max:20',
            'base_version' => 'nullable|integer|min:0',
            // Per-browser-tab id: with one shared admin account, this is the
            // only way to tell "my own save racing itself" from "someone at
            // another computer" — the 409 handler treats them very differently.
            'tab_id' => 'nullable|string|max:40',
        ]);

        $encoded = json_encode($data['payload']);
        if ($encoded === false || strlen($encoded) > self::MAX_PAYLOAD_BYTES) {
            return response()->json([
                'success' => false,
                'message' => 'El borrador excede el tamaño máximo permitido.',
            ], 422);
        }

        $existing = TourRevision::with('editor:id,name')
            ->where('tour_id', $tour->id)
            ->first();

        // Optimistic concurrency. base_version is the version the client last
        // read; if the stored draft has moved past it, someone else saved in
        // between and writing would erase their work without a trace. Refuse
        // and let the operator decide. Omitting base_version keeps the old
        // last-write-wins behaviour, so an older admin build still works.
        if ($existing && $request->filled('base_version')
            && (int) $data['base_version'] !== $existing->version) {
            return response()->json([
                'success' => false,
                'conflict' => true,
                'message' => $existing->editor?->name
                    ? "{$existing->editor->name} guardó cambios en este tour mientras lo editabas."
                    : 'Otra persona guardó cambios en este tour mientras lo editabas.',
                'data' => [
                    'version' => $existing->version,
                    'updated_at' => $existing->updated_at,
                    'updated_by_name' => $existing->editor?->name,
                    'updated_by_tab' => $existing->updated_by_tab,
                ],
            ], 409);
        }

        $revision = TourRevision::updateOrCreate(
            ['tour_id' => $tour->id],
            [
                'payload' => $data['payload'],
                'schema_version' => $data['schema_version'] ?? 'v1',
                'version' => ($existing?->version ?? 0) + 1,
                'updated_by' => $request->user()?->id,
                'updated_by_tab' => $data['tab_id'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Borrador guardado.',
            'data' => [
                'version' => $revision->version,
                'updated_at' => $revision->updated_at,
                'updated_by' => $revision->updated_by,
            ],
        ]);
    }

    /**
     * Discard the pending draft. Called on "Descartar cambios" and right after
     * the wizard successfully publishes them.
     */
    public function destroy($id): JsonResponse
    {
        $tour = Tour::findOrFail($id);
        $deleted = TourRevision::where('tour_id', $tour->id)->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted ? 'Borrador descartado.' : 'No había borrador pendiente.',
        ]);
    }
}
