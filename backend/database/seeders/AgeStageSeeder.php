<?php

namespace Database\Seeders;

use App\Models\AgeStage;
use Illuminate\Database\Seeder;

class AgeStageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ageStages = [
            [
                'description' => 'Adulto',
                'min_age' => 16,
                'max_age' => 99,
                'editable' => false,
                'translations' => null
            ],
            [
                'description' => 'Niño',
                'min_age' => 3,
                'max_age' => 11,
                'editable' => false,
                'translations' => null
            ],
        ];

        foreach ($ageStages as $ageStage) {
            AgeStage::create($ageStage);
        }
    }
}
