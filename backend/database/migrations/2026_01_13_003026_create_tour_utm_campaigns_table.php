<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_utm_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_translation_id')->constrained('tour_translations')->onDelete('cascade');
            $table->string('utm_source', 50)->nullable()->comment('google, facebook, newsletter');
            $table->string('utm_medium', 50)->nullable()->comment('cpc, email, social');
            $table->string('utm_campaign', 100)->nullable()->comment('summer_2026, black_friday');
            $table->string('utm_term', 100)->nullable()->comment('keyword');
            $table->string('utm_content', 100)->nullable()->comment('ad_variant');
            $table->string('campaign_label', 100)->nullable()->comment('Internal campaign name');
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->index(['tour_translation_id', 'active']);
            $table->index('utm_campaign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_utm_campaigns');
    }
};
