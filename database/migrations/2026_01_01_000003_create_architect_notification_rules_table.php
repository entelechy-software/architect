<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the architect_notification_rules table used by the NotificationRuleEngine.
 *
 * Each rule maps an application trigger (e.g. 'member.created') to a
 * notification type and configures how the recipient is resolved and
 * what message is sent.
 */
return new class extends Migration
{
    public function up(): void
    {
        $connection = config('architect.notifications.connection', config('database.default'));

        Schema::connection($connection)->create('architect_notification_rules', function (Blueprint $table) {
            $table->id();
            $table->string('trigger')->comment('Application trigger key (e.g. member.approved).');
            $table->string('notification_type')->comment('toast | alert | inbox | announcement');
            $table->string('severity')->default('info')->comment('info | success | warning | danger');
            $table->string('recipient_resolver')->nullable()->comment('FQCN or strategy key for recipient resolution.');
            $table->text('message_template')->nullable()->comment('Mustache-style message template.');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['trigger', 'enabled']);
        });
    }

    public function down(): void
    {
        $connection = config('architect.notifications.connection', config('database.default'));

        Schema::connection($connection)->dropIfExists('architect_notification_rules');
    }
};
