<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class ImportNotificationsSeeder extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run(): void
    {
        // Check if notifications table exists
        $tableExists = $this->hasTable('notifications');
        if (!$tableExists) {
            echo "Notifications table does not exist. Please run migrations first.\n";
            return;
        }

        // Check if notifications are already imported
        $notificationsTable = $this->table('notifications');
        $existingCount = $this->fetchRow('SELECT COUNT(*) as count FROM notifications')['count'];

        if ($existingCount > 0) {
            echo "Notifications already exist in database ({$existingCount} records). Skipping import.\n";
            return;
        }

        // Path to the JSON file
        $jsonFilePath = __DIR__ . '/../../../pub/data/notifications.json';

        if (!file_exists($jsonFilePath)) {
            echo "Notifications JSON file not found at: {$jsonFilePath}\n";
            return;
        }

        // Read and decode JSON
        $jsonContent = file_get_contents($jsonFilePath);
        $notifications = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Error parsing JSON file: " . json_last_error_msg() . "\n";
            return;
        }

        if (!is_array($notifications)) {
            echo "Invalid JSON structure. Expected array of notifications.\n";
            return;
        }

        echo "Found " . count($notifications) . " notifications to import.\n";

        // Prepare data for insertion
        $data = [];
        foreach ($notifications as $notification) {
            $data[] = [
                'title' => $notification['title'] ?? '',
                'description' => $notification['description'] ?? null,
                'notification_no' => $notification['notificationNo'] ?? null,
                'icon' => $notification['icon'] ?? '📄',
                'show_arrow' => isset($notification['showArrow']) ? (bool)$notification['showArrow'] : true,
                'priority' => $notification['priority'] ?? 'medium',
                'publish_date' => $notification['publishDate'] ?? null,
                'expiry_date' => $notification['expiryDate'] ?? null,
                'category' => $notification['category'] ?? 'General',
                'file_url' => $notification['fileUrl'] ?? null,
                'file_name' => $notification['fileName'] ?? null,
                'is_active' => isset($notification['isActive']) ? (bool)$notification['isActive'] : true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        // Insert data
        if (!empty($data)) {
            $notificationsTable->insert($data)->save();
            echo "Successfully imported " . count($data) . " notifications.\n";
        } else {
            echo "No valid notifications found to import.\n";
        }
    }
}
