<?php
/**
 * Key-gated seeder for tour option groups (Phase 1 QA).
 *
 * Links a handful of legacy sibling tours under a single canonical parent so
 * the new option-selector UI on the detail page has real data to render.
 * Mirrors migrate.php / purge-cache.php for root detection + key gating.
 *
 * Usage:
 *   https://api.incalake.com/seed-tour-options.php?key=<DEPLOY_HOOK_KEY>            (apply)
 *   https://api.incalake.com/seed-tour-options.php?key=<DEPLOY_HOOK_KEY>&action=preview  (dry-run)
 *   https://api.incalake.com/seed-tour-options.php?key=<DEPLOY_HOOK_KEY>&action=rollback (un-link)
 *
 * Idempotent — re-running with `apply` overwrites the same fields to the
 * same values; safe. `rollback` resets parent_tour_id, option_label,
 * option_color back to NULL for every touched tour.
 */

header('Content-Type: application/json; charset=utf-8');

// --- Locate Laravel root (same probe as migrate.php) ---
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

// --- Key gate ---
$expected = '';
$envFile = $appBase . '/.env';
if (is_file($envFile)) {
    $raw = (string) @file_get_contents($envFile);
    if (preg_match('/^\s*(?:export\s+)?DEPLOY_HOOK_KEY\s*=\s*(.*)$/m', $raw, $mm)) {
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
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

// --- Seed payload ---
// Each row: [tour_id, parent_tour_id_or_null, option_label, option_color]
// Three groups: Uros+Taquile clásico (parent 306), Lancha Rápida (parent 122),
// Amantani 2D1N (parent 86). Verified live IDs from prod listing.
$rows = [
    // Group A — Uros + Taquile clásico 1D
    [306, null, 'Compartido',     'blue'],
    [120, 306,  '+ Guía Privado', 'violet'],
    [121, 306,  'Privado',        'amber'],
    // Group B — Uros + Taquile Lancha Rápida
    [122, null, '+ Guía Privado', 'violet'],
    [123, 122,  'Privado',        'amber'],
    // Group C — Uros + Amantani + Taquile 2D1N
    [86,  null, '+ Guía Privado', 'violet'],
    [87,  86,   'Privado',        'amber'],
];

$action = isset($_GET['action']) ? (string) $_GET['action'] : 'apply';

try {
    require $appBase . '/vendor/autoload.php';
    /** @var \Illuminate\Foundation\Application $app */
    $app = require $appBase . '/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    $db = $app->make(\Illuminate\Database\DatabaseManager::class);
    $existingIds = collect($rows)->pluck(0)->all();
    $before = $db->table('tours')
        ->whereIn('id', $existingIds)
        ->select('id', 'parent_tour_id', 'option_label', 'option_color')
        ->get()
        ->keyBy('id')
        ->all();

    if ($action === 'preview') {
        echo json_encode([
            'success' => true,
            'action' => 'preview',
            'rows' => $rows,
            'current_state' => $before,
        ], JSON_PRETTY_PRINT);
        exit;
    }

    if ($action === 'rollback') {
        $affected = $db->table('tours')
            ->whereIn('id', $existingIds)
            ->update([
                'parent_tour_id' => null,
                'option_label'   => null,
                'option_color'   => null,
            ]);
        $cache = $app->make(\App\Services\CacheService::class);
        $cache::bumpToursVersion();
        echo json_encode([
            'success' => true,
            'action' => 'rollback',
            'affected_rows' => $affected,
        ]);
        exit;
    }

    // apply
    $applied = [];
    $missing = [];
    foreach ($rows as [$id, $parentId, $label, $color]) {
        if (!isset($before[$id])) {
            $missing[] = $id;
            continue;
        }
        $db->table('tours')->where('id', $id)->update([
            'parent_tour_id' => $parentId,
            'option_label'   => $label,
            'option_color'   => $color,
        ]);
        $applied[] = ['id' => $id, 'parent_tour_id' => $parentId, 'option_label' => $label, 'option_color' => $color];
    }

    // Invalidate the public listing cache so children stop appearing.
    $cache = $app->make(\App\Services\CacheService::class);
    $cache::bumpToursVersion();

    echo json_encode([
        'success' => true,
        'action' => 'apply',
        'applied' => $applied,
        'missing_ids' => $missing,
        'note' => count($missing) ? 'Some tour IDs were not found in the database — skipped.' : 'All groups linked.',
    ], JSON_PRETTY_PRINT);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al aplicar el seed.',
        'error' => $e->getMessage(),
    ]);
}
