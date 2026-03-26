<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tours_index_endpoint_requires_authentication_for_admin_actions(): void
    {
        $response = $this->postJson('/api/admin/tours', [
            'code' => 'TEST001',
            'city_name' => 'Test City',
        ]);

        $response->assertStatus(401);
    }

    public function test_products_index_is_public(): void
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    public function test_products_show_is_public(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_categories_index_is_public(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertStatus(200);
    }

    public function test_languages_index_is_public(): void
    {
        $response = $this->getJson('/api/languages');

        $response->assertStatus(200);
    }

    public function test_tours_index_is_public(): void
    {
        $response = $this->getJson('/api/tours');

        $response->assertStatus(200);
    }

    public function test_api_fallback_returns_404(): void
    {
        $response = $this->getJson('/api/invalid-endpoint');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Ruta no encontrada. Verifique la URL de la API.',
            ]);
    }

    public function test_tours_endpoint_supports_pagination(): void
    {
        $perPage = 10;

        $response = $this->getJson("/api/tours?per_page={$perPage}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => [
                    'per_page',
                    'current_page',
                    'total',
                ],
            ]);

        $metaResponse = $response->json('meta');
        $this->assertEquals($perPage, $metaResponse['per_page']);
    }

    public function test_tours_endpoint_supports_search(): void
    {
        $response = $this->getJson('/api/tours?search=test');

        $response->assertStatus(200);
    }

    public function test_tours_endpoint_supports_filtering_by_status(): void
    {
        $response = $this->getJson('/api/tours?status=published');

        $response->assertStatus(200);
    }

    public function test_tours_endpoint_supports_sorting(): void
    {
        $response = $this->getJson('/api/tours?sort_by=code&sort_order=asc');

        $response->assertStatus(200);
    }
}