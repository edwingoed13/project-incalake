<?php
/**
 * Key-gated runner for scheduled tasks on cPanel.
 *
 * This host has no cron running Laravel's scheduler, so recurring work has no
 * way to fire on its own. A GitHub Actions workflow calls this on a schedule
 * instead. Gated by the same DEPLOY_HOOK_KEY as migrate.php, which is already
 * configured on the server and in the repository secrets — the API's own
 * maintenance routes need an admin session, which a workflow doesn't have.
 *
 * Usage:
 *   https://api.incalake.com/cron.php?key=<DEPLOY_HOOK_KEY>&task=expiry-alerts
 *   …&task=expiry-alerts&dry_run=1     (report without sending or marking)
 *
 * Tasks are expected to be safe to call more than once: the expiry alert
 * decides what to send from expiry_alert_sent_at, not from how often this runs.
 */
header('Content-Type: application/json; charset=utf-8');

// Locate the Laravel root (same probe as migrate.php; open_basedir-safe).
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
foreach ($candidates as $c) {
    if (is_file($c . '/bootstrap/app.php')) { $appBase = $c; break; }
}
if (!$appBase) {
    foreach ($candidates as $c) {
        if (is_file($c . '/.env')) { $appBase = $c; break; }
    }
}
if (!$appBase) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se encontró la raíz de Laravel.']);
    exit;
}

// --- Read DEPLOY_HOOK_KEY (tolerant): .env file, then process env ---
$expected = '';
$envFile = $appBase . '/.env';
if (is_file($envFile)) {
    $raw = (string) @file_get_contents($envFile);
    if (preg_match('/^\s*(?:export\s+)?DEPLOY_HOOK_KEY\s*=\s*(.*)$/m', $raw, $mm)) {
        $expected = trim(preg_replace('/\s+#.*$/', '', trim(trim($mm[1]), "\"'")));
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
    ]);
    exit;
}

// Allow-list rather than passing the query straight to Artisan: this endpoint
// is reachable by anyone holding the key, and it should not become a way to
// run arbitrary commands.
$tasks = [
    'expiry-alerts' => 'tours:expiry-alerts',
];

$task = isset($_GET['task']) ? (string) $_GET['task'] : '';
if (!isset($tasks[$task])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Tarea desconocida.',
        'available' => array_keys($tasks),
    ]);
    exit;
}

try {
    require $appBase . '/vendor/autoload.php';
    /** @var \Illuminate\Foundation\Application $app */
    $app = require $appBase . '/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

    $args = [];
    if (!empty($_GET['dry_run'])) {
        $args['--dry-run'] = true;
    }

    $exit = $kernel->call($tasks[$task], $args);

    echo json_encode([
        'success' => $exit === 0,
        'task' => $task,
        'exit_code' => $exit,
        'output' => \Illuminate\Support\Facades\Artisan::output(),
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'task' => $task,
        'message' => 'Error al ejecutar la tarea.',
        'error' => $e->getMessage(),
    ]);
}
