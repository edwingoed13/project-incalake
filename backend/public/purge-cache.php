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

// Public docroot is .../public_html/api.incalake.com/ ; the Laravel app lives
// next to it at ../incalake-api/ (see deploy-backend.yml index.php bootstrap).
$appBase = realpath(__DIR__ . '/../incalake-api') ?: realpath(__DIR__ . '/..');

if (!$appBase || !is_dir($appBase)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'App base not found']);
    exit;
}

// --- Read DEPLOY_HOOK_KEY directly from .env (no Laravel) ---
$expected = '';
$envFile = $appBase . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(ltrim($line), 'DEPLOY_HOOK_KEY=') === 0) {
            $expected = trim(substr(trim($line), strlen('DEPLOY_HOOK_KEY=')));
            $expected = trim($expected, "\"'"); // strip optional quotes
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

echo json_encode([
    'success' => true,
    'message' => 'Cachés purgadas. Laravel las regenera en la próxima petición.',
    'app_base' => $appBase,
    'deleted_count' => count($deleted),
    'deleted' => array_slice($deleted, 0, 50),
]);
