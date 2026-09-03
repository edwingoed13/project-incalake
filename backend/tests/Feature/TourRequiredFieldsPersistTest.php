<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Language;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The tick boxes under "Datos requeridos del cliente" survive creation.
 *
 * StoreTourRequest validated data_requirement but not the two arrays beside it,
 * and the controller stores validated() — so a tour created with fields ticked
 * was born with none of them, silently, until someone saved it a second time
 * through UpdateTourRequest, which did list them. Nothing ever reported it: the
 * wizard showed the operator's own checkboxes back from local state.
 */
class TourRequiredFieldsPersistTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $extra = []): array
    {
        $lang = Language::query()->firstOrCreate(
            ['code' => 'ES'],
            ['name' => 'Español', 'country' => 'PE', 'active' => true]
        );

        $city = City::query()->firstOrCreate(['name' => 'Puno'], ['slug' => 'puno']);

        return array_merge([
            // The wizard asks /generate-code for this before saving.
            'code' => 'ES' . str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT),
            'primary_language_id' => $lang->id,
            'city_id' => $city->id,
            'city_name' => 'Puno',
            'service_type' => 'tour',
            'difficulty' => 'easy',
            'target_audience' => 'all',
            'capacity' => 20,
            'translations' => [
                [
                    'language_id' => $lang->id,
                    'h1_title' => 'Tour a los Uros',
                    'slug' => 'tour-a-los-uros',
                    'meta_title' => 'Tour a los Uros',
                    'meta_description' => 'Un dia en las islas flotantes.',
                ],
            ],
            // required|array, so it cannot be empty, but the rows themselves
            // are beside the point here: this is about the fields the operator
            // ticked, not about pricing.
            'prices' => [['active' => false, 'ranges' => []]],
        ], $extra);
    }

    public function test_the_ticked_fields_survive_creating_the_tour(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $res = $this->postJson('/api/admin/tours', $this->payload([
            'data_requirement' => 2,
            'personal_info_required' => ['nationality', 'birthdate'],
            'operational_info_required' => ['hotel_name'],
        ]));

        $res->assertSuccessful();

        $tour = Tour::query()->latest('id')->first();
        $this->assertSame(2, (int) $tour->data_requirement);
        $this->assertSame(['nationality', 'birthdate'], $tour->personal_info_required);
        $this->assertSame(['hotel_name'], $tour->operational_info_required);
    }

    public function test_a_tour_created_without_them_is_not_given_any(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/admin/tours', $this->payload())->assertSuccessful();

        $tour = Tour::query()->latest('id')->first();
        $this->assertEmpty($tour->personal_info_required ?? []);
    }
}
