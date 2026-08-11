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
        // fields that moved or vanished. Drop it and tell the client it's gone.
        $expected = (string) $request->query('schema_version', 'v1');
        if ($revision->schema_version !== $expected) {
            Log::info('Discarding stale tour draft', [
                'tour_id' => $tour->id,
                'stored' => $revision->schema_version,
                'expected' => $expected,
            ]);
            $revision->delete();

            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'El borrador guardado era de una versión anterior del editor y se descartó.',
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
