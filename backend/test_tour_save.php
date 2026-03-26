<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Tour;
use App\Models\Language;

// Habilitar registro de logs
\DB::enableQueryLog();

echo "Iniciando prueba de guardado de tour...\n";

try {
    \DB::beginTransaction();

    // Obtener el idioma español
    $spanish = Language::where('code', 'ES')->first();
    if (!$spanish) {
        throw new Exception("No se encontró el idioma español");
    }

    $tourData = [
        'code' => 'TEST001',
        'primary_language_id' => $spanish->id,
        'city_id' => 1,
        'city_name' => 'Lima',
        'service_type' => 'tour',
        'status' => 'draft',
        'difficulty' => 'easy',
        'target_audience' => 'all',
        'capacity' => 20,
        'duration_hours' => 4,
        'active' => true
    ];

    echo "Creando tour con datos: " . json_encode($tourData, JSON_PRETTY_PRINT) . "\n";

    $tour = Tour::create($tourData);

    echo "Tour creado con ID: " . $tour->id . "\n";

    // Crear traducción
    $tour->translations()->create([
        'language_id' => $spanish->id,
        'h1_title' => 'Tour de Prueba',
        'meta_title' => 'Tour de Prueba - Test',
        'meta_description' => 'Este es un tour de prueba',
        'slug' => 'tour-de-prueba'
    ]);

    echo "Traducción creada\n";

    \DB::commit();
    echo "✓ Transacción completada exitosamente\n";

    // Verificar que se guardó
    $savedTour = Tour::where('code', 'TEST001')->first();
    if ($savedTour) {
        echo "✓ Tour verificado en BD con ID: " . $savedTour->id . "\n";
    } else {
        echo "✗ No se pudo encontrar el tour en la BD\n";
    }

} catch (\Exception $e) {
    \DB::rollBack();
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
}

// Mostrar queries ejecutadas
$queries = \DB::getQueryLog();
echo "\nQueries ejecutadas: " . count($queries) . "\n";
foreach ($queries as $query) {
    echo "- " . $query['query'] . "\n";
}

echo "\nTotal de tours en BD: " . Tour::count() . "\n";