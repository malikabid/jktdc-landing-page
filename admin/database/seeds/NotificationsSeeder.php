<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * Imports the legacy pub/data/notifications.json into the notifications table.
 *
 * Idempotent, in three layers:
 *  1. Completion marker: once an import has finished, a row in legacy_imports
 *     stops the seeder for good. Records an admin later edits or deletes are never
 *     re-created from the JSON.
 *  2. Per-record guard: before the marker exists, a notification is matched on
 *     (title, publish_date) and skipped if present. Each record and its document
 *     are written in one transaction.
 *  3. Missing JSON: the seeder exits quietly if the table already has rows. A
 *     missing file with an empty table throws, so a fresh database fails loudly.
 *
 * Deploys run `phinx seed:run` on every push, so this must be safe to re-run.
 */
class NotificationsSeeder extends AbstractSeed
{
    public function run(): void
    {
        if ($this->isMarkedImported()) {
            echo "legacy notifications import already completed; skipped\n";
            return;
        }

        $jsonPath = $this->locateJsonFile();
        if ($jsonPath === null) {
            $existingCount = $this->countNotifications();
            if ($existingCount > 0) {
                echo "legacy JSON not found; nothing to import ({$existingCount} notifications in DB)\n";
                return;
            }
            throw new RuntimeException(
                'notifications.json not readable at any expected path and the notifications table is empty'
            );
        }

        $jsonContent = file_get_contents($jsonPath);
        $notifications = json_decode($jsonContent, true);

        if (!is_array($notifications)) {
            throw new RuntimeException("notifications.json is not a valid JSON array: {$jsonPath}");
        }

        echo "Found " . count($notifications) . " notifications to import from {$jsonPath}\n";

        $imported = 0;
        $skipped = 0;

        foreach ($notifications as $notification) {
            $title = trim($notification['title'] ?? '');
            $publishDate = $notification['publishDate'] ?? null;

            if ($title === '' || !$publishDate) {
                echo "Skipped a record with no title or publish date\n";
                $skipped++;
                continue;
            }

            $existing = $this->fetchRow(sprintf(
                "SELECT id FROM notifications WHERE title = %s AND publish_date = %s LIMIT 1",
                $this->quote($title),
                $this->quote($publishDate)
            ));

            $fileUrl = $notification['fileUrl'] ?? null;
            if (!empty($fileUrl) && !$this->isAllowedFileUrl($fileUrl)) {
                echo "Skipped (fileUrl not allowed): {$title}\n";
                $skipped++;
                continue;
            }

            if ($existing) {
                echo "Skipped (already exists): {$title}\n";
                $skipped++;
                continue;
            }

            $this->getAdapter()->beginTransaction();
            try {
                $this->importOne($notification, $title, $publishDate);
                $this->getAdapter()->commitTransaction();
            } catch (Throwable $e) {
                $this->getAdapter()->rollbackTransaction();
                throw new RuntimeException("Failed to import \"{$title}\": " . $e->getMessage(), 0, $e);
            }

            echo "Imported: {$title}\n";
            $imported++;
        }

        $this->markImported();

        echo "Import completed! {$imported} imported, {$skipped} skipped.\n";
    }

    private function isMarkedImported(): bool
    {
        return $this->fetchRow("SELECT name FROM legacy_imports WHERE name = 'notifications' LIMIT 1") !== false;
    }

    private function markImported(): void
    {
        $this->execute("INSERT IGNORE INTO legacy_imports (name) VALUES ('notifications')");
    }

    /**
     * Insert one notification and its legacy document (if any).
     */
    private function importOne(array $notification, string $title, string $publishDate): void
    {
        // isActive maps onto the status enum
        $status = ($notification['isActive'] ?? true)
            ? 'published'
            : 'archived';

        $this->insert('notifications', [
            'title' => $title,
            'notification_number' => $notification['notificationNo'] ?? null,
            'description' => $notification['description'] ?? '',
            'icon' => $notification['icon'] ?? '📄',
            'show_arrow' => ($notification['showArrow'] ?? true) ? 1 : 0,
            'priority' => $notification['priority'] ?? 'medium',
            'publish_date' => $publishDate,
            'expiry_date' => $notification['expiryDate'] ?? null,
            'category' => $notification['category'] ?? 'Official',
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $notificationId = $this->getAdapter()->getConnection()->lastInsertId();

        // The legacy format carries at most one file per notification
        if (!empty($notification['fileUrl'])) {
            $this->insert('notification_documents', [
                'notification_id' => $notificationId,
                'name' => $notification['fileName'] ?? basename($notification['fileUrl']),
                'file_path' => $notification['fileUrl'],
                'file_type' => pathinfo($notification['fileUrl'], PATHINFO_EXTENSION) ?: 'pdf',
                'sort_order' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Find pub/data/notifications.json. Returns null if it is not present at any expected path.
     */
    private function locateJsonFile(): ?string
    {
        $paths = [
            // Docker: pub/data is bind-mounted under the web root
            '/var/www/html/pub/data/notifications.json',
            // Production and staging: pub/ is a sibling of admin/
            dirname(__DIR__, 3) . '/pub/data/notifications.json',
        ];

        foreach ($paths as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Legacy documents live under pub/notifications/ or pub/pdf/, one file per directory level.
     */
    private function isAllowedFileUrl(string $url): bool
    {
        return (bool) preg_match('#^/pub/(notifications|pdf)/[^/]+$#', $url)
            && strpos($url, '..') === false
            && strpos($url, '\\') === false;
    }

    private function countNotifications(): int
    {
        return (int) $this->fetchRow('SELECT COUNT(*) AS c FROM notifications')['c'];
    }

    /**
     * Quote a value for safe inline use in the lookup query
     */
    private function quote(string $value): string
    {
        return $this->getAdapter()->getConnection()->quote($value);
    }
}
