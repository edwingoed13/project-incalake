<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Tour;

echo "=== VERIFICACIÓN DE TOURS ===\n";
echo "Tours activos (sin eliminar): " . Tour::count() . "\n";
echo "Tours totales (incluyendo eliminados): " . Tour::withTrashed()->count() . "\n\n";

echo "Detalle de todos los tours:\n";
echo "----------------------------\n";

$tours = Tour::withTrashed()->get();
foreach($tours as $tour) {
    echo "ID: " . $tour->id . "\n";
    echo "Código: " . $tour->code . "\n";
    echo "Estado: " . ($tour->deleted_at ? "ELIMINADO (soft delete)" : "ACTIVO") . "\n";
    if ($tour->deleted_at) {
        echo "Fecha eliminación: " . $tour->deleted_at . "\n";
    }
    echo "----------------------------\n";
}

echo "\nNOTA: Los tours con 'soft delete' no aparecen en el panel pero siguen en la BD.\n";