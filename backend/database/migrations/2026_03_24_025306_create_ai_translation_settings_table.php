<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_translation_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('openai'); // openai, gemini, deepseek, anthropic
            $table->string('api_key')->nullable();
            $table->string('model')->nullable(); // gpt-4, gemini-pro, deepseek-chat, claude-3
            $table->text('custom_prompt')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable(); // Additional provider-specific settings
            $table->timestamps();
        });

        // Insert default configuration
        DB::table('ai_translation_settings')->insert([
            'provider' => 'openai',
            'model' => 'gpt-4',
            'custom_prompt' => 'You are a professional tour translator. Translate the following tour content to {target_language}, maintaining the HTML structure and keeping tour-specific terms accurate.',
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_translation_settings');
    }
};
