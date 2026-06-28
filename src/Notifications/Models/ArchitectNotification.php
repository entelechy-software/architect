<?php

declare(strict_types=1);

namespace Entelechy\Architect\Notifications\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Persisted inbox notification record.
 *
 * Named ArchitectNotification to avoid clashing with
 * Illuminate\Notifications\DatabaseNotification.
 *
 * Database connection respects `config('architect.notifications.connection')`,
 * falling back to the application default.
 *
 * Table: architect_notifications
 *
 * @property int $id
 * @property int $recipient_id
 * @property string $type
 * @property array<string, mixed>|null $data
 * @property string|null $action_url
 * @property Carbon|null $read_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ArchitectNotification extends Model
{
    /** @var string */
    protected $table = 'architect_notifications';

    /** @var list<string> */
    protected $fillable = [
        'recipient_id',
        'type',
        'data',
        'action_url',
        'read_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function getConnectionName(): string
    {
        return config('architect.notifications.connection', config('database.default', 'mysql'));
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markRead(): void
    {
        $this->update(['read_at' => now()]);
    }
}
