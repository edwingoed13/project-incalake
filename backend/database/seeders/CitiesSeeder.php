<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitiesSeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            ['name' => 'Puno', 'country_code' => 'PE', 'timezone' => 'America/Lima', 'latitude' => -15.8402, 'longitude' => -70.0219, 'active' => true],
            ['name' => 'Cusco', 'country_code' => 'PE', 'timezone' => 'America/Lima', 'latitude' => -13.5319, 'longitude' => -71.9675, 'active' => true],
            ['name' => 'Lima', 'country_code' => 'PE', 'timezone' => 'America/Lima', 'latitude' => -12.0464, 'longitude' => -77.0428, 'active' => true],
            ['name' => 'Arequipa', 'country_code' => 'PE', 'timezone' => 'America/Lima', 'latitude' => -16.4090, 'longitude' => -71.5375, 'active' => true],
            ['name' => 'Juliaca', 'country_code' => 'PE', 'timezone' => 'America/Lima', 'latitude' => -15.5000, 'longitude' => -70.1333, 'active' => true],
        ];

        foreach ($cities as $city) {
            DB::table('cities')->insert(array_merge($city, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $this->command->info('✓ 5 ciudades de Perú creadas');
    }
}
