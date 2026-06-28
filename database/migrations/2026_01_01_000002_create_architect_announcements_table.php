<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the architect_announcements table for persistent site-wide
 * announcement banners. Announcements are shown to all users until
 * they expire or are individually dismissed (session-backed).
 */
return new class extends Migration
{
    public function up(): void
    {
        $connection = config('architect.notifications.connection', config('database.default'));

        Schema::connection($connection)->create('architect_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('severity')->default('info')->comment('info | success | warning | danger');
            $table->text('message');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['enabled', 'expires_at']);
        });
    }

    public function down(): void
    {
        $connection = config('architect.notifications.connection', config('database.default'));

        Schema::connection($connection)->dropIfExists('architect_announcements');
    }
};
