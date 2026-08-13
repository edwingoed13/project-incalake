<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Emails captured by the footer's "Únete a Incalake" form. The form existed
 * for a long time with `@submit.prevent` and no handler at all — it looked
 * like a subscription and silently discarded every address.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('newsletter_subscribers')) {
            return;
        }

        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('language', 5)->nullable();
            $table->string('source', 40)->default('footer');
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};
