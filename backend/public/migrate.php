<?php
/**
 * Standalone DB migrator for cPanel (no SSH).
 *
 * cPanel + GitHub-FTP deploy never runs `artisan`, so new migrations (e.g.
 * the `meeting_points` column) are NEVER applied in production -> the tour
 * wizard's save fails with "Unknown column 'meeting_points'". The Laravel
 * route /admin/maintenance/run-migrations is unusable from a browser (behind
 * auth:sanctum, no `login` route -> 500). This boots only the Laravel
 * container (no HTTP kernel/middleware) and runs `migrate --force`.
 *
 * Usage:
 *   https://api.incalake.com/migrate.php?key=<DEPLOY_HOOK_KEY>            (run)
 *   https://api.incalake.com/migrate.php?key=<DEPLOY_HOOK_KEY>&status=1   (dry: status only)
 *
 * Migrations applied here are additive (nullable column adds / enum expand),
 * non-destructive.
 */

header('Content-Type: application/json; charset=utf-8');

// --- Locate Laravel root (realpath unreliable on cPanel/open_basedir) ---
$candidates = [
    __DIR__ . '/../incalake-api',
    __DIR__ . '/../../incalake-api',
    __DIR__ . '/../../../incalake-api',
    dirname(__DIR__, 2) . '/incalake-api',
    dirname(__DIR__, 3) . '/incalake-api',
    __DIR__ . '/..',
    __DIR__,
];
$appBase = null;
$triedBase = [];
foreach ($candidates as $c) {
    $triedBase[] = $c;
    if (is_file($c . '/bootstrap/app.php')) { $appBase = $c; break; }
}
if (!$appBase) {
    foreach ($candidates as $c) {
        if (is_file($c . '/.env')) { $appBase = $c; break; }
    }
}
if (!$appBase) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'No se encontró la raíz de Laravel.',
        'diag' => ['script_dir' => __DIR__, 'tried' => $triedBase],
    ]);
    exit;
}

// --- Key gate: DEPLOY_HOOK_KEY from .env (tolerant) then process env ---
$expected = '';
$envFile = $appBase . '/.env';
$envExists = is_file($envFile);
$keyLineFound = false;
if ($envExists) {
    $raw = (string) @file_get_contents($envFile);
    if (preg_match('/^\s*(?:export\s+)?DEPLOY_HOOK_KEY\s*=\s*(.*)$/m', $raw, $mm)) {
        $keyLineFound = true;
        $expected = trim($mm[1]);
        $expected = trim($expected, "\"'");
        $expected = preg_replace('/\s+#.*$/', '', $expected);
        $expected = trim($expected, "\r\n\t ");
    }
}
if ($expected === '') {
    $envVal = getenv('DEPLOY_HOOK_KEY') ?: ($_SERVER['DEPLOY_HOOK_KEY'] ?? $_ENV['DEPLOY_HOOK_KEY'] ?? '');
    if ($envVal !== '') { $expected = trim((string) $envVal); }
}
$given = isset($_GET['key']) ? (string) $_GET['key'] : (string) ($_POST['key'] ?? '');
if ($expected === '' || !hash_equals($expected, $given)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => $expected === ''
            ? 'DEPLOY_HOOK_KEY no está configurado/legible en el servidor.'
            : 'Forbidden: clave inválida o ausente.',
        'diag' => [
            'script_dir' => __DIR__,
            'app_base' => $appBase,
            'app_base_tried' => $triedBase,
            'env_file' => $envFile,
            'env_file_exists' => $envExists,
            'key_line_found_in_env' => $keyLineFound,
            'key_configured' => $expected !== '',
            'key_provided_in_url' => $given !== '',
        ],
    ]);
    exit;
}

// --- Boot container only (no HTTP kernel -> no sanctum) and run migrate ---
try {
    require $appBase . '/vendor/autoload.php';
    $app = require $appBase . '/bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    $statusOnly = isset($_GET['status']) && $_GET['status'];

    \Illuminate\Support\Facades\Artisan::call('migrate:status');
    $statusBefore = trim(\Illuminate\Support\Facades\Artisan::output());

    if ($statusOnly) {
        echo json_encode([
            'success' => true,
            'mode' => 'status',
            'status' => $statusBefore,
        ]);
        exit;
    }

    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    $migrateOut = trim(\Illuminate\Support\Facades\Artisan::output());

    echo json_encode([
        'success' => true,
        'mode' => 'migrate',
        'message' => 'Migraciones ejecutadas. Si dice "Nothing to migrate." ya estaban aplicadas.',
        'status_before' => $statusBefore,
        'output' => $migrateOut,
    ], JSON_UNESCAPED_SLASHES);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Excepción al migrar.',
        'error' => $e->getMessage(),
    ]);
}
