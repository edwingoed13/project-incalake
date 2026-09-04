<?php

namespace Tests\Feature;

use App\Http\Requests\UpdateTourRequest;
use App\Models\City;
use App\Models\Language;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The gallery cap is a rule now, not a caption.
 *
 * The wizard's badge read "10 / 20 imágenes" and nothing anywhere enforced it:
 * not the browser, not the request, not the database. ES235 is at 25 today. The
 * number the business wants is 50, and it has to hold across two fields —
 * media_gallery carries what is already saved, temp_images what was just
 * uploaded, and it is their sum a traveller's browser has to download.
 */
class TourGalleryLimitTest extends TestCase
{
    use RefreshDatabase;

    private function actAsAdmin(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
    }

    private function imagenesGuardadas(int $n): array
    {
        return array_map(fn ($i) => ['id' => $i + 1, 'order' => $i], range(0, $n - 1));
    }

    private function imagenesNuevas(int $n): array
    {
        return array_map(fn ($i) => [
            'filename' => "foto-{$i}.webp",
            'path' => "tours/foto-{$i}.webp",
        ], range(0, max(0, $n - 1)));
    }

    private function guardar(Tour $tour, array $extra)
    {
        return $this->postJson("/api/admin/tours/{$tour->id}", array_merge([
            'code' => $tour->code,
            'city_name' => 'Puno',
        ], $extra));
    }

    private function tour(): Tour
    {
        $lang = Language::query()->firstOrCreate(['code' => 'ES'], ['name' => 'Español', 'country' => 'PE', 'active' => true]);
        $city = City::query()->firstOrCreate(['name' => 'Puno'], ['slug' => 'puno']);

        return Tour::factory()->create([
            'code' => 'ES' . random_int(100, 999),
            'city_id' => $city->id,
            'primary_language_id' => $lang->id,
        ]);
    }

    public function test_fifty_images_are_accepted(): void
    {
        $this->actAsAdmin();

        $this->guardar($this->tour(), ['media_gallery' => $this->imagenesGuardadas(50)])
            ->assertSuccessful();
    }

    public function test_the_fifty_first_is_refused(): void
    {
        $this->actAsAdmin();

        $this->guardar($this->tour(), ['media_gallery' => $this->imagenesGuardadas(51)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('media_gallery');
    }

    public function test_saved_and_newly_uploaded_are_counted_together(): void
    {
        // 40 already in the tour plus 15 just dropped in is 55, and neither
        // field is over the cap on its own — this is the case a rule on one
        // field would have waved through.
        $this->actAsAdmin();

        $this->guardar($this->tour(), [
            'media_gallery' => $this->imagenesGuardadas(40),
            'temp_images' => $this->imagenesNuevas(15),
        ])->assertStatus(422)->assertJsonValidationErrors('media_gallery');
    }

    public function test_a_tour_that_predates_the_cap_still_saves(): void
    {
        // ES235 carries 25 images, over the 20 the badge used to claim. The cap
        // must not lock the catalogue's own tours out of being edited.
        $this->actAsAdmin();

        $this->guardar($this->tour(), ['media_gallery' => $this->imagenesGuardadas(25)])
            ->assertSuccessful();
    }

    public function test_the_ceiling_is_the_one_the_wizard_shows(): void
    {
        $this->assertSame(50, UpdateTourRequest::MAX_GALLERY_IMAGES);
    }
}
