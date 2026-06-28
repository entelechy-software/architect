<?php

declare(strict_types=1);

namespace Entelechy\Architect\Notifications\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A notification rule that maps an application trigger event to a
 * notification delivery action.
 *
 * Rules are evaluated by NotificationRuleEngine::fire($trigger, $context).
 *
 * Table: architect_notification_rules
 *
 * @property int $id
 * @property string $trigger
 * @property string $notification_type
 * @property string $severity
 * @property string|null $recipient_resolver
 * @property string|null $message_template
 * @property bool $enabled
 */
class NotificationRule extends Model
{
    /** @var string */
    protected $table = 'architect_notification_rules';

    /** @var list<string> */
    protected $fillable = [
        'trigger',
        'notification_type',
        'severity',
        'recipient_resolver',
        'message_template',
        'enabled',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function getConnectionName(): string
    {
        return config('architect.notifications.connection', config('database.default', 'mysql'));
    }

    /**
     * Scope: rules for a specific trigger that are enabled.
     *
     * @param  Builder<NotificationRule>  $query
     * @return Builder<NotificationRule>
     */
    public function scopeForTrigger(Builder $query, string $trigger): Builder
    {
        return $query->where('trigger', $trigger)->where('enabled', true);
    }
}
