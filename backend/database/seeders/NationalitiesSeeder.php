<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Nationality;

class NationalitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $nationalities = [
            [
                'description' => 'General',
                'code' => 'GEN',
                'order' => 1,
                'editable' => false,
                'translations' => [
                    'es' => 'General',
                    'en' => 'General',
                    'fr' => 'Général',
                    'de' => 'Allgemein',
                    'pt' => 'Geral',
                    'it' => 'Generale',
                ],
            ],
            [
                'description' => 'Peruano',
                'code' => 'PE',
                'order' => 2,
                'editable' => false,
                'translations' => [
                    'es' => 'Peruano',
                    'en' => 'Peruvian',
                    'fr' => 'Péruvien',
                    'de' => 'Peruanisch',
                    'pt' => 'Peruano',
                    'it' => 'Peruviano',
                ],
            ],
            [
                'description' => 'Latinoamericano',
                'code' => 'LA',
                'order' => 3,
                'editable' => false,
                'translations' => [
                    'es' => 'Latinoamericano',
                    'en' => 'Latin American',
                    'fr' => 'Latino-américain',
                    'de' => 'Lateinamerikanisch',
                    'pt' => 'Latino-americano',
                    'it' => 'Latinoamericano',
                ],
            ],
            [
                'description' => 'Extranjero',
                'code' => 'EXT',
                'order' => 4,
                'editable' => false,
                'translations' => [
                    'es' => 'Extranjero',
                    'en' => 'Foreigner',
                    'fr' => 'Étranger',
                    'de' => 'Ausländer',
                    'pt' => 'Estrangeiro',
                    'it' => 'Straniero',
                ],
            ],
        ];

        foreach ($nationalities as $nationality) {
            Nationality::create($nationality);
        }
    }
}
