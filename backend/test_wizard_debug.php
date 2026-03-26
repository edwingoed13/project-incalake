<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Livewire\TourWizard;
use App\Models\Language;
use App\Models\City;
use App\Models\AgeStage;

echo "Probando TourWizard...\n";

// Simular el wizard
$wizard = new TourWizard();
$wizard->mount();

echo "Datos iniciales del wizard:\n";
echo "- code: " . ($wizard->code ?: 'vacio') . "\n";
echo "- primary_language_id: " . ($wizard->primary_language_id ?: 'vacio') . "\n";
echo "- city_id: " . ($wizard->city_id ?: 'vacio') . "\n";
echo "- city_name: " . ($wizard->city_name ?: 'vacio') . "\n";
echo "- departure_time: " . ($wizard->departure_time ?: 'vacio') . "\n";
echo "- duration_quantity: " . ($wizard->duration_quantity ?: 'vacio') . "\n";

// Simular llenado del formulario
$spanish = Language::where('code', 'ES')->first();
$wizard->primary_language_id = $spanish->id;
$wizard->code = 'WIZARD001';
$wizard->city_name = 'Lima';
$wizard->departure_time = '08:00';
$wizard->duration_quantity = 4;

// Llenar traducción
$wizard->translations[$spanish->id] = [
    'h1_title' => 'Tour desde Wizard',
    'meta_title' => 'Tour desde Wizard',
    'meta_description' => 'Descripción del tour',
    'slug' => 'tour-desde-wizard',
    'short_description' => '',
    'long_description' => '',
    'og_title' => '',
    'og_description' => '',
    'twitter_title' => '',
    'twitter_description' => '',
    'ads_headline' => '',
    'ads_description' => '',
    'cta_text' => 'Reservar Ahora',
];

echo "\nDatos después de llenar:\n";
echo "- code: " . $wizard->code . "\n";
echo "- primary_language_id: " . $wizard->primary_language_id . "\n";
echo "- city_name: " . $wizard->city_name . "\n";
echo "- departure_time: " . $wizard->departure_time . "\n";
echo "- duration_quantity: " . $wizard->duration_quantity . "\n";

echo "\nIntentando guardar...\n";
try {
    // Invocar save directamente
    $wizard->save();
    echo "✓ Método save() ejecutado\n";
} catch (\Exception $e) {
    echo "✗ Error al guardar: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\nTotal de tours en BD: " . \App\Models\Tour::count() . "\n";