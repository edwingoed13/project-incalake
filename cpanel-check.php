<?php
/**
 * cPanel Compatibility Check for Incalake Full-Stack
 * Sube este archivo a tu cPanel (public_html/) y accede desde el navegador
 * Elimínalo después del test por seguridad
 */

header('Content-Type: text/html; charset=utf-8');
$results = [];
$pass = 0;
$fail = 0;
$warn = 0;

function check($name, $ok, $detail = '', $critical = true) {
    global $results, $pass, $fail, $warn;
    if ($ok) { $pass++; $status = 'PASS'; }
    elseif ($critical) { $fail++; $status = 'FAIL'; }
    else { $warn++; $status = 'WARN'; }
    $results[] = ['name' => $name, 'status' => $status, 'detail' => $detail];
}

// 1. PHP Version
$phpVer = PHP_VERSION;
check('PHP Version >= 8.2', version_compare($phpVer, '8.2.0', '>='), "Actual: $phpVer (Laravel 12 requiere 8.2+)");

// 2. Required Extensions
$required = [
    'pdo_mysql' => 'Conexion a MySQL',
    'mbstring' => 'Strings multibyte',
    'openssl' => 'Encriptacion / HTTPS',
    'tokenizer' => 'Parser de PHP',
    'xml' => 'Procesamiento XML',
    'ctype' => 'Validacion de caracteres',
    'json' => 'JSON encode/decode',
    'bcmath' => 'Calculos de precision',
    'fileinfo' => 'Deteccion MIME de archivos',
    'curl' => 'HTTP requests (Culqi, APIs)',
    'dom' => 'Parsing HTML',
];

foreach ($required as $ext => $desc) {
    check("Extension: $ext", extension_loaded($ext), "$desc" . (!extension_loaded($ext) ? ' — ACTIVAR en cPanel > Select PHP Version' : ''));
}

// Optional but recommended
$optional = ['gd' => 'Manipulacion de imagenes', 'imagick' => 'Imagenes avanzadas', 'zip' => 'Compresion ZIP', 'redis' => 'Cache/Queue con Redis', 'intl' => 'Internacionalizacion'];
foreach ($optional as $ext => $desc) {
    check("Extension (opcional): $ext", extension_loaded($ext), $desc, false);
}

// 3. PHP Settings
$memLimit = ini_get('memory_limit');
$memBytes = (int)$memLimit * (stripos($memLimit, 'G') !== false ? 1073741824 : (stripos($memLimit, 'M') !== false ? 1048576 : 1));
check('memory_limit >= 128M', $memBytes >= 134217728, "Actual: $memLimit");

$maxExec = ini_get('max_execution_time');
check('max_execution_time >= 30', $maxExec >= 30 || $maxExec == 0, "Actual: {$maxExec}s");

$uploadMax = ini_get('upload_max_filesize');
check('upload_max_filesize >= 10M', (int)$uploadMax >= 10, "Actual: $uploadMax (para subir imagenes de tours)", false);

$postMax = ini_get('post_max_size');
check('post_max_size >= 20M', (int)$postMax >= 20, "Actual: $postMax", false);

// 4. Directory Permissions
$writableDirs = ['storage', 'storage/logs', 'storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views', 'bootstrap/cache'];
$baseDir = dirname(__FILE__);

// Check if we're in the Laravel directory or public_html
foreach ($writableDirs as $dir) {
    $fullPath = $baseDir . '/' . $dir;
    if (is_dir($fullPath)) {
        check("Directorio escribible: $dir", is_writable($fullPath), is_writable($fullPath) ? 'OK' : 'chmod 775');
    }
}

// 5. MySQL Connection Test
check('MySQL disponible', extension_loaded('pdo_mysql'), 'PDO MySQL driver');

// 6. Composer
$composerExists = file_exists('/usr/local/bin/composer') || file_exists('/usr/bin/composer') || file_exists(__DIR__ . '/composer.phar');
check('Composer disponible', $composerExists, $composerExists ? 'Encontrado' : 'Puedes subir composer.phar manualmente', false);

// 7. Node.js (not required on server - build locally)
check('Node.js (solo para build)', false, 'No necesario en servidor — build en local o CI', false);

// 8. .htaccess / mod_rewrite
check('mod_rewrite (Apache)', function_exists('apache_get_modules') ? in_array('mod_rewrite', apache_get_modules()) : true, function_exists('apache_get_modules') ? '' : 'No se puede verificar (CGI mode) — generalmente habilitado en cPanel', false);

// 9. HTTPS
check('HTTPS disponible', !empty($_SERVER['HTTPS']) || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'), 'Necesario para Culqi 3DS y cookies seguras', false);

// 10. Disk Space
$freeSpace = @disk_free_space(dirname(__FILE__));
if ($freeSpace !== false) {
    $freeGB = round($freeSpace / 1073741824, 2);
    check('Espacio en disco >= 1GB libre', $freeGB >= 1, "{$freeGB} GB libres");
} else {
    check('Espacio en disco', true, 'No se pudo verificar', false);
}

// 11. Symlink support (for storage:link)
check('Symlinks soportados', function_exists('symlink'), 'Necesario para php artisan storage:link');

// 12. proc_open (for artisan commands)
check('proc_open habilitado', function_exists('proc_open'), 'Necesario para Artisan y Composer', false);

// 13. putenv (for .env loading)
check('putenv habilitado', function_exists('putenv'), 'Necesario para cargar variables de .env');

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Incalake - cPanel Compatibility Check</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: -apple-system, 'Segoe UI', sans-serif; background: #f0f2f5; color: #1a1a2e; padding: 20px; }
.wrap { max-width: 700px; margin: 0 auto; }
h1 { font-size: 22px; margin-bottom: 4px; }
.sub { color: #666; font-size: 13px; margin-bottom: 20px; }
.summary { display: flex; gap: 12px; margin-bottom: 20px; }
.summary div { flex: 1; padding: 16px; border-radius: 12px; text-align: center; font-weight: 700; }
.summary .pass { background: #dcfce7; color: #166534; }
.summary .fail { background: #fef2f2; color: #991b1b; }
.summary .warn { background: #fefce8; color: #854d0e; }
.summary .num { font-size: 28px; display: block; }
.card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 12px; overflow: hidden; }
.row { display: flex; align-items: center; padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
.row:last-child { border-bottom: none; }
.badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 700; margin-right: 10px; min-width: 40px; text-align: center; }
.badge.PASS { background: #dcfce7; color: #166534; }
.badge.FAIL { background: #fef2f2; color: #991b1b; }
.badge.WARN { background: #fefce8; color: #854d0e; }
.name { font-weight: 600; flex: 1; }
.detail { color: #64748b; font-size: 12px; }
.verdict { text-align: center; padding: 20px; border-radius: 12px; font-size: 16px; font-weight: 700; margin-top: 16px; }
.verdict.ok { background: #dcfce7; color: #166534; }
.verdict.no { background: #fef2f2; color: #991b1b; }
.warn-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 14px; margin-top: 16px; font-size: 12px; color: #92400e; }
</style>
</head>
<body>
<div class="wrap">
<h1>Incalake — cPanel Compatibility Check</h1>
<p class="sub">Laravel 12 + Nuxt 4 + MySQL — <?= date('Y-m-d H:i:s') ?></p>

<div class="summary">
    <div class="pass"><span class="num"><?= $pass ?></span>OK</div>
    <div class="fail"><span class="num"><?= $fail ?></span>FAIL</div>
    <div class="warn"><span class="num"><?= $warn ?></span>WARN</div>
</div>

<div class="card">
<?php foreach ($results as $r): ?>
<div class="row">
    <span class="badge <?= $r['status'] ?>"><?= $r['status'] ?></span>
    <span class="name"><?= $r['name'] ?></span>
    <?php if ($r['detail']): ?><span class="detail"><?= $r['detail'] ?></span><?php endif; ?>
</div>
<?php endforeach; ?>
</div>

<div class="verdict <?= $fail === 0 ? 'ok' : 'no' ?>">
    <?= $fail === 0 ? 'Tu cPanel es COMPATIBLE con Incalake' : "HAY $fail PROBLEMAS que resolver antes del deploy" ?>
</div>

<?php if ($fail === 0): ?>
<div class="warn-box">
    <strong>Siguiente paso:</strong> Elimina este archivo (cpanel-check.php) por seguridad y procede con el deploy.
</div>
<?php endif; ?>

</div>
</body>
</html>
