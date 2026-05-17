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
     * Diagnose the Google Calendar integration (admin calendar).
     * cPanel + GitHub-FTP deploy does NOT carry the secret credentials file,
     * so events silently fail. This reports exactly what's wrong and, if the
     * creds are present, creates a real TEST event so it can be verified.
     */
    public function calendarTest(): JsonResponse
    {
        $path = storage_path('app/google-calendar-credentials.json');
        $exists = file_exists($path);
        $clientEmail = null;

        if ($exists) {
            $json = json_decode((string) @file_get_contents($path), true);
            $clientEmail = $json['client_email'] ?? null;
        }

        if (!$exists || !$clientEmail) {
            return response()->json([
                'success' => false,
                'credentials_file_present' => $exists,
                'service_account_email' => $clientEmail,
                'calendar_id' => config('services.google_calendar.calendar_id', 'reservas@incalake.com'),
                'message' => !$exists
                    ? 'Falta storage/app/google-calendar-credentials.json en el servidor (es secreto, no viaja por el deploy de GitHub). Súbelo por el File Manager de cPanel.'
                    : 'El archivo de credenciales no tiene client_email válido.',
            ], 422);
        }

        try {
            $ok = (new \App\Services\GoogleCalendarService())->createBookingEvent([
                'booking_code'   => 'TEST-' . now()->format('His'),
                'tour_title'     => 'Evento de prueba (ignorar / borrar)',
                'tour_date'      => now()->addDay()->format('Y-m-d'),
                'tour_time'      => '09:00:00',
                'adults'         => 1,
                'children'       => 0,
                'customer_name'  => 'Prueba Incalake',
                'customer_email' => 'reservas@incalake.com',
                'customer_phone' => '',
                'total'          => 0,
                'currency'       => 'USD',
                'payment_method' => 'test',
            ]);

            return response()->json([
                'success' => $ok,
                'credentials_file_present' => true,
                'service_account_email' => $clientEmail,
                'calendar_id' => config('services.google_calendar.calendar_id', 'reservas@incalake.com'),
                'message' => $ok
                    ? 'Evento de prueba creado. Revisa el Google Calendar de reservas@incalake.com (borra el evento TEST).'
                    : 'No se pudo crear el evento. Verifica que el calendario reservas@incalake.com esté compartido con ' . $clientEmail . ' con permiso "Hacer cambios en eventos". Revisa storage/logs/laravel.log para el detalle.',
            ], $ok ? 200 : 502);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'credentials_file_present' => true,
                'service_account_email' => $clientEmail,
                'message' => 'Excepción al crear el evento de prueba.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Secret-gated deploy hook (NOT behind auth:sanctum) so the GitHub Action
     * can clear caches automatically after the FTP deploy. Requires
     * ?key=<DEPLOY_HOOK_KEY> matching config('app.deploy_hook_key').
     */
    public function deployClearCaches(Request $request): JsonResponse
    {
        $expected = (string) config('app.deploy_hook_key', '');
        $given = (string) ($request->query('key') ?? $request->input('key') ?? '');

        if ($expected === '' || !hash_equals($expected, $given)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: invalid or missing deploy key.',
            ], 403);
        }

        return $this->clearCaches();
    }

    /**
     * Clear compiled views + config/route/app caches.
     * cPanel + GitHub-FTP deploys never run artisan, so a deployed Blade fix
     * (e.g. an email template) stays broken until the cached compiled view is
     * dropped. Call this once after deploy from the browser address bar.
     */
    public function clearCaches(): JsonResponse
    {
        try {
            $out = [];
            foreach (['view:clear', 'config:clear', 'route:clear', 'cache:clear'] as $cmd) {
                try {
                    Artisan::call($cmd);
                    $out[$cmd] = trim(Artisan::output()) ?: 'ok';
                } catch (\Throwable $inner) {
                    $out[$cmd] = 'ERROR: ' . $inner->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Cachés limpiadas. Las vistas Blade se recompilan en la próxima petición.',
                'output' => $out,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar cachés.',
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
     * Merge one tag into another. Moves all tour_tag pivot rows from `from_id`
     * to `to_id` (skipping rows that would duplicate), then deletes `from_id`.
     * Idempotent and transactional. Call with ?from_id=7&to_id=42
     */
    public function mergeTag(Request $request): JsonResponse
    {
        $fromId = (int) $request->input('from_id', $request->query('from_id', 0));
        $toId = (int) $request->input('to_id', $request->query('to_id', 0));

        if (!$fromId || !$toId) {
            return response()->json([
                'success' => false,
                'message' => 'Se requieren from_id y to_id.',
            ], 422);
        }
        if ($fromId === $toId) {
            return response()->json([
                'success' => false,
                'message' => 'from_id y to_id no pueden ser iguales.',
            ], 422);
        }

        $from = Tag::find($fromId);
        $to = Tag::find($toId);
        if (!$from || !$to) {
            return response()->json([
                'success' => false,
                'message' => 'Uno o ambos tags no existen.',
                'detail' => ['from_found' => !!$from, 'to_found' => !!$to],
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Tours that already have the destination tag (to avoid PK conflict on insert)
            $alreadyHasTo = DB::table('tour_tag')
                ->where('tag_id', $toId)
                ->pluck('tour_id')
                ->toArray();

            // Move pivot rows: tour_tag(from_id) → tour_tag(to_id), skipping conflicts
            $moved = DB::table('tour_tag')
                ->where('tag_id', $fromId)
                ->whereNotIn('tour_id', $alreadyHasTo)
                ->update(['tag_id' => $toId]);

            // Delete the leftover pivot rows where the tour already had the destination
            $deletedDup = DB::table('tour_tag')
                ->where('tag_id', $fromId)
                ->delete();

            // Finally, delete the source tag (soft delete via SoftDeletes trait)
            $from->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Tag '{$from->slug}' (#{$fromId}) fusionado en '{$to->slug}' (#{$toId}).",
                'stats' => [
                    'moved' => $moved,
                    'deleted_duplicates' => $deletedDup,
                    'tag_deleted' => $from->slug,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al fusionar tags.',
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
