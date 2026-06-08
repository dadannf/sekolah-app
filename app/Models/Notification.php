<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'performed_by_id',
        'performed_by_name',
        'type',
        'action',
        'title',
        'message',
        'data',
        'changes',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'changes' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['is_read'];

    /**
     * Create a notification only if a matching one does not already exist.
     *
     * This is used to prevent duplicate notifications when an event is fired
     * more than once (e.g. due to double-submit, observer/event duplication, etc.).
     */
    public static function createIfMissing(array $dedupeWhere, array $attributes, ?int $withinSeconds = null): ?self
    {
        $query = static::query();

        foreach ($dedupeWhere as $key => $value) {
            $query->where($key, $value);
        }

        if ($withinSeconds !== null) {
            $query->where('created_at', '>=', now()->subSeconds($withinSeconds));
        }

        $existing = $query->latest('id')->first();
        if ($existing) {
            return $existing;
        }

        return static::create($attributes);
    }

    /**
     * Get the user that owns this notification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Get the is_read attribute
     */
    public function getIsReadAttribute(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Scope: Get unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope: Get notifications by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Get notifications by action
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: Get recent notifications
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
