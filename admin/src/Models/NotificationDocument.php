<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDocument extends Model
{
    protected $table = 'notification_documents';

    protected $fillable = [
        'notification_id',
        'name',
        'file_path',
        'file_type',
        'file_size',
        'sort_order',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the notification this document belongs to
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSize(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get the full URL for the document
     */
    public function getUrl(): string
    {
        // If it's already a full path starting with /pub, return as-is
        if (str_starts_with($this->file_path, '/pub/')) {
            return $this->file_path;
        }

        // Otherwise prepend the notifications directory
        return '/pub/notifications/' . $this->file_path;
    }
}
