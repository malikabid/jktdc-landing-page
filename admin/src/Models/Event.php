<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Event extends Model
{
    protected $table = 'events';

    protected $fillable = [
        'title',
        'description',
        'startDate',
        'endDate',
        'location',
        'category',
        'videoUrl',
        'thumbnail',
        'showOnHomepage',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'startDate' => 'date',
        'endDate' => 'date',
        'showOnHomepage' => 'boolean',
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
    public function scopeHomepage($query)
    {
        return $query->where('showOnHomepage', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('title', 'like', "%{$search}%")
                     ->orWhere('description', 'like', "%{$search}%")
                     ->orWhere('location', 'like', "%{$search}%");
    }

    /**
     * Helper Methods
     */
    public function isUpcoming(): bool
    {
        return now()->isBefore($this->startDate);
    }

    public function isPast(): bool
    {
        $endDate = $this->endDate ?? $this->startDate;
        return now()->isAfter($endDate);
    }

    public function isOngoing(): bool
    {
        return now()->isBetween($this->startDate, $this->endDate ?? $this->startDate);
    }

    public function getDaysUntilEvent(): int
    {
        if ($this->isOngoing() || $this->isPast()) {
            return 0;
        }
        return now()->diffInDays($this->startDate, false);
    }

    public function getFormattedDate(): string
    {
        if ($this->endDate && $this->endDate->notEqualTo($this->startDate)) {
            return $this->startDate->format('M d') . ' - ' . $this->endDate->format('M d, Y');
        }
        return $this->startDate->format('M d, Y');
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
            'startDate' => $this->startDate ? $this->startDate->format('Y-m-d') : null,
            'endDate' => $this->endDate ? $this->endDate->format('Y-m-d') : null,
            'location' => $this->location,
            'category' => $this->category,
            'videoUrl' => $this->videoUrl,
            'thumbnail' => $this->thumbnail,
            'showOnHomepage' => $this->showOnHomepage,
        ];
    }
}