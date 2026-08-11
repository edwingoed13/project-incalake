<?php

namespace Tests\Feature;

use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The command deletes files with no undo, so the tests that matter here are
 * the ones asserting it does NOT delete: referenced files, recent files, and
 * everything when the reference query looks broken.
 */
class PruneOrphanTourImagesTest extends TestCase
{
    use RefreshDatabase;

    /** tour_media_gallery requires language_id and alt_text (both NOT NULL). */
    private function gallery(Tour $tour, string $imagePath, ?string $originalPath = null): void
    {
        DB::table('tour_media_gallery')->insert([
            'tour_id' => $tour->id,
            'language_id' => $tour->primary_language_id,
            'image_path' => $imagePath,
            'original_path' => $originalPath,
            'alt_text' => 'foto de prueba',
        ]);
    }

    private function agedFile(string $path, string $body = 'x'): void
    {
        Storage::disk('public')->put($path, $body);
        // Push mtime well past the default --days=7 window.
        touch(Storage::disk('public')->path($path), now()->subDays(30)->getTimestamp());
    }

    public function test_deletes_an_abandoned_temp_upload(): void
    {
        Storage::fake('public');
        $tour = Tour::factory()->create();
        $this->gallery($tour, "tours/{$tour->id}/keep.jpg");
        $this->agedFile("tours/{$tour->id}/keep.jpg");
        $this->agedFile('tours/temp/abandonada.jpg');

        $this->artisan('tours:prune-images', ['--force' => true])->assertExitCode(0);

        Storage::disk('public')->assertMissing('tours/temp/abandonada.jpg');
        Storage::disk('public')->assertExists("tours/{$tour->id}/keep.jpg");
    }

    public function test_never_deletes_a_referenced_file(): void
    {
        Storage::fake('public');
        $tour = Tour::factory()->create();

        // One file per referencing column — miss any of these in the command
        // and a live image gets deleted.
        DB::table('tours')->where('id', $tour->id)->update([
            'featured_image_path' => "tours/{$tour->id}/featured.jpg",
            'thumbnail_path' => "tours/{$tour->id}/thumb.jpg",
        ]);
        $this->gallery($tour, "tours/{$tour->id}/display.jpg", "tours/{$tour->id}/original.jpg");

        foreach (['featured', 'thumb', 'display', 'original'] as $name) {
            $this->agedFile("tours/{$tour->id}/{$name}.jpg");
        }

        $this->artisan('tours:prune-images', ['--force' => true])->assertExitCode(0);

        foreach (['featured', 'thumb', 'display', 'original'] as $name) {
            Storage::disk('public')->assertExists("tours/{$tour->id}/{$name}.jpg");
        }
    }

    public function test_leaves_recent_files_alone(): void
    {
        Storage::fake('public');
        $tour = Tour::factory()->create();
        $this->gallery($tour, "tours/{$tour->id}/keep.jpg");
        $this->agedFile("tours/{$tour->id}/keep.jpg");

        // Just uploaded — could be a wizard session in progress right now.
        Storage::disk('public')->put('tours/temp/subiendo-ahora.jpg', 'x');

        $this->artisan('tours:prune-images', ['--force' => true])->assertExitCode(0);

        Storage::disk('public')->assertExists('tours/temp/subiendo-ahora.jpg');
    }

    public function test_dry_run_is_the_default(): void
    {
        Storage::fake('public');
        $tour = Tour::factory()->create();
        $this->gallery($tour, "tours/{$tour->id}/keep.jpg");
        $this->agedFile("tours/{$tour->id}/keep.jpg");
        $this->agedFile('tours/temp/huerfana.jpg');

        $this->artisan('tours:prune-images')->assertExitCode(0);

        Storage::disk('public')->assertExists('tours/temp/huerfana.jpg');
    }

    public function test_aborts_when_no_paths_are_referenced_at_all(): void
    {
        Storage::fake('public');
        // No gallery rows anywhere: indistinguishable from a broken query, and
        // treating every file as an orphan would wipe the whole library.
        $this->agedFile('tours/1/parece-huerfana.jpg');

        $this->artisan('tours:prune-images', ['--force' => true])->assertExitCode(1);

        Storage::disk('public')->assertExists('tours/1/parece-huerfana.jpg');
    }

    public function test_soft_deleted_tours_keep_their_photos(): void
    {
        Storage::fake('public');
        $tour = Tour::factory()->create();
        $this->gallery($tour, "tours/{$tour->id}/foto.jpg");
        $this->agedFile("tours/{$tour->id}/foto.jpg");

        $tour->delete(); // soft delete — restorable, so the photo must survive

        $this->artisan('tours:prune-images', ['--force' => true])->assertExitCode(0);

        Storage::disk('public')->assertExists("tours/{$tour->id}/foto.jpg");
    }
}
