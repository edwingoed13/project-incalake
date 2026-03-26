<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Tours en BD: " . App\Models\Tour::count() . "\n";

$lastTour = App\Models\Tour::latest()->first();
if ($lastTour) {
    echo "Último tour: " . $lastTour->code . " - Creado: " . $lastTour->created_at . "\n";
} else {
    echo "No hay tours en la BD\n";
}