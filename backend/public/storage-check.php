<?php
/**
 * Standalone storage diagnostic for cPanel (no SSH, no Laravel boot).
 *
 * Tiptap content-image uploads land at /storage/tours/temp/<uuid> and 404,
 * while gallery images at /storage/tours/<id> serve fine. This reports the
 * real filesystem layout so we can see WHERE the public disk writes vs WHAT
 * the docroot /storage actually serves.
 *
 * Usage: https://api.incalake.com/storage-check.php?key=<DEPLOY_HOOK_KEY>
 *        &file=tours/temp/<uuid>.jpeg   (optional: check a specific path)
 */

header('Content-Type: application/json; charset=utf-8');

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

// --- Key gate (same tolerant parser as purge-cache.php) ---
$expected = '';
$envFile = $appBase . '/.env';
if (is_file($envFile)) {
    $raw = (string) @file_get_contents($envFile);
    if (preg_match('/^\s*(?:export\s+)?DEPLOY_HOOK_KEY\s*=\s*(.*)$/m', $raw, $mm)) {
        $expected = trim(trim($mm[1]), "\"'");
        $expected = trim(preg_replace('/\s+#.*$/', '', $expected), "\r\n\t ");
    }
    if (preg_match('/^\s*(?:export\s+)?APP_URL\s*=\s*(.*)$/m', $raw, $au)) {
        $appUrl = trim(trim($au[1]), "\"'");
    }
    if (preg_match('/^\s*(?:export\s+)?PUBLIC_DISK_ROOT\s*=\s*(.*)$/m', $raw, $pr)) {
        $publicDiskRoot = trim(trim($pr[1]), "\"'");
    }
}
if ($expected === '') {
    $expected = (string) (getenv('DEPLOY_HOOK_KEY') ?: ($_SERVER['DEPLOY_HOOK_KEY'] ?? $_ENV['DEPLOY_HOOK_KEY'] ?? ''));
}
$given = isset($_GET['key']) ? (string) $_GET['key'] : '';
if ($expected === '' || !hash_equals($expected, $given)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: clave inválida o ausente.']);
    exit;
}

// --- Diagnostics ---
$pubRoot       = $appBase . '/storage/app/public';     // where the 'public' disk writes
$docrootLink   = __DIR__ . '/storage';                  // what /storage serves from
$toursAppDir   = $pubRoot . '/tours';
$tempAppDir    = $pubRoot . '/tours/temp';

$lsApp = is_dir($toursAppDir) ? array_slice(array_values(array_diff(scandir($toursAppDir) ?: [], ['.', '..'])), 0, 25) : null;
$lsTemp = is_dir($tempAppDir) ? array_slice(array_values(array_diff(scandir($tempAppDir) ?: [], ['.', '..'])), 0, 25) : null;

// Test write directly into the public disk, mimicking what storeAs does.
$testRel = 'tours/temp/_diag_' . time() . '.txt';
$testAbs = $pubRoot . '/' . $testRel;
@mkdir(dirname($testAbs), 0775, true);
$wrote = @file_put_contents($testAbs, 'diag ' . date('c'));
$testPerms = is_file($testAbs) ? substr(sprintf('%o', fileperms($testAbs)), -4) : null;

// Can we reach that same file through the docroot /storage path?
$viaDocroot = $docrootLink . '/' . $testRel;
$reachableViaDocroot = is_file($viaDocroot);

$checkFile = isset($_GET['file']) ? preg_replace('#[^a-zA-Z0-9/_.\-]#', '', (string) $_GET['file']) : null;

// Test writing into the DOCROOT served storage (where the fix points the disk).
$docTestRel = 'tours/temp/_docdiag_' . time() . '.txt';
$docTestAbs = $docrootLink . '/' . $docTestRel;
@mkdir(dirname($docTestAbs), 0775, true);
$docWrote = @file_put_contents($docTestAbs, 'docdiag ' . date('c'));
$docTestUrl = ($appUrl ?? 'https://api.incalake.com') . '/storage/' . $docTestRel;

echo json_encode([
    'success' => true,
    'app_base' => $appBase,
    'app_url_in_env' => $appUrl ?? null,
    'public_disk_root_in_env' => $publicDiskRoot ?? '(NOT SET — add it to .env)',
    'script_dir_docroot' => __DIR__,

    // Verifies the fix end-to-end: write into the served docroot dir and
    // expose the URL so we can confirm it serves (200) over HTTP.
    'docroot_write_test' => [
        'rel' => $docTestRel,
        'bytes_written' => $docWrote,
        'exists_on_disk' => is_file($docTestAbs),
        'public_url_to_try' => $docTestUrl,
    ],

    'public_disk_root' => [
        'path' => $pubRoot,
        'realpath' => realpath($pubRoot) ?: null,
        'is_dir' => is_dir($pubRoot),
        'writable' => is_writable($pubRoot),
    ],

    'docroot_storage' => [
        'path' => $docrootLink,
        'exists' => file_exists($docrootLink),
        'is_link' => is_link($docrootLink),
        'link_target' => is_link($docrootLink) ? @readlink($docrootLink) : null,
        'realpath' => realpath($docrootLink) ?: null,
        'is_dir' => is_dir($docrootLink),
    ],

    // The crux: does the public disk write to the SAME place the docroot serves?
    'app_storage_equals_docroot_storage' => (realpath($pubRoot) && realpath($docrootLink))
        ? (realpath($pubRoot) === realpath($docrootLink))
        : 'cannot_compare',

    'test_write' => [
        'rel' => $testRel,
        'abs' => $testAbs,
        'bytes_written' => $wrote,
        'exists_on_disk' => is_file($testAbs),
        'perms' => $testPerms,
        'reachable_via_docroot_path' => $reachableViaDocroot,
        'docroot_check_path' => $viaDocroot,
    ],

    'tours_dir_app_side' => ['path' => $toursAppDir, 'is_dir' => is_dir($toursAppDir), 'entries' => $lsApp],
    'temp_dir_app_side'  => ['path' => $tempAppDir, 'is_dir' => is_dir($tempAppDir), 'entries' => $lsTemp],

    'specific_file' => $checkFile ? [
        'rel' => $checkFile,
        'app_side_exists' => is_file($pubRoot . '/' . $checkFile),
        'docroot_side_exists' => is_file($docrootLink . '/' . $checkFile),
    ] : null,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
