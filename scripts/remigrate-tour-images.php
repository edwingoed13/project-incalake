<?php
/**
 * Re-migración de imágenes de tours: la importación original copió las
 * variantes 800x400 (short-slider/thumbs) — este script busca las versiones
 * de mayor resolución en la galería legacy local y las sube a prod vía el
 * endpoint admin /api/admin/tours/{id}/replace-gallery.
 *
 * Requisitos: MySQL local (3306) con las tablas legacy en inc0910d_cms_incalake,
 * copia legacy en D:\incalake-v2\..., y credenciales admin del API.
 *
 * Uso:
 *   php remigrate-tour-images.php --dry             # reporte sin subir nada
 *   php remigrate-tour-images.php --tour=259 --run  # un solo tour
 *   php remigrate-tour-images.php --run             # todos los tours activos
 * Credenciales: variables de entorno ADMIN_EMAIL / ADMIN_PASSWORD.
 */

const API = 'https://api.incalake.com/api';
const LEGACY_ROOT = 'D:/incalake-v2/public_html/apps-incalake/web/galeria';
const MIN_WIDTH = 900;   // por debajo de esto no vale la pena reemplazar
const MAX_IMAGES = 10;   // tope por tour

$opts = getopt('', ['dry', 'run', 'tour::']);
$dryRun = !isset($opts['run']);
$onlyTour = isset($opts['tour']) ? (int) $opts['tour'] : null;

// ---------- helpers ----------
function api_json(string $url, array $post = null, string $token = null): array {
    $ch = curl_init($url);
    $headers = ['Accept: application/json'];
    if ($token) $headers[] = 'Authorization: Bearer ' . $token;
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 60,
    ]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post); // array => multipart
    }
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode((string) $raw, true) ?: [];
    $data['_http'] = $code;
    return $data;
}

// ---------- 1) índice de archivos legacy (basename → mejor archivo) ----------
echo "Indexando galería legacy...\n";
$index = []; // lowercase basename => ['path' =>, 'w' =>, 'h' =>]
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(LEGACY_ROOT, FilesystemIterator::SKIP_DOTS));
foreach ($rii as $f) {
    if (!$f->isFile()) continue;
    $ext = strtolower($f->getExtension());
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) continue;
    if (stripos($f->getPathname(), DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR) !== false) continue;
    $key = strtolower($f->getBasename());
    $size = @getimagesize($f->getPathname());
    if (!$size) continue;
    [$w, $h] = $size;
    if (!isset($index[$key]) || ($w * $h) > ($index[$key]['w'] * $index[$key]['h'])) {
        $index[$key] = ['path' => $f->getPathname(), 'w' => $w, 'h' => $h];
    }
}
echo 'Archivos indexados: ' . count($index) . "\n\n";

// ---------- 2) conexiones ----------
$db = new mysqli('127.0.0.1', 'root', '', 'inc0910d_cms_incalake', 3306);
if ($db->connect_error) exit("MySQL local: {$db->connect_error}\n");

$token = null;
if (!$dryRun) {
    $email = getenv('ADMIN_EMAIL') ?: 'admin@incalake.com';
    $pass = getenv('ADMIN_PASSWORD') ?: exit("Falta ADMIN_PASSWORD\n");
    $login = api_json(API . '/auth/login', ['email' => $email, 'password' => $pass]);
    $token = $login['data']['token'] ?? $login['token'] ?? $login['access_token'] ?? null;
    if (!$token) exit('Login falló: ' . json_encode($login) . "\n");
    echo "Login admin OK\n";
}

// ---------- 3) tours de prod ----------
$tours = [];
for ($page = 1; $page <= 5; $page++) {
    $res = api_json(API . '/tours?active=1&per_page=100&language=ES&page=' . $page);
    $data = $res['data']['data'] ?? $res['data'] ?? [];
    if (!$data) break;
    foreach ($data as $t) $tours[] = ['id' => $t['id'], 'slug' => $t['slug'] ?? '', 'title' => $t['title'] ?? ''];
    if (count($data) < 100) break;
}
echo 'Tours activos en prod: ' . count($tours) . "\n\n";

// ---------- 4) mapeo + subida ----------
$stmt = $db->prepare(
    'SELECT g.url_archivo FROM galeria_has_producto ghp
     JOIN galeria g ON g.id_galeria = ghp.id_galeria
     JOIN producto p ON p.id_producto = ghp.id_producto
     JOIN servicio s ON s.id_servicio = p.id_servicio
     WHERE s.uri_servicio = ?
     ORDER BY ghp.id_galeria_has_producto'
);

$summary = ['ok' => 0, 'sin_match' => 0, 'sin_grandes' => 0, 'errores' => 0];
$pendingManual = [];

foreach ($tours as $t) {
    if ($onlyTour && $t['id'] !== $onlyTour) continue;

    $baseSlug = preg_replace('/-\d+$/', '', $t['slug']);
    $legacy = [];
    foreach (array_unique([$t['slug'], $baseSlug]) as $slug) {
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        if ($rows) { $legacy = array_column($rows, 'url_archivo'); break; }
    }

    if (!$legacy) {
        $summary['sin_match']++;
        echo "[{$t['id']}] {$t['title']}: SIN MATCH legacy (slug: {$t['slug']})\n";
        continue;
    }

    // Mejor archivo por imagen, solo los grandes.
    $keepers = [];
    foreach ($legacy as $file) {
        $best = $index[strtolower($file)] ?? null;
        // Grandes Y con proporción de foto: los banners del slider legacy son
        // 1200x400 (ratio 3:1) y reventarían igual al recortarse a tarjeta.
        if ($best && $best['w'] >= MIN_WIDTH && ($best['w'] / max(1, $best['h'])) <= 2.0) {
            $keepers[] = $best + ['name' => $file];
        }
        if (count($keepers) >= MAX_IMAGES) break;
    }

    if (!$keepers) {
        $summary['sin_grandes']++;
        $pendingManual[] = "[{$t['id']}] {$t['title']}";
        echo "[{$t['id']}] {$t['title']}: sin versiones grandes (" . count($legacy) . " legacy) — conservar actuales\n";
        continue;
    }

    echo "[{$t['id']}] {$t['title']}: " . count($keepers) . '/' . count($legacy) . " imágenes grandes";
    if ($dryRun) {
        echo ' [DRY] ej: ' . $keepers[0]['w'] . 'x' . $keepers[0]['h'] . ' ' . basename($keepers[0]['path']) . "\n";
        $summary['ok']++;
        continue;
    }

    $failed = false;
    foreach ($keepers as $i => $img) {
        $res = api_json(API . "/admin/tours/{$t['id']}/replace-gallery", [
            'image' => new CURLFile($img['path']),
            'clear_first' => $i === 0 ? '1' : '0',
            'is_primary' => $i === 0 ? '1' : '0',
            'order' => (string) ($i + 1),
            'alt_text' => $t['title'],
        ], $token);
        if (empty($res['success'])) {
            echo "\n   ERROR subiendo {$img['name']}: HTTP {$res['_http']} " . json_encode($res['message'] ?? $res) . "\n";
            $failed = true;
            break;
        }
    }
    echo $failed ? "   → INCOMPLETO\n" : " → subidas\n";
    $summary[$failed ? 'errores' : 'ok']++;
    usleep(300000); // no saturar el hosting
}

echo "\n===== RESUMEN =====\n";
foreach ($summary as $k => $v) echo "$k: $v\n";
if ($pendingManual) {
    echo "\nTours SIN fotos grandes en el legacy (necesitan fotos nuevas manuales):\n" . implode("\n", $pendingManual) . "\n";
}
