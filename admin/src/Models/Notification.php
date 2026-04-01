<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Notification extends Model
{
    protected $table = 'notifications';

    // Notification priorities
    const PRIORITIES = [
        'low',
        'medium',
        'high',
        'critical'
    ];

    // Notification categories
    const CATEGORIES = [
        'General',
        'Official',
        'Announcement',
        'Update',
        'Alert'
    ];

    protected $fillable = [
        'title',
        'description',
        'notification_no',
        'icon',
        'show_arrow',
        'priority',
        'publish_date',
        'expiry_date',
        'category',
        'file_url',
        'file_name',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'publish_date' => 'date',
        'expiry_date' => 'date',
        'show_arrow' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopePublished($query)
    {
        return $query->where('publish_date', '<=', Carbon::now());
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expiry_date')
              ->orWhere('expiry_date', '>=', Carbon::now());
        });
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('title', 'like', "%{$search}%")
                     ->orWhere('description', 'like', "%{$search}%")
                     ->orWhere('notification_no', 'like', "%{$search}%");
    }

    /**
     * Helper Methods
     */
    public function isPublished(): bool
    {
        return $this->publish_date && now()->isSameOrAfter($this->publish_date);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && now()->isAfter($this->expiry_date);
    }

    public function isActiveAndPublished(): bool
    {
        return $this->is_active && $this->isPublished() && !$this->isExpired();
    }

    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }
        return now()->diffInDays($this->expiry_date, false);
    }

    public function getPriorityColor(): string
    {
        return match($this->priority) {
            'critical' => '#dc2626',
            'high' => '#ea580c',
            'medium' => '#ca8a04',
            'low' => '#16a34a',
            default => '#6b7280'
        };
    }

    /**
     * Convert to public API array
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'notificationNo' => $this->notification_no,
            'icon' => $this->icon,
            'showArrow' => $this->show_arrow,
            'priority' => $this->priority,
            'publishDate' => $this->publish_date ? $this->publish_date->format('Y-m-d') : null,
            'expiryDate' => $this->expiry_date ? $this->expiry_date->format('Y-m-d') : null,
            'category' => $this->category,
            'fileUrl' => $this->file_url,
            'fileName' => $this->file_name,
            'isActive' => $this->is_active,
        ];
    }
}