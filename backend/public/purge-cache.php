<?php
/**
 * Standalone cache purger for cPanel (no SSH, no Laravel boot).
 *
 * The backend deploys via GitHub Action -> FTP, which never runs `artisan`,
 * so the server keeps serving STALE compiled Blade views + cached config
 * (this is why deployed email/template fixes "didn't apply"). The Laravel
 * route version is unusable here: it sits behind auth:sanctum (browser hit ->
 * redirect to missing `login` route -> 500) and, if config is cached, can't
 * even read its own key. This script bypasses all of that: it just deletes
 * the cache files on disk. Laravel regenerates them on the next request.
 *
 * Usage:  https://api.incalake.com/purge-cache.php?key=<DEPLOY_HOOK_KEY>
 * The key is read straight from the .env file (works even with cached config).
 */

header('Content-Type: application/json; charset=utf-8');

// Locate the Laravel root. realpath() is unreliable on cPanel (open_basedir
// makes ../ paths return false), so probe string candidates with is_file()
// and detect the root by bootstrap/app.php (always deployed).
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
if (!$appBase) { // fallback: first candidate that has a .env
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
    // Tolerate: optional `export `, spaces around `=`, quotes, CRLF/BOM.
    if (preg_match('/^\s*(?:export\s+)?DEPLOY_HOOK_KEY\s*=\s*(.*)$/m', $raw, $mm)) {
        $keyLineFound = true;
        $expected = trim($mm[1]);
        $expected = trim($expected, "\"'");          // strip quotes
        $expected = preg_replace('/\s+#.*$/', '', $expected); // strip inline comment
        $expected = trim($expected, "\r\n\t ");
    }
}
if ($expected === '') { // fallback: real process env (SetEnv / cPanel env)
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
        // Non-sensitive diagnostics (no key value is exposed):
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

// --- Delete cache files (only known, safe paths) ---
$deleted = [];

// 1) bootstrap/cache compiled files (config/routes/packages/services)
$bootCache = $appBase . '/bootstrap/cache';
if (is_dir($bootCache)) {
    foreach (glob($bootCache . '/*.php') ?: [] as $f) {
        if (@unlink($f)) { $deleted[] = 'bootstrap/cache/' . basename($f); }
    }
}

// 2) compiled Blade views
$views = $appBase . '/storage/framework/views';
if (is_dir($views)) {
    foreach (glob($views . '/*.php') ?: [] as $f) {
        if (@unlink($f)) { $deleted[] = 'views/' . basename($f); }
    }
}

// 3) framework file-cache data (optional, safe)
$dataCache = $appBase . '/storage/framework/cache/data';
if (is_dir($dataCache)) {
    $rii = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dataCache, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($rii as $item) {
        if ($item->isFile()) { @unlink($item->getPathname()); }
    }
}

// 4) Reset PHP OPcache so updated .php files (controllers, resources, models)
// take effect IMMEDIATELY after a deploy, instead of waiting for OPcache to
// revalidate timestamps (which may be disabled on the host).
$opcacheReset = false;
if (function_exists('opcache_reset')) {
    $opcacheReset = @opcache_reset();
}

echo json_encode([
    'success' => true,
    'message' => 'Cachés purgadas. Laravel las regenera en la próxima petición.',
    'app_base' => $appBase,
    'deleted_count' => count($deleted),
    'deleted' => array_slice($deleted, 0, 50),
    'opcache_reset' => $opcacheReset,
]);
