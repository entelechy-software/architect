<?php

declare(strict_types=1);

namespace Entelechy\Architect\Notifications\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Persistent site-wide announcement banner.
 *
 * Announcements are created by administrators and shown to all users until
 * they expire or are dismissed (per-session). Active announcements are
 * those with enabled=true and expires_at in the future (or null).
 *
 * Table: architect_announcements
 */
class ArchitectAnnouncement extends Model
{
    /** @var string */
    protected $table = 'architect_announcements';

    /** @var list<string> */
    protected $fillable = [
        'severity',
        'message',
        'expires_at',
        'enabled',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'expires_at' => 'datetime',
        'enabled' => 'boolean',
    ];

    public function getConnectionName(): string
    {
        return config('architect.notifications.connection', config('database.default', 'mysql'));
    }

    /** Scope: announcements that are currently active and not expired.
     *
     * @param  Builder<ArchitectAnnouncement>  $query
     * @return Builder<ArchitectAnnouncement>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('enabled', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
