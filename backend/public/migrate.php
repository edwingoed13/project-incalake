<?php
/**
 * Key-gated migration runner for cPanel (no SSH, no artisan in CI).
 *
 * The backend deploys via GitHub Action -> FTP, which never runs `artisan`,
 * so new migrations don't apply on their own. Hit this once after a deploy
 * that adds migrations. Mirrors purge-cache.php for root detection + key
 * gating; unlike that script it DOES boot Laravel so it can run the migrator.
 *
 * Usage:
 *   https://api.incalake.com/migrate.php?key=<DEPLOY_HOOK_KEY>             (run)
 *   https://api.incalake.com/migrate.php?key=<DEPLOY_HOOK_KEY>&action=status
 *
 * Safe to leave deployed (key-gated) or delete after use. Migrations here are
 * idempotent, so re-running is harmless.
 */

header('Content-Type: application/json; charset=utf-8');

// Locate the Laravel root (same probe as purge-cache.php; open_basedir-safe).
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

// --- Read DEPLOY_HOOK_KEY (tolerant): .env file, then process env ---
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
            'env_file_exists' => $envExists,
            'key_line_found_in_env' => $keyLineFound,
            'key_configured' => $expected !== '',
            'key_provided_in_url' => $given !== '',
        ],
    ]);
    exit;
}

// --- Boot Laravel and run the migrator ---
$action = isset($_GET['action']) ? (string) $_GET['action'] : 'migrate';

try {
    require $appBase . '/vendor/autoload.php';
    /** @var \Illuminate\Foundation\Application $app */
    $app = require $appBase . '/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

    if ($action === 'status') {
        $exit = $kernel->call('migrate:status');
        echo json_encode([
            'success' => $exit === 0,
            'action' => 'status',
            'exit_code' => $exit,
            'output' => \Illuminate\Support\Facades\Artisan::output(),
        ]);
        exit;
    }

    $exit = $kernel->call('migrate', ['--force' => true]);
    echo json_encode([
        'success' => $exit === 0,
        'action' => 'migrate',
        'exit_code' => $exit,
        'output' => \Illuminate\Support\Facades\Artisan::output(),
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al ejecutar las migraciones.',
        'error' => $e->getMessage(),
    ]);
}
