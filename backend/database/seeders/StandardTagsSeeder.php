<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

/**
 * Seeds the 15 standardized tourism tags based on the convergent taxonomy
 * used by major OTAs (Viator, GetYourGuide, Airbnb Experiences, Booking).
 * Each tag has translations in the 6 supported languages.
 */
class StandardTagsSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            // Tipo de tour
            ['slug' => 'aventura', 'translations' => ['ES' => 'Aventura', 'EN' => 'Adventure', 'FR' => 'Aventure', 'DE' => 'Abenteuer', 'PT' => 'Aventura', 'IT' => 'Avventura']],
            ['slug' => 'cultural', 'translations' => ['ES' => 'Cultural', 'EN' => 'Cultural', 'FR' => 'Culturel', 'DE' => 'Kultur', 'PT' => 'Cultural', 'IT' => 'Culturale']],
            ['slug' => 'naturaleza', 'translations' => ['ES' => 'Naturaleza', 'EN' => 'Nature', 'FR' => 'Nature', 'DE' => 'Natur', 'PT' => 'Natureza', 'IT' => 'Natura']],
            ['slug' => 'gastronomico', 'translations' => ['ES' => 'Gastronómico', 'EN' => 'Food & Drink', 'FR' => 'Gastronomique', 'DE' => 'Gastronomie', 'PT' => 'Gastronômico', 'IT' => 'Gastronomico']],
            ['slug' => 'vivencial', 'translations' => ['ES' => 'Vivencial', 'EN' => 'Local Living', 'FR' => 'Immersif', 'DE' => 'Immersiv', 'PT' => 'Vivencial', 'IT' => 'Esperienziale']],
            ['slug' => 'mistico', 'translations' => ['ES' => 'Místico', 'EN' => 'Mystical', 'FR' => 'Mystique', 'DE' => 'Mystisch', 'PT' => 'Místico', 'IT' => 'Mistico']],

            // Modalidad
            ['slug' => 'city-tour', 'translations' => ['ES' => 'City Tour', 'EN' => 'City Tour', 'FR' => 'Tour de la ville', 'DE' => 'Stadtrundfahrt', 'PT' => 'City Tour', 'IT' => 'Tour della città']],
            ['slug' => 'dia-completo', 'translations' => ['ES' => 'Día completo', 'EN' => 'Full day', 'FR' => 'Journée complète', 'DE' => 'Ganztägig', 'PT' => 'Dia inteiro', 'IT' => 'Giornata intera']],
            ['slug' => 'multi-dia', 'translations' => ['ES' => 'Multi-día', 'EN' => 'Multi-day', 'FR' => 'Plusieurs jours', 'DE' => 'Mehrtägig', 'PT' => 'Vários dias', 'IT' => 'Più giorni']],
            ['slug' => 'acuatico', 'translations' => ['ES' => 'Acuático', 'EN' => 'Water Activities', 'FR' => 'Activités aquatiques', 'DE' => 'Wasseraktivitäten', 'PT' => 'Aquático', 'IT' => 'Acquatico']],

            // Audiencia
            ['slug' => 'familiar', 'translations' => ['ES' => 'Familiar', 'EN' => 'Family-friendly', 'FR' => 'Famille', 'DE' => 'Familienfreundlich', 'PT' => 'Familiar', 'IT' => 'Famiglie']],
            ['slug' => 'romantico', 'translations' => ['ES' => 'Romántico', 'EN' => 'Romantic', 'FR' => 'Romantique', 'DE' => 'Romantisch', 'PT' => 'Romântico', 'IT' => 'Romantico']],
            ['slug' => 'premium', 'translations' => ['ES' => 'Premium', 'EN' => 'Premium', 'FR' => 'Premium', 'DE' => 'Premium', 'PT' => 'Premium', 'IT' => 'Premium']],
            ['slug' => 'estudiantes', 'translations' => ['ES' => 'Estudiantes', 'EN' => 'Students', 'FR' => 'Étudiants', 'DE' => 'Studenten', 'PT' => 'Estudantes', 'IT' => 'Studenti']],

            // Especial
            ['slug' => 'fotografico', 'translations' => ['ES' => 'Fotográfico', 'EN' => 'Photography', 'FR' => 'Photographie', 'DE' => 'Fotografie', 'PT' => 'Fotográfico', 'IT' => 'Fotografico']],
        ];

        foreach ($tags as $tag) {
            Tag::updateOrCreate(
                ['slug' => $tag['slug']],
                ['translations' => $tag['translations'], 'active' => true],
            );
        }
    }
}
