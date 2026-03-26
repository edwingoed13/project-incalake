<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique()->comment('Unique tour identifier (tour-uros-taquile)');
            $table->foreignId('city_id')->constrained('cities')->onDelete('cascade');
            
            // Service characteristics
            $table->enum('service_type', ['tour', 'package', 'experience', 'transport'])->default('tour');
            $table->enum('status', ['draft', 'published', 'hidden', 'archived'])->default('draft');
            $table->enum('difficulty', ['easy', 'moderate', 'hard'])->default('moderate');
            $table->enum('target_audience', ['families', 'couples', 'solo', 'groups', 'all'])->default('all');
            
            // Capacity and duration
            $table->integer('capacity')->default(999)->comment('Max people capacity');
            $table->integer('cupos')->nullable()->comment('Available slots');
            $table->integer('duration_days')->default(1);
            $table->integer('duration_hours')->nullable();
            $table->time('start_time')->nullable();
            
            // Booking settings
            $table->integer('booking_anticipation_hours')->default(24)->comment('Hours before tour to book');
            $table->tinyInteger('data_requirement')->default(1)->comment('1=before, 2=after, 3=not required');
            
            // SEO settings (global, not translated)
            $table->enum('index_status', ['index', 'noindex'])->default('index');
            $table->enum('follow_status', ['follow', 'nofollow'])->default('follow');
            
            // Media (global)
            $table->string('featured_image_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('attachments_path')->nullable()->comment('PDF attachments');
            
            // Status
            $table->boolean('active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['status', 'active']);
            $table->index(['city_id', 'service_type']);
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
