<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained('tours')->onDelete('cascade');
            $table->foreignId('language_id')->constrained('languages')->onDelete('cascade');
            
            // SEO BÁSICO
            $table->string('slug', 150)->comment('URL-friendly slug per language');
            $table->string('h1_title', 100)->comment('Main H1 title for SEO');
            $table->string('meta_title', 60)->comment('Meta title (max 60 chars)');
            $table->string('meta_description', 160)->comment('Meta description (max 160 chars)');
            $table->string('canonical_url')->nullable();
            
            // SEO AVANZADO - Open Graph
            $table->string('og_title', 60)->nullable();
            $table->string('og_description', 160)->nullable();
            $table->string('og_image_path')->nullable();
            
            // SEO AVANZADO - Twitter Cards
            $table->string('twitter_title', 60)->nullable();
            $table->string('twitter_description', 160)->nullable();
            $table->string('twitter_image_path')->nullable();
            
            // CONTENIDO COMERCIAL
            $table->text('short_description')->nullable()->comment('Commercial short description');
            $table->longText('long_description')->nullable()->comment('SEO-optimized long description');
            
            // CONTENIDO ESTRUCTURADO (JSON)
            $table->json('what_includes')->nullable()->comment('What the tour includes');
            $table->json('what_not_includes')->nullable()->comment('What is NOT included');
            $table->json('itinerary')->nullable()->comment('Day-by-day itinerary');
            $table->json('recommendations')->nullable()->comment('Travel recommendations');
            $table->json('what_to_bring')->nullable()->comment('What to bring list');
            $table->json('policies')->nullable()->comment('Tour policies');
            $table->text('cancellation_policy')->nullable();
            
            // CTA & CONVERSIÓN
            $table->string('cta_text', 50)->default('Book Now')->comment('Call-to-action button text');
            $table->string('price_from_label', 20)->default('From')->comment('Price label');
            
            // SEM / GOOGLE ADS
            $table->string('ads_headline', 30)->nullable()->comment('Google Ads headline');
            $table->string('ads_description', 90)->nullable()->comment('Google Ads description');
            
            $table->timestamps();
            
            // Indexes
            $table->unique(['tour_id', 'language_id'], 'tour_lang_unique');
            $table->unique('slug', 'slug_unique');
            $table->index('h1_title');
            $table->index(['tour_id', 'language_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_translations');
    }
};
