<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tender extends Model
{
    protected $table = 'tenders';
    
    protected $fillable = [
        'title',
        'description',
        'tender_number',
        'reference_number',
        'publish_date',
        'closing_date',
        'extended_date',
        'estimated_value',
        'category',
        'status',
        'department',
        'contact_person',
        'contact_email',
        'contact_phone',
        'created_by',
        'updated_by',
    ];
    
    protected $casts = [
        'publish_date' => 'date',
        'closing_date' => 'date',
        'extended_date' => 'date',
        'estimated_value' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_EXTENDED = 'extended';
    const STATUS_CLOSED = 'closed';
    const STATUS_CANCELLED = 'cancelled';
    
    // Common categories
    const CATEGORIES = [
        'General Supplies',
        'Housekeeping Services',
        'GeM Procurement',
        'Printing Services',
        'Adventure Equipment/Vehicles',
        'Sports Goods/Uniforms',
        'Sports Goods/Equipments',
        'Construction/Civil Works',
        'IT Equipment',
        'Consultancy Services',
        'Other',
    ];
    
    /**
     * Get documents for this tender
     */
    public function documents(): HasMany
    {
        return $this->hasMany(TenderDocument::class)->orderBy('sort_order');
    }
    
    /**
     * Get the user who created this tender
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    /**
     * Get the user who last updated this tender
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    
    /**
     * Check if tender is active (open for bidding)
     */
    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_EXTENDED]);
    }
    
    /**
     * Check if tender closing date has passed
     */
    public function isExpired(): bool
    {
        $closingDate = $this->extended_date ?? $this->closing_date;
        return $closingDate->isPast();
    }
    
    /**
     * Get effective closing date (extended date if available)
     */
    public function getEffectiveClosingDate()
    {
        return $this->extended_date ?? $this->closing_date;
    }
    
    /**
     * Format estimated value for display
     */
    public function getFormattedValue(): string
    {
        if (!$this->estimated_value) {
            return 'Not specified';
        }
        
        // Convert paise to rupees
        $rupees = $this->estimated_value / 100;
        
        // Format in Indian number system
        if ($rupees >= 10000000) {
            return '₹' . number_format($rupees / 10000000, 2) . ' Cr';
        } elseif ($rupees >= 100000) {
            return '₹' . number_format($rupees / 100000, 2) . ' Lakh';
        } else {
            return '₹' . number_format($rupees, 0);
        }
    }
    
    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_EXTENDED => 'warning',
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_CLOSED => 'info',
            self::STATUS_CANCELLED => 'danger',
            default => 'secondary',
        };
    }
    
    /**
     * Scope for active tenders
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_EXTENDED]);
    }
    
    /**
     * Scope for published tenders (non-draft)
     */
    public function scopePublished($query)
    {
        return $query->where('status', '!=', self::STATUS_DRAFT);
    }
    
    /**
     * Convert to array for JSON API (public)
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'tenderNumber' => $this->tender_number,
            'publishDate' => $this->publish_date->format('Y-m-d'),
            'closingDate' => $this->getEffectiveClosingDate()->format('Y-m-d'),
            'estimatedValue' => $this->getFormattedValue(),
            'category' => $this->category,
            'status' => $this->status === self::STATUS_EXTENDED ? 'active' : $this->status,
            'department' => $this->department,
            'documents' => $this->documents->map(fn($doc) => [
                'name' => $doc->name,
                'url' => $doc->file_path,
                'type' => $doc->file_type,
            ])->toArray(),
            'contactPerson' => $this->contact_person,
            'contactEmail' => $this->contact_email,
            'contactPhone' => $this->contact_phone,
        ];
    }
}
