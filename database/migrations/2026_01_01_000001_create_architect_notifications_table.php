<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the architect_notifications table for the Architect inbox notification
 * system. Each row represents a single notification delivered to a specific
 * recipient.
 *
 * Host apps may override the database connection via:
 *   config('architect.notifications.connection')
 */
return new class extends Migration
{
    public function up(): void
    {
        $connection = config('architect.notifications.connection', config('database.default'));

        Schema::connection($connection)->create('architect_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recipient_id')->comment('FK to the host app user/admin table.');
            $table->string('type')->comment('Application-defined notification type slug.');
            $table->json('data')->nullable()->comment('Structured payload for the notification.');
            $table->string('action_url')->nullable()->comment('Optional deep-link URL.');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_id', 'read_at']);
        });
    }

    public function down(): void
    {
        $connection = config('architect.notifications.connection', config('database.default'));

        Schema::connection($connection)->dropIfExists('architect_notifications');
    }
};
