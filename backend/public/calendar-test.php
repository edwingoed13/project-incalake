<?php
/**
 * Standalone Google Calendar diagnostic (no Laravel HTTP / no sanctum).
 *
 * The admin calendar (reservas@incalake.com) silently gets no events because
 * storage/app/google-calendar-credentials.json is a SECRET that is NOT in git,
 * so it's absent on cPanel after the GitHub-FTP deploy -> getAccessToken()
 * fails. The Laravel route version is unusable from a browser (behind
 * auth:sanctum, no `login` route -> 500). This script boots only the Laravel
 * container (no HTTP kernel/middleware) and calls the service directly.
 *
 * Usage: https://api.incalake.com/calendar-test.php?key=<DEPLOY_HOOK_KEY>
 */

header('Content-Type: application/json; charset=utf-8');

$appBase = realpath(__DIR__ . '/../incalake-api') ?: realpath(__DIR__ . '/..');
if (!$appBase || !is_dir($appBase)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'App base not found']);
    exit;
}

// --- Key gate: read DEPLOY_HOOK_KEY straight from .env (no Laravel) ---
$expected = '';
$envFile = $appBase . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(ltrim($line), 'DEPLOY_HOOK_KEY=') === 0) {
            $expected = trim(trim(substr(trim($line), strlen('DEPLOY_HOOK_KEY='))), "\"'");
            break;
        }
    }
}
$given = isset($_GET['key']) ? (string) $_GET['key'] : (string) ($_POST['key'] ?? '');
if ($expected === '' || !hash_equals($expected, $given)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => $expected === ''
            ? 'DEPLOY_HOOK_KEY no está configurado en el .env del servidor.'
            : 'Forbidden: clave inválida o ausente.',
    ]);
    exit;
}

// --- Inspect the credentials file BEFORE booting Laravel ---
$credPath = $appBase . '/storage/app/google-calendar-credentials.json';
$credExists = is_file($credPath);
$serviceAccountEmail = null;
if ($credExists) {
    $j = json_decode((string) @file_get_contents($credPath), true);
    $serviceAccountEmail = $j['client_email'] ?? null;
}

if (!$credExists || !$serviceAccountEmail) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'credentials_file_present' => $credExists,
        'service_account_email' => $serviceAccountEmail,
        'expected_path' => 'incalake-api/storage/app/google-calendar-credentials.json',
        'message' => !$credExists
            ? 'Falta el archivo de credenciales en el servidor. Súbelo por el File Manager de cPanel a incalake-api/storage/app/google-calendar-credentials.json (es secreto, no viaja por el deploy de GitHub).'
            : 'El archivo de credenciales no tiene client_email válido.',
    ]);
    exit;
}

// --- Boot only the container (no HTTP kernel -> no sanctum, no routes) ---
try {
    require $appBase . '/vendor/autoload.php';
    $app = require $appBase . '/bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    $calendarId = config('services.google_calendar.calendar_id', 'reservas@incalake.com');

    $ok = (new \App\Services\GoogleCalendarService())->createBookingEvent([
        'booking_code'   => 'TEST-' . date('His'),
        'tour_title'     => 'Evento de prueba Incalake (ignorar / borrar)',
        'tour_date'      => date('Y-m-d', strtotime('+1 day')),
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

    echo json_encode([
        'success' => $ok,
        'credentials_file_present' => true,
        'service_account_email' => $serviceAccountEmail,
        'calendar_id' => $calendarId,
        'message' => $ok
            ? 'OK: evento de prueba creado. Revisa el Google Calendar de ' . $calendarId . ' y borra el evento TEST.'
            : 'Credenciales presentes pero el evento NO se creó. Comparte el calendario ' . $calendarId
              . ' con ' . $serviceAccountEmail . ' (permiso "Hacer cambios en los eventos"). Detalle en storage/logs/laravel.log.',
    ], JSON_UNESCAPED_SLASHES);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'credentials_file_present' => true,
        'service_account_email' => $serviceAccountEmail,
        'message' => 'Excepción al ejecutar la prueba.',
        'error' => $e->getMessage(),
    ]);
}
