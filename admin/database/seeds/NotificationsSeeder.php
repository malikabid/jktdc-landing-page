<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * Seeds notifications from the legacy pub/data/notifications.json.
 *
 * Idempotent: a notification is matched on (title, publish_date) and skipped if
 * it already exists. Deploys run `phinx seed:run` on every push, so this must be
 * safe to re-run.
 */
class NotificationsSeeder extends AbstractSeed
{
    public function run(): void
    {
        // Read existing notifications from JSON file
        // Try multiple paths
        $paths = [
            '/tmp/notifications.json',
            // Docker: pub/data is bind-mounted under the web root
            '/var/www/html/pub/data/notifications.json',
            // Production: pub/ is a sibling of admin/
            dirname(__DIR__, 3) . '/pub/data/notifications.json',
        ];

        $jsonPath = null;
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $jsonPath = $path;
                break;
            }
        }

        if (!$jsonPath) {
            echo "notifications.json not found in any of the expected paths\n";
            return;
        }

        $jsonContent = file_get_contents($jsonPath);
        $notifications = json_decode($jsonContent, true);

        if (!$notifications) {
            echo "No notifications found in JSON file\n";
            return;
        }

        echo "Found " . count($notifications) . " notifications to import\n";

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

            // Idempotency guard - skip anything already imported
            $existing = $this->fetchRow(sprintf(
                "SELECT id FROM notifications WHERE title = %s AND publish_date = %s LIMIT 1",
                $this->quote($title),
                $this->quote($publishDate)
            ));

            if ($existing) {
                echo "Skipped (already exists): {$title}\n";
                $skipped++;
                continue;
            }

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

            echo "Imported: {$title}\n";
            $imported++;
        }

        echo "Import completed! {$imported} imported, {$skipped} skipped.\n";
    }

    /**
     * Quote a value for safe inline use in the lookup query
     */
    private function quote(string $value): string
    {
        return $this->getAdapter()->getConnection()->quote($value);
    }
}
