<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            $table->text('company_name')->nullable();
            $table->text('index_title')->nullable();
            $table->text('index_keywords')->nullable();
            $table->text('index_description')->nullable();
            $table->unsignedBigInteger('index_logo_id')->nullable();
            $table->unsignedBigInteger('index_favicon_id')->nullable();
            $table->text('google_analytics_code')->nullable();
            $table->text('zoopim_code')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configurations');
    }
};
