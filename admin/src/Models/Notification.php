<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'title',
        'notification_number',
        'description',
        'icon',
        'show_arrow',
        'priority',
        'publish_date',
        'expiry_date',
        'category',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'show_arrow' => 'boolean',
        'publish_date' => 'date',
        'expiry_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';

    // Priority levels (ordered most to least urgent)
    const PRIORITIES = ['critical', 'high', 'medium', 'low'];

    const PRIORITY_CRITICAL = 'critical';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_LOW = 'low';

    // Common categories
    const CATEGORIES = [
        'Official',
        'Recruitment',
        'Tender',
        'Event',
        'Circular',
        'Public Notice',
        'Other',
    ];

    /**
     * Get documents for this notification
     */
    public function documents(): HasMany
    {
        return $this->hasMany(NotificationDocument::class)->orderBy('sort_order');
    }

    /**
     * Get the user who created this notification
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this notification
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if the notification is published and inside its display window
     */
    public function isLive(): bool
    {
        if ($this->status !== self::STATUS_PUBLISHED) {
            return false;
        }

        $today = Carbon::today();

        if ($this->publish_date && $this->publish_date->startOfDay()->gt($today)) {
            return false;
        }

        if ($this->expiry_date && $this->expiry_date->startOfDay()->lt($today)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the notification's expiry date has passed
     */
    public function isExpired(): bool
    {
        return $this->expiry_date !== null
            && $this->expiry_date->startOfDay()->lt(Carbon::today());
    }

    /**
     * Get the primary (first) attached document, if any
     */
    public function primaryDocument(): ?NotificationDocument
    {
        return $this->documents->first();
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_PUBLISHED => 'success',
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_ARCHIVED => 'info',
            default => 'secondary',
        };
    }

    /**
     * Get priority badge class for UI
     */
    public function getPriorityBadgeClass(): string
    {
        return match($this->priority) {
            self::PRIORITY_CRITICAL => 'danger',
            self::PRIORITY_HIGH => 'warning',
            self::PRIORITY_MEDIUM => 'info',
            self::PRIORITY_LOW => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Scope for published notifications (non-draft, non-archived)
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Scope for notifications currently inside their display window
     */
    public function scopeCurrent($query)
    {
        $today = Carbon::today()->toDateString();

        return $query->where('publish_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>=', $today);
            });
    }

    /**
     * Order by priority (critical first), then newest publish date
     */
    public function scopeByPriority($query)
    {
        return $query
            ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
            ->orderBy('publish_date', 'desc');
    }

    /**
     * Convert to array for JSON API (public)
     *
     * Shape matches the legacy pub/data/notifications.json so the frontend
     * renderer and its JSON fallback stay interchangeable. `fileUrl`/`fileName`
     * expose the first document; `documents` carries the full list.
     */
    public function toPublicArray(): array
    {
        $primary = $this->primaryDocument();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'notificationNo' => $this->notification_number,
            'description' => $this->description,
            'icon' => $this->icon ?: '🔔',
            'showArrow' => (bool)$this->show_arrow,
            'priority' => $this->priority,
            'publishDate' => $this->publish_date->format('Y-m-d'),
            'expiryDate' => $this->expiry_date?->format('Y-m-d'),
            'category' => $this->category,
            'fileUrl' => $primary?->getUrl(),
            'fileName' => $primary?->name,
            'documents' => $this->documents->map(fn($doc) => [
                'name' => $doc->name,
                'url' => $doc->getUrl(),
                'type' => $doc->file_type,
            ])->toArray(),
            'isActive' => true,
        ];
    }
}
