<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leads captured when a tour requires availability verification
 * (tours.require_availability = true): instead of an instant booking, the
 * customer sends an availability request from the tour page. Stored here so the
 * lead survives even if the notification email fails.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availability_inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->nullable()->constrained('tours')->nullOnDelete();
            $table->string('tour_title')->nullable();   // snapshot for context
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->date('preferred_date')->nullable();
            $table->unsignedSmallInteger('adults')->default(1);
            $table->unsignedSmallInteger('children')->default(0);
            $table->text('message')->nullable();
            $table->string('language', 5)->nullable();
            $table->string('status', 20)->default('new');   // new | contacted | closed
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_inquiries');
    }
};
